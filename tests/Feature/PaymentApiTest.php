<?php

use App\Models\Payment;
use App\Models\Order;
use App\Models\Table;

function paymentTestTable(): Table
{
    return Table::create([
        'table_number' => 'P-' . rand(1, 1000),
        'seating_type' => 'chair',
        'is_active' => true,
        'qr_code' => 'qr-pay-' . uniqid(),
    ]);
}

function paymentTestOrder(): Order
{
    $table = paymentTestTable();
    return Order::create([
        'table_id' => $table->id,
        'total_items' => 1,
        'total_price' => 50000,
        'status_order' => 'pending',
    ]);
}

describe('Payment API', function () {
    test('can create payment for order', function () {
        $order = paymentTestOrder();

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'grass_amount' => 50000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Pembayaran tunai berhasil diselesaikan.',
            ]);

        expect(Payment::count())->toBe(1);
    });

    test('duplicate payment request is idempotent for same order', function () {
        $order = paymentTestOrder();

        $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'grass_amount' => 50000,
        ])->assertStatus(200);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'payment_method' => 'cash',
            'grass_amount' => 50000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        expect(Payment::where('order_id', $order->id)->count())->toBe(1);
    });

    test('can get payment by order id', function () {
        $order = paymentTestOrder();

        Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'bank_transfer',
            'grass_amount' => 50000,
            'status_payment' => 'pending',
            'snap_token' => '',
        ]);

        $response = $this->getJson('/api/payments/order/' . $order->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    test('can update payment status', function () {
        $order = paymentTestOrder();

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'bank_transfer',
            'grass_amount' => 50000,
            'status_payment' => 'pending',
            'snap_token' => '',
        ]);

        $response = $this->putJson('/api/payments/' . $payment->id . '/status', [
            'status_payment' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        expect($payment->fresh()->status_payment)->toBe('completed');
        expect($order->fresh()->status_order)->toBe('completed');
    });

    test('cannot create payment with invalid order', function () {
        $response = $this->postJson('/api/payments', [
            'order_id' => 999,
            'payment_method' => 'bank_transfer',
            'grass_amount' => 50000,
        ]);

        $response->assertStatus(422);
    });
});
