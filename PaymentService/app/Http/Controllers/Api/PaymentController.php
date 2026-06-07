<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

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
            'user_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        $payment = Payment::create([
            'order_id' => $request->order_id,
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method ?? 'bank_transfer',
            'status' => 'pending',
            'payment_url' => 'https://payment.example.com/pay/' . uniqid(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment berhasil dibuat',
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

        return response()->json([
            'success' => true,
            'message' => 'Status payment berhasil diperbarui',
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
