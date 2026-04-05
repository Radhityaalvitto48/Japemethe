<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Promo;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function menus(): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Menu> $menus */
        $menus = Menu::query()
            ->with('menuCategory:id,name')
            ->where('status_menu', '=', 'available')
            ->orderBy('name', 'asc')
            ->get(['id', 'menu_category_id', 'name', 'price', 'stock']);

        return response()->json([
            'success' => true,
            'data' => $menus->map(fn(Menu $menu): array => [
                'id' => $menu->id,
                'menu_category_id' => $menu->menu_category_id,
                'menu_category_name' => $menu->menuCategory?->name,
                'name' => $menu->name,
                'price' => (float) $menu->price,
                'stock' => (int) $menu->stock,
            ])->values(),
        ]);
    }

    public function tables(): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $tables = Table::query()
            ->where('is_active', '=', true)
            ->orderBy('table_number', 'asc')
            ->get(['id', 'table_number']);

        return response()->json([
            'success' => true,
            'data' => $tables,
        ]);
    }

    public function activeOrders(): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $orders = Order::query()
            ->with(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value'])
            ->whereIn('status_order', ['pending', 'in_progress', 'completed'])
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn(Order $order) => $this->serializeOrder($order))->values(),
        ]);
    }

    public function showOrder(Order $order): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $order->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function scanOrder(Request $request): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $validated = $request->validate([
            'payload' => ['required', 'string', 'max:1000'],
        ]);

        $candidate = $this->normalizeScanPayload($validated['payload']);

        if (! $candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Payload QR tidak valid.',
            ], 422);
        }

        $order = Order::query()
            ->with(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value'])
            ->where('order_number', '=', $candidate)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan untuk hasil scan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function createManualOrder(Request $request): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $validated = $request->validate([
            'table_id' => ['required', 'integer', 'exists:tables,id'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $table = Table::query()->where('is_active', '=', true)->find($validated['table_id']);
        if (! $table) {
            return response()->json([
                'success' => false,
                'message' => 'Meja tidak aktif.',
            ], 422);
        }

        $menuIds = collect($validated['items'])->pluck('menu_id')->unique()->values();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Menu> $menuMap */
        $menuMap = Menu::query()
            ->whereIn('id', $menuIds)
            ->where('status_menu', '=', 'available')
            ->get()
            ->keyBy('id');

        if ($menuMap->count() !== $menuIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Ada menu yang tidak tersedia.',
            ], 422);
        }

        $computedItems = [];
        $totalItems = 0;
        $baseTotal = 0.0;

        foreach ($validated['items'] as $item) {
            $menu = $menuMap->get((int) $item['menu_id']);

            if (! $menu || (int) $menu->stock < (int) $item['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok menu tidak mencukupi.',
                ], 422);
            }

            $quantity = (int) $item['quantity'];
            $price = (float) $menu->price;
            $subtotal = $price * $quantity;

            $computedItems[] = [
                'menu_id' => (int) $menu->id,
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $subtotal,
                'note' => $item['note'] ?? null,
            ];

            $totalItems += $quantity;
            $baseTotal += $subtotal;
        }

        $order = DB::transaction(function () use ($validated, $computedItems, $totalItems, $baseTotal) {
            /** @var Order $createdOrder */
            $createdOrder = Order::query()->create([
                'table_id' => (int) $validated['table_id'],
                'total_items' => $totalItems,
                'total_price' => $baseTotal,
                'status_order' => 'pending',
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'ordered_at' => now(),
            ]);

            foreach ($computedItems as $item) {
                OrderDetail::query()->create([
                    'order_id' => $createdOrder->id,
                    'menu_id' => $item['menu_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'note' => $item['note'],
                ]);
            }

            return $createdOrder;
        });

        $order->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan manual berhasil dibuat.',
            'data' => $this->serializeOrder($order),
        ], 201);
    }

    public function addOrderItem(Request $request, Order $order): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        if ($order->payment?->status_payment === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Order sudah lunas dan tidak bisa diubah.',
            ], 422);
        }

        $validated = $request->validate([
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $menu = Menu::query()
            ->where('id', '=', (int) $validated['menu_id'])
            ->where('status_menu', '=', 'available')
            ->first();

        if (! $menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu tidak tersedia.',
            ], 422);
        }

        $quantityToAdd = (int) ($validated['quantity'] ?? 1);
        $hasNoteUpdate = array_key_exists('note', $validated);
        $normalizedNote = null;

        if ($hasNoteUpdate) {
            $rawNote = trim((string) ($validated['note'] ?? ''));
            $normalizedNote = $rawNote !== '' ? $rawNote : null;
        }

        DB::transaction(function () use ($order, $menu, $quantityToAdd, $hasNoteUpdate, $normalizedNote) {
            $detail = OrderDetail::query()
                ->where('order_id', '=', $order->id)
                ->where('menu_id', '=', $menu->id)
                ->first();

            $nextQty = ($detail?->quantity ?? 0) + $quantityToAdd;

            if ($nextQty > (int) $menu->stock) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Stok menu tidak mencukupi untuk penambahan ini.',
                ], 422));
            }

            if ($detail) {
                DB::table('order_details')->where('id', '=', $detail->id)->update([
                    'quantity' => $nextQty,
                    'unit_price' => (float) $menu->price,
                    'subtotal' => $nextQty * (float) $menu->price,
                    'note' => $hasNoteUpdate ? $normalizedNote : $detail->note,
                ]);
            } else {
                OrderDetail::query()->create([
                    'order_id' => $order->id,
                    'menu_id' => $menu->id,
                    'quantity' => $nextQty,
                    'unit_price' => (float) $menu->price,
                    'subtotal' => $nextQty * (float) $menu->price,
                    'note' => $normalizedNote,
                ]);
            }

            $this->recalculateOrderTotals($order->fresh());
        });

        $order->refresh()->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil ditambahkan ke order.',
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function updateOrderItem(Request $request, Order $order, OrderDetail $detail): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        if ($detail->order_id !== $order->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item order tidak valid.',
            ], 422);
        }

        if ($order->payment?->status_payment === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Order sudah lunas dan tidak bisa diubah.',
            ], 422);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $menu = $detail->menu;
        $nextQty = (int) $validated['quantity'];
        $hasNoteUpdate = array_key_exists('note', $validated);
        $normalizedNote = null;

        if ($hasNoteUpdate) {
            $rawNote = trim((string) ($validated['note'] ?? ''));
            $normalizedNote = $rawNote !== '' ? $rawNote : null;
        }

        if (! $menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu pada item ini tidak ditemukan.',
            ], 422);
        }

        if ($nextQty > (int) $menu->stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stok menu tidak mencukupi.',
            ], 422);
        }

        DB::transaction(function () use ($detail, $menu, $nextQty, $order, $hasNoteUpdate, $normalizedNote) {
            if ($nextQty === 0) {
                DB::table('order_details')->where('id', '=', $detail->id)->delete();
            } else {
                DB::table('order_details')->where('id', '=', $detail->id)->update([
                    'quantity' => $nextQty,
                    'unit_price' => (float) $menu->price,
                    'subtotal' => $nextQty * (float) $menu->price,
                    'note' => $hasNoteUpdate ? $normalizedNote : $detail->note,
                ]);
            }

            $this->recalculateOrderTotals($order->fresh());
        });

        $order->refresh()->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'message' => 'Item order berhasil diperbarui.',
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function applyPromo(Request $request, Order $order): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $order->load('orderDetails');
        $baseTotal = (float) $order->orderDetails->sum('subtotal');

        $promo = Promo::query()
            ->whereRaw('UPPER(code) = ?', [Str::upper(trim($validated['code']))])
            ->where('status_promo', '=', 'active')
            ->whereDate('valid_from', '<=', now()->toDateString())
            ->whereDate('valid_until', '>=', now()->toDateString())
            ->first();

        if (! $promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak valid atau tidak aktif.',
            ], 422);
        }

        if ($baseTotal < (float) $promo->minimum_price) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum transaksi promo belum terpenuhi.',
            ], 422);
        }

        $discount = $promo->type === 'percentage'
            ? ($baseTotal * ((float) $promo->value / 100))
            : (float) $promo->value;

        $discount = min($discount, $baseTotal);
        $finalTotal = max(0, $baseTotal - $discount);

        DB::table('orders')->where('id', '=', $order->id)->update([
            'promo_id' => $promo->id,
            'total_price' => $finalTotal,
        ]);

        $payment = $order->payment;
        if ($payment && $payment->status_payment === 'pending') {
            DB::table('payments')->where('id', '=', $payment->id)->update([
                'grass_amount' => $finalTotal,
                'snap_token' => null,
            ]);
        }

        $order->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diterapkan.',
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function removePromo(Order $order): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $order->load('orderDetails');

        $baseTotal = (float) $order->orderDetails->sum('subtotal');

        DB::table('orders')->where('id', '=', $order->id)->update([
            'promo_id' => null,
            'total_price' => $baseTotal,
        ]);

        $payment = $order->payment;
        if ($payment && $payment->status_payment === 'pending') {
            DB::table('payments')->where('id', '=', $payment->id)->update([
                'grass_amount' => $baseTotal,
                'snap_token' => null,
            ]);
        }

        $order->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'message' => 'Promo dihapus dari pesanan.',
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function payCash(Request $request, Order $order): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $validated = $request->validate([
            'cash_received' => ['nullable', 'numeric', 'min:0'],
        ]);

        $amount = (float) $order->total_price;
        $cashReceived = (float) ($validated['cash_received'] ?? $amount);

        if ($cashReceived < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Nominal uang tunai kurang dari total tagihan.',
            ], 422);
        }

        try {
            $payment = DB::transaction(function () use ($order, $amount) {
                $lockedOrder = Order::query()
                    ->with('payment')
                    ->lockForUpdate()
                    ->find($order->id);

                if (! $lockedOrder) {
                    throw new \RuntimeException('Order tidak ditemukan.');
                }

                if ($lockedOrder->payment?->status_payment === 'completed') {
                    throw new \RuntimeException('Order sudah lunas dan tidak bisa dibayar ulang.');
                }

                $this->decrementMenuStockForOrder($lockedOrder);

                $updatedPayment = Payment::query()->updateOrCreate(
                    ['order_id' => $lockedOrder->id],
                    [
                        'payment_method' => 'cash',
                        'status_payment' => 'completed',
                        'grass_amount' => $amount,
                        'snap_token' => null,
                        'payment_date' => now(),
                    ],
                );

                DB::table('orders')->where('id', '=', $lockedOrder->id)->update([
                    'status_order' => 'completed',
                ]);

                return $updatedPayment;
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $order->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran tunai berhasil diproses.',
            'change_amount' => max(0, $cashReceived - $amount),
            'payment' => $payment,
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function markDigitalPaid(Order $order): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::query()
                    ->with('payment')
                    ->lockForUpdate()
                    ->find($order->id);

                if (! $lockedOrder) {
                    throw new \RuntimeException('Order tidak ditemukan.');
                }

                if ($lockedOrder->payment?->status_payment === 'completed') {
                    throw new \RuntimeException('Order sudah lunas dan tidak bisa dibayar ulang.');
                }

                $this->decrementMenuStockForOrder($lockedOrder);

                Payment::query()->updateOrCreate(
                    ['order_id' => $lockedOrder->id],
                    [
                        'payment_method' => 'qris',
                        'status_payment' => 'completed',
                        'grass_amount' => (float) $lockedOrder->total_price,
                        'snap_token' => null,
                        'payment_date' => now(),
                    ],
                );

                DB::table('orders')->where('id', '=', $lockedOrder->id)->update([
                    'status_order' => 'completed',
                ]);
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $order->load(['table:id,table_number', 'orderDetails.menu:id,name', 'payment', 'promo:id,code,name,type,value']);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran QRIS ditandai selesai.',
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function pendingReservations(): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        $reservations = Reservation::query()
            ->with('table:id,table_number')
            ->where('status', '=', 'pending')
            ->orderBy('reservation_date', 'asc')
            ->orderBy('reservation_time', 'asc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reservations->map(fn(Reservation $reservation): array => $this->serializeReservation($reservation))->values(),
        ]);
    }

    public function confirmReservation(Reservation $reservation): JsonResponse
    {
        if ($response = $this->authorizePosAccess()) {
            return $response;
        }

        if ($reservation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi sudah diproses dan tidak dapat dikonfirmasi lagi.',
            ], 422);
        }

        DB::table('reservations')->where('id', '=', $reservation->id)->update([
            'status' => 'confirmed',
        ]);

        $reservation->load('table:id,table_number');

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dikonfirmasi oleh kasir.',
            'data' => $this->serializeReservation($reservation),
        ]);
    }

    private function authorizePosAccess(): ?JsonResponse
    {
        $user = Auth::user();

        if (! $user || ! in_array($user->role, ['admin', 'kasir'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return null;
    }

    private function normalizeScanPayload(string $rawPayload): ?string
    {
        $payload = trim($rawPayload);
        if ($payload === '') {
            return null;
        }

        if (preg_match('/^[A-Z0-9]{8,30}$/', $payload)) {
            return Str::upper($payload);
        }

        if (preg_match('/\b(\d{11,30})\b/', $payload, $matches)) {
            return $matches[1];
        }

        if (filter_var($payload, FILTER_VALIDATE_URL)) {
            $parts = parse_url($payload);
            $query = [];
            parse_str($parts['query'] ?? '', $query);

            $fromQuery = $query['order_number'] ?? $query['order'] ?? null;
            if (is_string($fromQuery) && trim($fromQuery) !== '') {
                return trim($fromQuery);
            }

            if (! empty($parts['path'])) {
                $segments = array_values(array_filter(explode('/', trim($parts['path'], '/'))));
                $last = end($segments);

                if (is_string($last) && preg_match('/^[A-Za-z0-9_-]{8,30}$/', $last)) {
                    return Str::upper($last);
                }
            }
        }

        if (Str::startsWith($payload, ['{', '['])) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                foreach (['order_number', 'orderNumber', 'order'] as $key) {
                    if (! empty($decoded[$key]) && is_string($decoded[$key])) {
                        return trim($decoded[$key]);
                    }
                }
            }
        }

        try {
            $decrypted = Crypt::decryptString($payload);
            if ($decrypted !== '' && $decrypted !== $payload) {
                return $this->normalizeScanPayload($decrypted);
            }
        } catch (DecryptException) {
            // ignore invalid encrypted payload
        }

        return null;
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $order->loadMissing(['orderDetails', 'promo', 'payment']);

        $baseTotal = (float) $order->orderDetails->sum('subtotal');
        $totalItems = (int) $order->orderDetails->sum('quantity');

        $activePromo = null;
        if ($order->promo) {
            $activePromo = Promo::query()
                ->where('id', '=', $order->promo_id)
                ->where('status_promo', '=', 'active')
                ->whereDate('valid_from', '<=', now()->toDateString())
                ->whereDate('valid_until', '>=', now()->toDateString())
                ->first();
        }

        $discount = 0.0;
        $promoId = null;

        if ($activePromo && $baseTotal >= (float) $activePromo->minimum_price) {
            $discount = $activePromo->type === 'percentage'
                ? ($baseTotal * ((float) $activePromo->value / 100))
                : (float) $activePromo->value;
            $discount = min($discount, $baseTotal);
            $promoId = $activePromo->id;
        }

        $finalTotal = max(0, $baseTotal - $discount);

        DB::table('orders')->where('id', '=', $order->id)->update([
            'total_items' => $totalItems,
            'total_price' => $finalTotal,
            'promo_id' => $promoId,
        ]);

        if ($order->payment && $order->payment->status_payment !== 'completed') {
            DB::table('payments')->where('id', '=', $order->payment->id)->update([
                'grass_amount' => $finalTotal,
                'snap_token' => null,
            ]);
        }
    }

    private function decrementMenuStockForOrder(Order $order): void
    {
        $details = OrderDetail::query()
            ->where('order_id', '=', $order->id)
            ->get(['menu_id', 'quantity']);

        if ($details->isEmpty()) {
            throw new \RuntimeException('Order belum memiliki item.');
        }

        $menuIds = $details->pluck('menu_id')->unique()->values();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Menu> $menus */
        $menus = Menu::query()
            ->whereIn('id', $menuIds)
            ->lockForUpdate()
            ->get(['id', 'name', 'stock'])
            ->keyBy('id');

        foreach ($details as $detail) {
            $menu = $menus->get((int) $detail->menu_id);

            if (! $menu) {
                throw new \RuntimeException('Ada menu order yang tidak ditemukan saat memproses pembayaran.');
            }

            if ((int) $menu->stock < (int) $detail->quantity) {
                throw new \RuntimeException("Stok menu {$menu->name} tidak mencukupi saat pembayaran.");
            }
        }

        foreach ($details as $detail) {
            DB::table('menus')
                ->where('id', '=', (int) $detail->menu_id)
                ->decrement('stock', (int) $detail->quantity);
        }
    }

    private function serializeOrder(Order $order): array
    {
        $subtotal = (float) $order->orderDetails->sum('subtotal');
        $total = (float) $order->total_price;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status_order' => $order->status_order,
            'table_number' => $order->table?->table_number,
            'total_items' => $order->total_items,
            'subtotal_amount' => $subtotal,
            'discount_amount' => max(0, $subtotal - $total),
            'total_price' => $total,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'created_at' => $order->created_at,
            'items' => $order->orderDetails->map(function (OrderDetail $detail): array {
                return [
                    'id' => $detail->id,
                    'menu_name' => $detail->menu?->name,
                    'quantity' => $detail->quantity,
                    'unit_price' => (float) $detail->unit_price,
                    'subtotal' => (float) $detail->subtotal,
                    'note' => $detail->note,
                ];
            })->values()->all(),
            'promo' => $order->promo ? [
                'id' => $order->promo->id,
                'code' => $order->promo->code,
                'name' => $order->promo->name,
                'type' => $order->promo->type,
                'value' => (float) $order->promo->value,
            ] : null,
            'payment' => $order->payment ? [
                'id' => $order->payment->id,
                'payment_method' => $order->payment->payment_method,
                'status_payment' => $order->payment->status_payment,
                'grass_amount' => (float) $order->payment->grass_amount,
                'snap_token' => $order->payment->snap_token,
                'payment_date' => $order->payment->payment_date,
            ] : null,
        ];
    }

    private function serializeReservation(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id,
            'customer_name' => $reservation->customer_name,
            'customer_phone' => $reservation->customer_phone,
            'table_number' => $reservation->table?->table_number,
            'seating_type' => $reservation->seating_type,
            'reservation_date' => $reservation->reservation_date,
            'reservation_time' => $reservation->reservation_time,
            'status' => $reservation->status,
        ];
    }
}
