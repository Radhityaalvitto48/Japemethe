<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderPromo;
use App\Models\Promo;
use App\Models\Table;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends \App\Http\Controllers\Controller
{
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $table = Table::query()
            ->whereKey($validated['table_id'])
            ->where('is_active', true)
            ->first();

        if (! $table) {
            return response()->json([
                'success' => false,
                'message' => 'Meja tidak ditemukan atau tidak aktif.',
            ], 422);
        }

        $menuIds = collect($validated['items'])
            ->pluck('menu_id')
            ->unique()
            ->values();

        $menuMap = Menu::query()
            ->whereIn('id', $menuIds)
            ->get()
            ->keyBy('id');

        if ($menuMap->count() !== $menuIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada menu yang tidak ditemukan.',
            ], 422);
        }

        $orderItems = [];
        $totalItems = 0;
        $baseTotal = 0.0;

        foreach ($validated['items'] as $item) {
            /** @var Menu $menu */
            $menu = $menuMap[$item['menu_id']];

            if ($menu->status_menu !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => "Menu {$menu->name} sedang tidak tersedia.",
                ], 422);
            }

            if ((int) $menu->stock < (int) $item['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok menu {$menu->name} tidak mencukupi.",
                ], 422);
            }

            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $menu->price;
            $subtotal = $unitPrice * $quantity;

            $orderItems[] = [
                'menu_id' => $menu->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'note' => $item['note'] ?? null,
            ];

            $totalItems += $quantity;
            $baseTotal += $subtotal;
        }

        $promo = null;
        $promoDiscount = 0.0;
        $finalTotal = $baseTotal;

        if (! empty($validated['promo_code'])) {
            $promo = $this->findValidPromo($validated['promo_code']);

            if (! $promo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode promo tidak valid atau tidak aktif.',
                ], 422);
            }

            if ($baseTotal < (float) $promo->minimum_price) {
                return response()->json([
                    'success' => false,
                    'message' => sprintf(
                        'Promo hanya berlaku untuk minimal transaksi Rp %s.',
                        number_format((float) $promo->minimum_price, 0, ',', '.')
                    ),
                ], 422);
            }

            $promoDiscount = $this->calculatePromoDiscount($promo, $baseTotal);
            $finalTotal = max(0, $baseTotal - $promoDiscount);
        }

        $order = DB::transaction(function () use ($validated, $orderItems, $totalItems, $promo, $promoDiscount, $finalTotal): Order {
            $order = Order::create([
                'table_id' => $validated['table_id'],
                'total_items' => $totalItems,
                'total_price' => $finalTotal,
                'status_order' => 'pending',
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'ordered_at' => now(),
                'promo_id' => $promo?->id,
            ]);

            foreach ($orderItems as $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'menu_id' => $item['menu_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'note' => $item['note'],
                ]);
            }

            if ($promo && $promoDiscount > 0) {
                OrderPromo::query()->create([
                    'order_id' => $order->id,
                    'promo_id' => $promo->id,
                    'discount_amount' => $promoDiscount,
                ]);
            }

            return $order;
        });

        $order->load([
            'table:id,table_number',
            'orderDetails.menu:id,name',
            'payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat. Tunjukkan QR ke kasir untuk validasi pembayaran.',
            'data' => $this->transformOrder($order),
        ], 201);
    }

    public function getByIds(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = collect($validated['ids'])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $orders = Order::query()
            ->with([
                'table:id,table_number',
                'orderDetails.menu:id,name',
                'payment',
            ])
            ->whereIn('id', $ids)
            ->orderByDesc('ordered_at')
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    public function showByNumber(string $orderNumber): JsonResponse
    {
        $order = Order::query()
            ->with([
                'table:id,table_number',
                'orderDetails.menu:id,name',
                'payment',
            ])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformOrder($order),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status_order' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ]);

        $order = Order::query()->find($id);
        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        $order->update([
            'status_order' => $validated['status_order'],
        ]);

        $order->load([
            'table:id,table_number',
            'orderDetails.menu:id,name',
            'payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diperbarui.',
            'data' => $this->transformOrder($order),
        ]);
    }

    public function scanQr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_payload' => ['required', 'string'],
        ]);

        $orderNumber = $this->decodeQrPayload($validated['qr_payload']);
        if (! $orderNumber) {
            return response()->json([
                'success' => false,
                'message' => 'QR tidak valid.',
            ], 422);
        }

        $order = Order::query()
            ->with([
                'table:id,table_number',
                'orderDetails.menu:id,name',
                'payment',
            ])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil ditemukan.',
            'data' => $this->transformOrder($order),
        ]);
    }

    public function applyPromo(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'promo_code' => ['required', 'string', 'max:30'],
        ]);

        $order = Order::query()
            ->with(['orderDetails', 'payment', 'table:id,table_number', 'orderDetails.menu:id,name'])
            ->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan.',
            ], 404);
        }

        if (in_array($order->status_order, ['completed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Order sudah final dan tidak bisa diubah.',
            ], 422);
        }

        $promo = $this->findValidPromo($validated['promo_code']);
        if (! $promo) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak valid atau tidak aktif.',
            ], 422);
        }

        $baseTotal = (float) $order->orderDetails->sum('subtotal');
        if ($baseTotal < (float) $promo->minimum_price) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Promo hanya berlaku untuk minimal transaksi Rp %s.',
                    number_format((float) $promo->minimum_price, 0, ',', '.')
                ),
            ], 422);
        }

        $discount = $this->calculatePromoDiscount($promo, $baseTotal);
        $finalTotal = max(0, $baseTotal - $discount);

        DB::transaction(function () use ($order, $promo, $discount, $finalTotal): void {
            $order->update([
                'promo_id' => $promo->id,
                'total_price' => $finalTotal,
            ]);

            OrderPromo::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'promo_id' => $promo->id,
                    'discount_amount' => $discount,
                ],
            );
        });

        $order->refresh()->load([
            'table:id,table_number',
            'orderDetails.menu:id,name',
            'payment',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diterapkan.',
            'data' => [
                'order' => $this->transformOrder($order),
                'promo' => [
                    'id' => $promo->id,
                    'code' => $promo->code,
                    'name' => $promo->name,
                    'discount_amount' => $discount,
                ],
            ],
        ]);
    }

    private function transformOrder(Order $order): array
    {
        $baseTotal = (float) $order->orderDetails->sum('subtotal');
        $discountAmount = max(0, $baseTotal - (float) $order->total_price);
        $qrData = $this->buildQrData($order->order_number);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'total_items' => (int) $order->total_items,
            'total_price' => (float) $order->total_price,
            'status_order' => $order->status_order,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'ordered_at' => $order->ordered_at,
            'created_at' => $order->created_at,
            'table' => $order->table ? [
                'id' => $order->table->id,
                'id_table' => $order->table->id,
                'table_number' => $order->table->table_number,
            ] : null,
            'order_details' => $order->orderDetails->map(function (OrderDetail $detail) {
                return [
                    'id' => $detail->id,
                    'menu_id' => $detail->menu_id,
                    'quantity' => (int) $detail->quantity,
                    'unit_price' => (float) $detail->unit_price,
                    'subtotal' => (float) $detail->subtotal,
                    'note' => $detail->note,
                    'menu' => $detail->menu ? [
                        'id' => $detail->menu->id,
                        'id_menu' => $detail->menu->id,
                        'name' => $detail->menu->name,
                    ] : null,
                ];
            })->values(),
            'payment' => $order->payment ? [
                'id' => $order->payment->id,
                'payment_method' => $order->payment->payment_method,
                'status_payment' => $order->payment->status_payment,
                'grass_amount' => (float) $order->payment->grass_amount,
                'snap_token' => $order->payment->snap_token,
            ] : null,
            'base_total' => $baseTotal,
            'discount_amount' => $discountAmount,
            'qr_payload' => $qrData['payload'],
            'qr_code_data_uri' => $qrData['data_uri'],
        ];
    }

    private function buildQrData(string $orderNumber): array
    {
        $payload = Crypt::encryptString($orderNumber);
        $svg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->generate($payload);

        return [
            'payload' => $payload,
            'data_uri' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ];
    }

    private function decodeQrPayload(string $payload): ?string
    {
        try {
            return Crypt::decryptString($payload);
        } catch (DecryptException) {
            $normalized = trim($payload);

            if ($normalized !== '' && preg_match('/^[A-Za-z0-9\-]+$/', $normalized) === 1) {
                return $normalized;
            }

            return null;
        }
    }

    private function findValidPromo(string $code): ?Promo
    {
        return Promo::query()
            ->whereRaw('UPPER(code) = ?', [Str::upper(trim($code))])
            ->where('status_promo', 'active')
            ->whereDate('valid_from', '<=', now()->toDateString())
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->first();
    }

    private function calculatePromoDiscount(Promo $promo, float $baseTotal): float
    {
        $discount = $promo->type === 'percentage'
            ? ($baseTotal * ((float) $promo->value / 100))
            : (float) $promo->value;

        return (float) min($baseTotal, $discount);
    }
}
