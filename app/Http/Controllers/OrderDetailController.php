<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderDetailController extends \App\Http\Controllers\Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'menu_id' => ['required', 'exists:menus,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $menu = Menu::query()->find($validated['menu_id']);
        if (! $menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu tidak ditemukan.',
            ], 404);
        }

        $unitPrice = isset($validated['unit_price'])
            ? (float) $validated['unit_price']
            : (float) $menu->price;

        $detail = OrderDetail::query()->create([
            'order_id' => (int) $validated['order_id'],
            'menu_id' => (int) $validated['menu_id'],
            'quantity' => (int) $validated['quantity'],
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * (int) $validated['quantity'],
            'note' => $validated['note'] ?? null,
        ]);

        $order = Order::query()->find((int) $validated['order_id']);
        if ($order) {
            $order->update([
                'total_items' => (int) $order->orderDetails()->sum('quantity'),
                'total_price' => (float) $order->orderDetails()->sum('subtotal'),
            ]);
        }

        $detail->load('menu:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Detail order berhasil ditambahkan.',
            'data' => $detail,
        ], 201);
    }

    public function getByOrder(int $orderId): JsonResponse
    {
        $details = OrderDetail::query()
            ->with('menu:id,name')
            ->where('order_id', $orderId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $details,
        ]);
    }
}
