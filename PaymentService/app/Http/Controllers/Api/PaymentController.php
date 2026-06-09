<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'List data payment',
            'data' => Payment::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'payment_method' => 'nullable|string',
        ]);

        $orderServiceUrl = env('ORDER_SERVICE_URL', 'http://localhost:8003');

        $response = Http::get($orderServiceUrl . '/api/orders/' . $request->order_id);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan di OrderService'
            ], 404);
        }

        $orderResponse = $response->json();

        $order = $orderResponse['data'] ?? $orderResponse;

        $payment = Payment::create([
            'order_id' => $order['id'],
            'user_id' => $order['user_id'],
            'amount' => $order['total_price'],
            'payment_method' => $request->payment_method ?? 'bank_transfer',
            'status' => 'pending',
            'payment_url' => 'https://payment.example.com/pay/' . uniqid(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil dibuat berdasarkan order',
            'data' => $payment
        ], 201);
    }

    public function show($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail payment',
            'data' => $payment
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,failed,expired',
        ]);

        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        $payment->status = $request->status;

        if ($request->status === 'paid') {
            $payment->paid_at = now();
        }

        $payment->save();

        // Jika payment sudah paid, update status order otomatis ke OrderService
        if ($request->status === 'paid') {
            Http::put(env('ORDER_SERVICE_URL') . '/api/orders/' . $payment->order_id, [
                'status' => 'paid'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status payment berhasil diperbarui dan order ikut diperbarui',
            'data' => $payment
        ]);
    }

    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil dihapus'
        ]);
    }
    private function publishPaymentPaid($payment)
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest')
        );

        $channel = $connection->channel();

        $queue = env('RABBITMQ_QUEUE', 'payment-paid');

        $channel->queue_declare($queue, false, true, false, false);

        $payload = [
            'event' => 'payment.paid',
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
        ];

        $message = new AMQPMessage(json_encode($payload), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $channel->basic_publish($message, '', $queue);

        $channel->close();
        $connection->close();
    }
    public function getByOrder($order_id)
    {
        $payment = Payment::where('order_id', $order_id)->latest()->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment untuk order ini belum ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment ditemukan berdasarkan order',
            'data' => $payment
        ]);
    }
}
