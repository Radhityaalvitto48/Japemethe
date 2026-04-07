<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Snap;

class PaymentController extends \App\Http\Controllers\Controller
{
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = Order::query()
            ->with(['orderDetails.menu:id,name', 'payment'])
            ->find($validated['order_id']);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $paymentMethod = strtolower((string) ($validated['payment_method'] ?? 'cash'));
        $amount = (float) ($validated['grass_amount'] ?? $order->total_price);

        if ($this->isCashMethod($paymentMethod)) {
            return $this->processCashPayment($order, $amount);
        }

        return $this->processDigitalPayment($order, $paymentMethod, $amount);
    }

    public function createSnapTokenForOrder(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'grass_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:30'],
        ]);

        $order = Order::query()
            ->with(['orderDetails.menu:id,name', 'payment'])
            ->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $paymentMethod = strtolower((string) ($validated['payment_method'] ?? 'midtrans'));
        $amount = (float) ($validated['grass_amount'] ?? $order->total_price);

        return $this->processDigitalPayment($order, $paymentMethod, $amount);
    }

    public function payCash(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'grass_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = Order::query()
            ->with(['orderDetails.menu:id,name', 'payment'])
            ->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        return $this->processCashPayment($order, (float) ($validated['grass_amount'] ?? $order->total_price));
    }

    public function getByOrder(int $orderId): JsonResponse
    {
        $payment = Payment::query()
            ->where('order_id', $orderId)
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status_payment' => ['required', 'in:pending,completed,failed'],
        ]);

        $payment = Payment::query()->find($id);
        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Data pembayaran tidak ditemukan.',
            ], 404);
        }

        $payment->update([
            'status_payment' => $validated['status_payment'],
            'payment_date' => $validated['status_payment'] === 'completed'
                ? ($payment->payment_date ?? now())
                : $payment->payment_date,
        ]);

        if ($payment->order && $validated['status_payment'] === 'completed') {
            $payment->order->update(['status_order' => 'completed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pembayaran berhasil diperbarui.',
            'data' => $payment->fresh(),
        ]);
    }

    public function handleNotification(Request $request): JsonResponse
    {
        $payload = $request->all();

        $midtransOrderId = (string) ($payload['order_id'] ?? '');
        if ($midtransOrderId === '') {
            return response()->json([
                'success' => false,
                'message' => 'order_id tidak ditemukan pada notifikasi.',
            ], 422);
        }

        $signatureKey = (string) ($payload['signature_key'] ?? '');
        if ($signatureKey !== '' && ! $this->isValidMidtransSignature($payload, $signatureKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Signature Midtrans tidak valid.',
            ], 403);
        }

        $orderNumber = $this->extractOrderNumber($midtransOrderId);
        $order = Order::query()->where('order_number', $orderNumber)->first();
        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $paymentStatus = $this->mapMidtransStatus(
            (string) ($payload['transaction_status'] ?? ''),
            (string) ($payload['fraud_status'] ?? ''),
        );

        $payment = Payment::query()->firstOrNew(['order_id' => $order->id]);
        $payment->payment_method = (string) ($payload['payment_type'] ?? $payment->payment_method ?? 'midtrans');
        $payment->status_payment = $paymentStatus;
        $payment->grass_amount = (float) ($payload['gross_amount'] ?? $order->total_price);

        if ($paymentStatus === 'completed' && ! $payment->payment_date) {
            $payment->payment_date = now();
        }

        $payment->save();

        if ($paymentStatus === 'completed') {
            $order->update(['status_order' => 'completed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi Midtrans berhasil diproses.',
        ]);
    }

    private function processCashPayment(Order $order, float $amount): JsonResponse
    {
        $amount = $amount > 0 ? $amount : (float) $order->total_price;

        $payment = DB::transaction(function () use ($order, $amount): Payment {
            $payment = Payment::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_method' => 'cash',
                    'status_payment' => 'completed',
                    'grass_amount' => $amount,
                    'snap_token' => null,
                    'payment_date' => now(),
                ],
            );

            $order->update(['status_order' => 'completed']);

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran tunai berhasil diselesaikan.',
            'data' => [
                'payment' => $payment->fresh(),
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status_order' => 'completed',
                ],
            ],
        ]);
    }

    private function processDigitalPayment(Order $order, string $paymentMethod, float $amount): JsonResponse
    {
        if (! config('services.midtrans.server_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Midtrans belum lengkap.',
            ], 422);
        }

        $amount = $amount > 0 ? $amount : (float) $order->total_price;
        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah pembayaran tidak valid.',
            ], 422);
        }

        $existingPendingPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status_payment', 'pending')
            ->whereNotNull('snap_token')
            ->latest('id')
            ->first();

        if ($existingPendingPayment) {
            return response()->json([
                'success' => true,
                'message' => 'Snap token pembayaran digital sudah tersedia.',
                'snap_token' => $existingPendingPayment->snap_token,
                'data' => [
                    'payment' => $existingPendingPayment,
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status_order' => $order->status_order,
                    ],
                ],
            ]);
        }

        $payload = $this->buildMidtransPayload($order, $amount);

        try {
            $snapToken = Snap::getSnapToken($payload);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat snap token Midtrans.',
            ], 500);
        }

        $payment = Payment::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'payment_method' => $paymentMethod ?: 'midtrans',
                'status_payment' => 'pending',
                'grass_amount' => $amount,
                'snap_token' => $snapToken,
                'payment_date' => null,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Snap token Midtrans berhasil dibuat.',
            'snap_token' => $snapToken,
            'data' => [
                'payment' => $payment->fresh(),
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status_order' => $order->status_order,
                ],
            ],
        ]);
    }

    private function buildMidtransPayload(Order $order, float $amount): array
    {
        $itemDetails = $order->orderDetails
            ->map(function ($detail) {
                return [
                    'id' => (string) $detail->menu_id,
                    'price' => (int) round((float) $detail->unit_price),
                    'quantity' => (int) $detail->quantity,
                    'name' => mb_substr($detail->menu?->name ?? 'Menu', 0, 50),
                ];
            })
            ->values()
            ->all();

        if ($itemDetails === []) {
            $itemDetails[] = [
                'id' => 'ORDER-' . $order->id,
                'price' => (int) round($amount),
                'quantity' => 1,
                'name' => 'Pesanan #' . $order->order_number,
            ];
        }

        return [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) round($amount),
            ],
            'customer_details' => [
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'item_details' => $itemDetails,
        ];
    }

    private function isCashMethod(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['cash', 'tunai'], true);
    }

    private function extractOrderNumber(string $midtransOrderId): string
    {
        if (str_contains($midtransOrderId, '-P')) {
            return explode('-P', $midtransOrderId)[0];
        }

        return $midtransOrderId;
    }

    private function isValidMidtransSignature(array $payload, string $signatureKey): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');

        if ($serverKey === '' || $orderId === '' || $statusCode === '' || $grossAmount === '') {
            return false;
        }

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    private function mapMidtransStatus(string $transactionStatus, string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept' ? 'completed' : 'pending',
            'settlement' => 'completed',
            'pending' => 'pending',
            'deny', 'cancel', 'expire', 'failure' => 'failed',
            default => 'pending',
        };
    }
}
