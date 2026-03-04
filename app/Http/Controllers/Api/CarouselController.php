<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\JsonResponse;

class CarouselController extends Controller
{
    /**
     * Ambil carousel aktif untuk promo section
     */
    public function active(): JsonResponse
    {
        $carousels = Carousel::active()
            ->latest()
            ->limit(3)
            ->get(['id', 'image'])
            ->map(function ($carousel) {
                return [
                    'id' => $carousel->id,
                    'image' => $carousel->image ? asset('storage/' . $carousel->image) : null,
                    'title' => 'Promo ' . $carousel->id
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $carousels
        ], 200, [
            'Cache-Control' => 'public, max-age=300', // Cache 5 menit
            'ETag' => md5(serialize($carousels))
        ]);
    }
}
