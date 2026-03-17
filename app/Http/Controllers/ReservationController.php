<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reservations = Reservation::with(['table'])
            ->orderBy('reservation_date', 'desc')
            ->orderBy('reservation_time', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            $validated = $request->validated();

            // Cek apakah meja sudah direservasi pada waktu yang sama
            $existingReservation = Reservation::where('id_table', $validated['id_table'])
                ->where('reservation_date', $validated['reservation_date'])
                ->where('reservation_time', $validated['reservation_time'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meja sudah direservasi pada waktu tersebut'
                ], 422);
            }

            // Map seating type: frontend sends 'kursi', DB stores 'chair'
            $dbSeatingType = $validated['seating_type'] === 'kursi' ? 'chair' : $validated['seating_type'];

            // Cek apakah meja sesuai dengan seating type
            $table = Table::find($validated['id_table']);
            if ($table->seating_type !== $dbSeatingType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipe tempat duduk tidak sesuai dengan meja yang dipilih'
                ], 422);
            }

            $reservation = Reservation::create([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'id_table' => $validated['id_table'],
                'seating_type' => $dbSeatingType,
                'reservation_date' => $validated['reservation_date'],
                'reservation_time' => $validated['reservation_time'],
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reservasi berhasil dibuat',
                'data' => $reservation->load('table')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat reservasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reservation = Reservation::with(['table'])->find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reservation
        ]);
    }

    /**
     * Cancel reservation.
     */
    public function cancel(string $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi tidak ditemukan'
            ], 404);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Reservasi sudah dibatalkan'
            ], 422);
        }

        $reservation->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservasi berhasil dibatalkan',
            'data' => $reservation
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
