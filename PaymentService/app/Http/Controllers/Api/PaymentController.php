<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

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

        if ($request->status === 'paid') {
            try {
                $orderResponse = Http::timeout(5)
                    ->retry(2, 200)
                    ->put(
                        rtrim(env('ORDER_SERVICE_URL', 'http://order-service:8000'), '/')
                            . '/api/orders/' . $payment->order_id,
                        ['status' => 'paid']
                    );
            } catch (Throwable $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment belum diubah karena OrderService tidak dapat dihubungi',
                ], 502);
            }

            if ($orderResponse->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment belum diubah karena status order gagal diperbarui',
                    'order_service_response' => $orderResponse->json(),
                ], 502);
            }
        }

        $payment->update([
            'status' => $request->status,
            'paid_at' => $request->status === 'paid' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->status === 'paid'
                ? 'Payment lunas dan status order berhasil diubah menjadi paid'
                : 'Status payment berhasil diperbarui',
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
}
