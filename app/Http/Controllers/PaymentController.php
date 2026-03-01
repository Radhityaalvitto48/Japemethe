<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::with(['order'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {
        try {
            $validated = $request->validated();

            // Cek apakah order sudah ada payment
            $existingPayment = Payment::where('order_id', $validated['order_id'])->first();
            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ini sudah memiliki pembayaran'
                ], 422);
            }

            $payment = Payment::create([
                'order_id' => $validated['order_id'],
                'payment_method' => $validated['payment_method'],
                'grass_amount' => $validated['grass_amount'],
                'status_payment' => 'pending',
                'snap_token' => '', // Akan diisi setelah generate dari Midtrans
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment berhasil dibuat',
                'data' => $payment->load('order')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment by order ID.
     */
    public function getByOrder(string $orderId)
    {
        $payment = Payment::with(['order'])
            ->where('order_id', $orderId)
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan untuk order ini'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update payment status.
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status_payment' => 'required|in:pending,completed,failed'
        ]);

        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        $payment->update([
            'status_payment' => $request->status_payment
        ]);

        // Update order status jika payment completed
        if ($request->status_payment === 'completed') {
            $payment->order->update(['status_order' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status payment berhasil diupdate',
            'data' => $payment
        ]);
    }

    /**
     * Handle Midtrans notification callback.
     */
    public function handleNotification(Request $request)
    {
        // TODO: Implementasi Midtrans notification handler
        // Ini akan dipanggil oleh Midtrans setelah pembayaran

        $orderId = $request->order_id;
        $transactionStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? null;

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $payment->update(['status_payment' => 'completed']);
            $payment->order->update(['status_order' => 'in_progress']);
        } elseif ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
            $payment->update(['status_payment' => 'failed']);
        }

        return response()->json(['message' => 'Notification handled']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::with(['order'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
