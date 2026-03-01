<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Table::where('is_active', true);

        // Filter by seating type
        if ($request->has('seating_type')) {
            $query->where('seating_type', $request->seating_type);
        }

        $tables = $query->orderBy('table_number')->get();

        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }

    /**
     * Display table by table number (untuk QR code scan).
     */
    public function showByNumber(string $tableNumber)
    {
        $table = Table::where('table_number', $tableNumber)
            ->where('is_active', true)
            ->first();

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Meja tidak ditemukan atau tidak aktif'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $table
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Meja tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $table
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
