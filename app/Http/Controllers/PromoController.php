<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $promos = Promo::where('status_promo', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->get();

        return response()->json([
            'success' => true,
            'data' => $promos
        ]);
    }

    /**
     * Validate promo code.
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:30',
            'total_price' => 'nullable|numeric|min:0',
        ]);

        $promo = Promo::where('code', strtoupper($request->code))
            ->where('status_promo', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->first();

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak valid atau sudah kadaluarsa'
            ]);
        }

        // Cek minimum price jika total_price diberikan
        if ($request->total_price && $request->total_price < $promo->minimum_price) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum pembelian untuk promo ini adalah Rp ' . number_format($promo->minimum_price, 0, ',', '.')
            ]);
        }

        // Hitung diskon
        $discountAmount = 0;
        if ($request->total_price) {
            if ($promo->type === 'percentage') {
                $discountAmount = ($request->total_price * $promo->value) / 100;
            } else {
                $discountAmount = $promo->value;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode promo valid',
            'data' => [
                'promo' => $promo,
                'discount_amount' => $discountAmount,
                'final_price' => $request->total_price ? max(0, $request->total_price - $discountAmount) : null,
            ]
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $promo = Promo::find($id);

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $promo
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
