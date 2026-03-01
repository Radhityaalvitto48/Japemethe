<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use App\Models\Order;
use App\Models\Menu;
use Illuminate\Http\Request;

class OrderDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orderDetails = OrderDetail::with(['order', 'menu'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orderDetails
        ]);
    }

    /**
     * Store a newly created resource in storage (menambah item ke order yang sudah ada).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'menu_id' => 'required|exists:menus,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $menu = Menu::findOrFail($validated['menu_id']);
            $subtotal = $menu->price * $validated['quantity'];

            $orderDetail = OrderDetail::create([
                'order_id' => $validated['order_id'],
                'menu_id' => $validated['menu_id'],
                'quantity' => $validated['quantity'],
                'unit_price' => $menu->price,
                'subtotal' => $subtotal,
                'note' => $validated['note'] ?? null,
            ]);

            // Update total items dan total price di order
            $order = Order::find($validated['order_id']);
            $order->total_items += $validated['quantity'];
            $order->total_price += $subtotal;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan',
                'data' => $orderDetail->load('menu')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order details by order ID.
     */
    public function getByOrder(string $orderId)
    {
        $orderDetails = OrderDetail::with(['menu'])
            ->where('order_id', $orderId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orderDetails
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $orderDetail = OrderDetail::with(['order', 'menu'])->find($id);

        if (!$orderDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Order detail tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $orderDetail
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
