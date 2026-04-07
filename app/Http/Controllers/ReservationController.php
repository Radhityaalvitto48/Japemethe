<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Http\JsonResponse;

class ReservationController extends \App\Http\Controllers\Controller
{
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $seatingType = $validated['seating_type'] === 'kursi'
            ? 'chair'
            : $validated['seating_type'];

        $table = Table::query()
            ->whereKey($validated['id_table'])
            ->where('is_active', true)
            ->first();

        if (! $table) {
            return response()->json([
                'success' => false,
                'message' => 'Meja tidak tersedia.',
            ], 422);
        }

        if ($table->seating_type !== $seatingType) {
            return response()->json([
                'success' => false,
                'message' => 'Tipe meja tidak sesuai dengan pilihan tempat duduk.',
            ], 422);
        }

        $reservation = Reservation::query()->create([
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'id_table' => (int) $validated['id_table'],
            'seating_type' => $seatingType,
            'reservation_date' => $validated['reservation_date'],
            'reservation_time' => $validated['reservation_time'],
            'status' => 'pending',
        ]);

        $reservation->load('table:id,table_number,seating_type');

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dibuat.',
            'data' => $reservation,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $reservation = Reservation::query()
            ->with('table:id,table_number,seating_type')
            ->find($id);

        if (! $reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $reservation = Reservation::query()->find($id);
        if (! $reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan.',
            ], 404);
        }

        if (in_array($reservation->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak dapat dibatalkan.',
            ], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dibatalkan.',
            'data' => $reservation->fresh(),
        ]);
    }
}
