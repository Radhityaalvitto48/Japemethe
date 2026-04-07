<?php

use App\Models\Reservation;
use App\Models\Table;

function reservationTestTable(): Table
{
    return Table::create([
        'table_number' => 'L-' . rand(1, 10000),
        'seating_type' => 'lesehan',
        'is_active' => true,
        'qr_code' => 'qr-rsv-' . uniqid(),
    ]);
}

describe('Reservation API', function () {
    test('can create reservation', function () {
        $table = reservationTestTable();

        $response = $this->postJson('/api/reservations', [
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'seating_type' => 'lesehan',
            'id_table' => $table->id,
            'reservation_date' => now()->addDay()->format('Y-m-d'),
            'reservation_time' => '19:00',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Reservasi berhasil dibuat',
            ]);

        expect(Reservation::count())->toBe(1);
    });

    test('cannot create reservation with mismatched seating type', function () {
        $table = reservationTestTable();

        $response = $this->postJson('/api/reservations', [
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'seating_type' => 'chair', // Meja adalah lesehan
            'id_table' => $table->id,
            'reservation_date' => now()->addDay()->format('Y-m-d'),
            'reservation_time' => '19:00',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });

    test('cannot create duplicate reservation for same table and time', function () {
        $table = reservationTestTable();
        $reservationDate = now()->addDay()->format('Y-m-d');
        $reservationTime = '19:00';

        // Create first reservation
        Reservation::create([
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'seating_type' => 'lesehan',
            'id_table' => $table->id,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'status' => 'pending',
        ]);

        // Try to create duplicate
        $response = $this->postJson('/api/reservations', [
            'customer_name' => 'Jane Doe',
            'customer_phone' => '089876543210',
            'seating_type' => 'lesehan',
            'id_table' => $table->id,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Meja sudah direservasi pada waktu tersebut',
            ]);
    });

    test('can cancel reservation', function () {
        $table = reservationTestTable();

        $reservation = Reservation::create([
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'seating_type' => 'lesehan',
            'id_table' => $table->id,
            'reservation_date' => now()->addDay()->format('Y-m-d'),
            'reservation_time' => '19:00',
            'status' => 'pending',
        ]);

        $response = $this->putJson('/api/reservations/' . $reservation->id . '/cancel');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reservasi berhasil dibatalkan',
            ]);

        expect($reservation->fresh()->status)->toBe('cancelled');
    });

    test('can get reservation by id', function () {
        $table = reservationTestTable();

        $reservation = Reservation::create([
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'seating_type' => 'lesehan',
            'id_table' => $table->id,
            'reservation_date' => now()->addDay()->format('Y-m-d'),
            'reservation_time' => '19:00',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/reservations/' . $reservation->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    });

    test('cannot create reservation in the past', function () {
        $table = reservationTestTable();

        $response = $this->postJson('/api/reservations', [
            'customer_name' => 'John Doe',
            'customer_phone' => '081234567890',
            'seating_type' => 'lesehan',
            'id_table' => $table->id,
            'reservation_date' => now()->subDay()->format('Y-m-d'),
            'reservation_time' => '19:00',
        ]);

        $response->assertStatus(422);
    });
});
