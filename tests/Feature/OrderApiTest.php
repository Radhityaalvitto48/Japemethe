<?php

use App\Models\Order;
use App\Models\Menu;
use App\Models\Table;
use App\Models\Promo;
use App\Models\MenuCategory;
use App\Models\OrderDetail;

function orderTestTable(): Table
{
    return Table::create([
        'table_number' => 'T-' . rand(1, 1000),
        'seating_type' => 'chair',
        'is_active' => true,
        'qr_code' => 'qr-' . uniqid(),
    ]);
}

function orderTestMenu(): Menu
{
    $category = MenuCategory::withoutEvents(function () {
        return MenuCategory::firstOrCreate(
            ['name' => 'Test Category'],
            ['image' => 'test-category.webp']
        );
    });
    $slug = 'test-menu-' . uniqid();
    return Menu::create([
        'name' => 'Test Menu ' . uniqid(),
        'slug' => $slug,
        'price' => 25000,
        'status_menu' => 'available',
        'menu_category_id' => $category->id,
    ]);
}

describe('Order API', function () {
    test('can create order with items', function () {
        /** @var \Tests\TestCase $this */
        $table = orderTestTable();
        $menu = orderTestMenu();

        $response = $this->postJson('/api/orders', [
            'table_id' => $table->id,
            'customer_email' => 'test@example.com',
            'customer_phone' => '081234567890',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 2,
                    'note' => 'Extra spicy',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order berhasil dibuat',
            ]);

        expect(Order::count())->toBe(1);
        expect(OrderDetail::count())->toBe(1);
    });

    test('cannot create order without items', function () {
        /** @var \Tests\TestCase $this */
        $table = orderTestTable();

        $response = $this->postJson('/api/orders', [
            'table_id' => $table->id,
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });

    test('cannot create order with invalid table', function () {
        /** @var \Tests\TestCase $this */
        $menu = orderTestMenu();

        $response = $this->postJson('/api/orders', [
            'table_id' => 999,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
    });

    test('can get order by order number', function () {
        /** @var \Tests\TestCase $this */
        $table = orderTestTable();

        $order = Order::create([
            'table_id' => $table->id,
            'total_items' => 1,
            'total_price' => 25000,
            'status_order' => 'pending',
        ]);

        $response = $this->getJson('/api/orders/' . $order->order_number);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    test('can update order status', function () {
        /** @var \Tests\TestCase $this */
        $table = orderTestTable();

        $order = Order::create([
            'table_id' => $table->id,
            'total_items' => 1,
            'total_price' => 25000,
            'status_order' => 'pending',
        ]);

        $response = $this->putJson('/api/orders/' . $order->id . '/status', [
            'status_order' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        expect($order->fresh()->status_order)->toBe('in_progress');
    });
});

describe('Order with Promo', function () {
    test('can apply valid promo code', function () {
        /** @var \Tests\TestCase $this */
        $table = orderTestTable();
        $menu = orderTestMenu();

        $promo = Promo::create([
            'code' => 'TESTPROMO',
            'name' => 'Test Promo',
            'type' => 'percentage',
            'value' => 10,
            'minimum_price' => 0,
            'status_promo' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $response = $this->postJson('/api/orders', [
            'table_id' => $table->id,
            'promo_code' => 'TESTPROMO',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $order = Order::first();
        expect($order->promo_id)->toBe($promo->id);
        // 25000 * 2 = 50000, 10% off = 45000
        expect((float) $order->total_price)->toEqual(45000.0);
    });

    test('cannot apply invalid promo code', function () {
        /** @var \Tests\TestCase $this */
        $table = orderTestTable();
        $menu = orderTestMenu();

        $response = $this->postJson('/api/orders', [
            'table_id' => $table->id,
            'promo_code' => 'INVALID',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Kode promo tidak valid atau sudah kadaluarsa',
            ]);
    });
});
