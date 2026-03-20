<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\JsonResponse;

class CarouselController extends Controller
{
    public function active(): JsonResponse
    {
        $carousels = Carousel::query()
            ->active()
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (Carousel $carousel) {
                $image = $carousel->image;

                if ($image && ! str_starts_with($image, 'http://') && ! str_starts_with($image, 'https://')) {
                    $image = asset('storage/' . ltrim($image, '/'));
                }

                return [
                    'id' => $carousel->id,
                    'image' => $image,
                    'title' => null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $carousels,
        ])->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=60');
    }
}
