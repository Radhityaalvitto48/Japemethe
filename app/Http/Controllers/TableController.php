<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\JsonResponse;

class TableController extends \App\Http\Controllers\Controller
{
    public function index(): JsonResponse
    {
        $tables = Table::query()
            ->orderBy('table_number')
            ->get()
            ->map(fn (Table $table) => $this->transformTable($table))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $tables,
        ]);
    }

    public function showByNumber(string $tableNumber): JsonResponse
    {
        $table = Table::query()
            ->where('table_number', $tableNumber)
            ->first();

        if (! $table) {
            return response()->json([
                'success' => false,
                'message' => 'Meja tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformTable($table),
        ]);
    }

    private function transformTable(Table $table): array
    {
        return [
            'id' => $table->id,
            'id_table' => $table->id,
            'table_number' => $table->table_number,
            'seating_type' => $table->seating_type,
            'qr_code' => $table->qr_code ? asset('storage/' . ltrim($table->qr_code, '/')) : null,
            'is_active' => (bool) $table->is_active,
        ];
    }
}
