<?php

use App\Models\Promo;

describe('Promo Validation API', function () {
    test('can validate active promo code', function () {
        $promo = Promo::create([
            'code' => 'DISCOUNT10',
            'name' => 'Diskon 10%',
            'type' => 'percentage',
            'value' => 10,
            'minimum_price' => 50000,
            'status_promo' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $response = $this->postJson('/api/promos/validate', [
            'code' => 'DISCOUNT10',
            'total_price' => 100000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Kode promo valid',
            ]);

        // Check discount calculation (10% of 100000 = 10000)
        expect($response->json('data.discount_amount'))->toEqual(10000);
        expect($response->json('data.final_price'))->toEqual(90000);
    });

    test('can validate fixed amount promo', function () {
        Promo::create([
            'code' => 'FLAT20K',
            'name' => 'Diskon 20rb',
            'type' => 'fixed_amount',
            'value' => 20000,
            'minimum_price' => 50000,
            'status_promo' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $response = $this->postJson('/api/promos/validate', [
            'code' => 'FLAT20K',
            'total_price' => 100000,
        ]);

        $response->assertStatus(200);
        expect($response->json('data.discount_amount'))->toEqual(20000);
        expect($response->json('data.final_price'))->toEqual(80000);
    });

    test('returns error for invalid promo code', function () {
        $response = $this->postJson('/api/promos/validate', [
            'code' => 'INVALIDCODE',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Kode promo tidak valid atau sudah kadaluarsa',
            ]);
    });

    test('returns error for expired promo', function () {
        Promo::create([
            'code' => 'EXPIRED',
            'name' => 'Expired Promo',
            'type' => 'percentage',
            'value' => 10,
            'minimum_price' => 0,
            'status_promo' => 'active',
            'valid_from' => now()->subDays(10),
            'valid_until' => now()->subDay(), // Expired yesterday
        ]);

        $response = $this->postJson('/api/promos/validate', [
            'code' => 'EXPIRED',
        ]);

        $response->assertStatus(404);
    });

    test('returns error for inactive promo', function () {
        Promo::create([
            'code' => 'INACTIVE',
            'name' => 'Inactive Promo',
            'type' => 'percentage',
            'value' => 10,
            'minimum_price' => 0,
            'status_promo' => 'inactive',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $response = $this->postJson('/api/promos/validate', [
            'code' => 'INACTIVE',
        ]);

        $response->assertStatus(404);
    });

    test('returns error when total price below minimum', function () {
        Promo::create([
            'code' => 'MINPRICE',
            'name' => 'Min Price Promo',
            'type' => 'percentage',
            'value' => 10,
            'minimum_price' => 100000,
            'status_promo' => 'active',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $response = $this->postJson('/api/promos/validate', [
            'code' => 'MINPRICE',
            'total_price' => 50000, // Below minimum
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });
});
