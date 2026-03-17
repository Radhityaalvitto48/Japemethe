<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Midtrans\Snap;

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
     * Creates a payment record and generates a Midtrans Snap token.
     */
    public function store(StorePaymentRequest $request)
    {
        try {
            $validated = $request->validated();

            // Cek apakah order sudah ada payment
            $existingPayment = Payment::where('order_id', $validated['order_id'])->first();
            if ($existingPayment) {
                // Jika sudah ada dan masih pending, kembalikan snap_token yang ada
                if ($existingPayment->status_payment === 'pending' && $existingPayment->snap_token) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment sudah ada',
                        'data' => $existingPayment->load('order')
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Order ini sudah memiliki pembayaran'
                ], 422);
            }

            $order = Order::with('orderDetails.menu')->findOrFail($validated['order_id']);

            // Build item details untuk Midtrans
            $itemDetails = [];
            foreach ($order->orderDetails as $detail) {
                $itemDetails[] = [
                    'id' => (string) $detail->menu_id,
                    'price' => (int) round($detail->unit_price),
                    'quantity' => $detail->quantity,
                    'name' => mb_substr($detail->menu->name ?? 'Menu', 0, 50),
                ];
            }

            // Hitung total dari item details (harus match dengan gross_amount)
            $grossAmount = 0;
            foreach ($itemDetails as $item) {
                $grossAmount += $item['price'] * $item['quantity'];
            }

            // Jika ada diskon (total_price < gross dari items), tambahkan sebagai item diskon
            $orderTotal = (int) round($order->total_price);
            if ($orderTotal < $grossAmount) {
                $discountAmount = $grossAmount - $orderTotal;
                $itemDetails[] = [
                    'id' => 'DISCOUNT',
                    'price' => -$discountAmount,
                    'quantity' => 1,
                    'name' => 'Diskon Promo',
                ];
                $grossAmount = $orderTotal;
            }

            // Buat Midtrans Snap payload
            $midtransOrderId = 'ORDER-' . $order->id . '-' . time();

            $snapPayload = [
                'transaction_details' => [
                    'order_id' => $midtransOrderId,
                    'gross_amount' => $grossAmount,
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'email' => $order->customer_email ?? 'guest@japemethe.com',
                    'phone' => $order->customer_phone ?? '',
                ],
            ];

            $snapToken = Snap::getSnapToken($snapPayload);

            $payment = Payment::create([
                'order_id' => $validated['order_id'],
                'payment_method' => $validated['payment_method'] ?? 'midtrans_snap',
                'grass_amount' => $grossAmount,
                'status_payment' => 'pending',
                'snap_token' => $snapToken,
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
     * Endpoint ini dipanggil oleh Midtrans server setelah pembayaran.
     */
    public function handleNotification(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');

        // Verifikasi signature dari Midtrans
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;
        $signatureKey = $request->signature_key;
        $transactionStatus = $request->transaction_status;
        $fraudStatus = $request->fraud_status ?? null;

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!hash_equals($expectedSignature, $signatureKey ?? '')) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Extract real order ID dari format "ORDER-{id}-{timestamp}"
        $realOrderId = null;
        if (preg_match('/^ORDER-(\d+)-/', $orderId, $matches)) {
            $realOrderId = $matches[1];
        }

        if (!$realOrderId) {
            return response()->json(['message' => 'Invalid order ID format'], 400);
        }

        $payment = Payment::where('order_id', $realOrderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Update status berdasarkan transaction_status dari Midtrans
        if ($transactionStatus === 'capture') {
            // Untuk credit card: cek fraud status
            if ($fraudStatus === 'accept') {
                $payment->update([
                    'status_payment' => 'completed',
                    'payment_method' => $request->payment_type ?? $payment->payment_method,
                    'payment_date' => now(),
                ]);
                $payment->order->update(['status_order' => 'in_progress']);
            } elseif ($fraudStatus === 'challenge') {
                // Tetap pending, tunggu merchant review
                $payment->update(['status_payment' => 'pending']);
            }
        } elseif ($transactionStatus === 'settlement') {
            $payment->update([
                'status_payment' => 'completed',
                'payment_method' => $request->payment_type ?? $payment->payment_method,
                'payment_date' => now(),
            ]);
            $payment->order->update(['status_order' => 'in_progress']);
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel', 'failure'])) {
            $payment->update([
                'status_payment' => 'failed',
                'payment_date' => now(),
            ]);
        } elseif ($transactionStatus === 'pending') {
            $payment->update(['status_payment' => 'pending']);
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
