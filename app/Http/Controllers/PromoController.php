<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromoController extends Controller
{
	public function validateCode(Request $request): JsonResponse
	{
		$validated = $request->validate([
			'code' => ['required', 'string', 'max:30'],
			'total_price' => ['required', 'numeric', 'min:0'],
		]);

		$promo = Promo::query()
			->whereRaw('UPPER(code) = ?', [Str::upper(trim($validated['code']))])
			->where('status_promo', 'active')
			->whereDate('valid_from', '<=', now()->toDateString())
			->whereDate('valid_until', '>=', now()->toDateString())
			->first();

		if (! $promo instanceof Promo) {
			return response()->json([
				'success' => false,
				'message' => 'Kode promo tidak valid atau sudah kadaluarsa.',
			], 422);
		}

		$totalPrice = (float) $validated['total_price'];
		if ($totalPrice < (float) $promo->minimum_price) {
			return response()->json([
				'success' => false,
				'message' => sprintf(
					'Minimum pembelian untuk promo ini adalah Rp %s.',
					number_format((float) $promo->minimum_price, 0, ',', '.')
				),
			], 422);
		}

		$discount = $this->calculateDiscount($promo, $totalPrice);

		return response()->json([
			'success' => true,
			'message' => 'Kode promo valid.',
			'data' => [
				'id' => $promo->id,
				'code' => $promo->code,
				'name' => $promo->name,
				'type' => $promo->type,
				'value' => (float) $promo->value,
				'minimum_price' => (float) $promo->minimum_price,
				'discount_amount' => $discount,
				'final_price' => max(0, $totalPrice - $discount),
			],
		]);
	}

	private function calculateDiscount(Promo $promo, float $totalPrice): float
	{
		$discount = $promo->type === 'percentage'
			? ($totalPrice * ((float) $promo->value / 100))
			: (float) $promo->value;

		return (float) min($discount, $totalPrice);
	}
}
