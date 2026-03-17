<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Menu;
use App\Models\Payment;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Snap;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with(['table', 'orderDetails.menu', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            // Validasi promo code jika ada
            $promoId = null;
            $discountAmount = 0;

            if (!empty($validated['promo_code'])) {
                $promo = Promo::where('code', $validated['promo_code'])
                    ->where('status_promo', 'active')
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now())
                    ->first();

                if (!$promo) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode promo tidak valid atau sudah kadaluarsa'
                    ], 422);
                }

                $promoId = $promo->id;
            }

            // Hitung total items dan total price
            $totalItems = 0;
            $totalPrice = 0;

            foreach ($validated['items'] as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                $subtotal = $menu->price * $item['quantity'];
                $totalItems += $item['quantity'];
                $totalPrice += $subtotal;
            }

            // Hitung diskon jika ada promo
            if ($promoId) {
                $promo = Promo::find($promoId);
                if ($totalPrice >= $promo->minimum_price) {
                    if ($promo->type === 'percentage') {
                        $discountAmount = ($totalPrice * $promo->value) / 100;
                    } else {
                        $discountAmount = $promo->value;
                    }
                    $totalPrice -= $discountAmount;
                }
            }

            // Create order
            $order = Order::create([
                'table_id' => $validated['table_id'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'total_items' => $totalItems,
                'total_price' => max(0, $totalPrice),
                'status_order' => 'pending',
                'promo_id' => $promoId,
            ]);

            // Create order details
            $itemDetails = [];
            foreach ($validated['items'] as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                OrderDetail::create([
                    'order_id' => $order->id,
                    'menu_id' => $item['menu_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $menu->price,
                    'subtotal' => $menu->price * $item['quantity'],
                    'note' => $item['note'] ?? null,
                ]);

                $itemDetails[] = [
                    'id' => (string) $menu->id,
                    'price' => (int) round($menu->price),
                    'quantity' => $item['quantity'],
                    'name' => mb_substr($menu->name, 0, 50),
                ];
            }

            // Hitung gross amount dari item details
            $grossAmount = 0;
            foreach ($itemDetails as $itemDetail) {
                $grossAmount += $itemDetail['price'] * $itemDetail['quantity'];
            }

            // Jika ada diskon, tambahkan sebagai item negatif agar gross_amount match
            $orderTotal = (int) round(max(0, $totalPrice));
            if ($orderTotal < $grossAmount) {
                $discountValue = $grossAmount - $orderTotal;
                $itemDetails[] = [
                    'id' => 'DISCOUNT',
                    'price' => -$discountValue,
                    'quantity' => 1,
                    'name' => 'Diskon Promo',
                ];
                $grossAmount = $orderTotal;
            }

            // Generate Midtrans Snap token
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

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'midtrans_snap',
                'grass_amount' => $grossAmount,
                'status_payment' => 'pending',
                'snap_token' => $snapToken,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibuat',
                'data' => $order->load(['orderDetails.menu', 'table', 'payment']),
                'snap_token' => $snapToken,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource by order number.
     */
    public function showByNumber(string $orderNumber)
    {
        $order = Order::with(['table', 'orderDetails.menu', 'payment'])
            ->where('order_number', $orderNumber)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Get orders by multiple IDs (for session-based history).
     */
    public function getByIds(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|max:50',
            'ids.*' => 'integer',
        ]);

        $orders = Order::with(['table', 'orderDetails.menu', 'payment'])
            ->whereIn('id', $request->ids)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status_order' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        $order->update([
            'status_order' => $request->status_order
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diupdate',
            'data' => $order
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with(['table', 'orderDetails.menu', 'payment'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
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
