<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Promo;
use App\Models\Table;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Snap;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class OrderController extends Controller
{
	public function store(StoreOrderRequest $request): JsonResponse
	{
		$validated = $request->validated();

		$table = Table::query()
			->whereKey($validated['table_id'])
			->where('is_active', true)
			->first();

		if (! $table) {
			return response()->json([
				'success' => false,
				'message' => 'Meja tidak ditemukan atau tidak aktif.',
			], 422);
		}

		$menuIds = collect($validated['items'])
			->pluck('menu_id')
			->unique()
			->values();

		$menuMap = Menu::query()
			->whereIn('id', $menuIds)
			->get()
			->keyBy('id');

		if ($menuMap->count() !== $menuIds->count()) {
			return response()->json([
				'success' => false,
				'message' => 'Ada menu yang tidak ditemukan.',
			], 422);
		}

		$orderItems = [];
		$totalItems = 0;
		$baseTotal = 0.0;

		foreach ($validated['items'] as $item) {
			/** @var Menu|null $menu */
			$menu = $menuMap->get((int) $item['menu_id']);

			if (! $menu) {
				return response()->json([
					'success' => false,
					'message' => 'Menu tidak ditemukan.',
				], 422);
			}

			if ($menu->status_menu !== 'available') {
				return response()->json([
					'success' => false,
					'message' => "Menu {$menu->name} sedang tidak tersedia.",
				], 422);
			}

			if ((int) $menu->stock < (int) $item['quantity']) {
				return response()->json([
					'success' => false,
					'message' => "Stok menu {$menu->name} tidak mencukupi.",
				], 422);
			}

			$quantity = (int) $item['quantity'];
			$unitPrice = (float) $menu->price;
			$subtotal = $unitPrice * $quantity;

			$orderItems[] = [
				'menu_id' => (int) $menu->id,
				'name' => (string) $menu->name,
				'quantity' => $quantity,
				'unit_price' => $unitPrice,
				'subtotal' => $subtotal,
				'note' => $item['note'] ?? null,
			];

			$totalItems += $quantity;
			$baseTotal += $subtotal;
		}

		$promo = null;
		$discountAmount = 0.0;
		$finalTotal = $baseTotal;

		if (! empty($validated['promo_code'])) {
			$promo = $this->findValidPromo((string) $validated['promo_code']);

			if (! $promo) {
				return response()->json([
					'success' => false,
					'message' => 'Kode promo tidak valid atau tidak aktif.',
				], 422);
			}

			if ($baseTotal < (float) $promo->minimum_price) {
				return response()->json([
					'success' => false,
					'message' => sprintf(
						'Promo hanya berlaku untuk minimal transaksi Rp %s.',
						number_format((float) $promo->minimum_price, 0, ',', '.')
					),
				], 422);
			}

			$discountAmount = $this->calculatePromoDiscount($promo, $baseTotal);
			$finalTotal = max(0, $baseTotal - $discountAmount);
		}

		DB::beginTransaction();

		try {
			$order = Order::query()->create([
				'table_id' => (int) $validated['table_id'],
				'total_items' => $totalItems,
				'total_price' => $finalTotal,
				'status_order' => 'pending',
				'customer_email' => $validated['customer_email'] ?? null,
				'customer_phone' => $validated['customer_phone'] ?? null,
				'ordered_at' => now(),
				'promo_id' => $promo?->id,
			]);

			$itemDetails = [];
			$grossAmountFromItems = 0;

			foreach ($orderItems as $item) {
				OrderDetail::query()->create([
					'order_id' => $order->id,
					'menu_id' => $item['menu_id'],
					'quantity' => $item['quantity'],
					'unit_price' => $item['unit_price'],
					'subtotal' => $item['subtotal'],
					'note' => $item['note'],
				]);

				$price = (int) round((float) $item['unit_price']);

				$itemDetails[] = [
					'id' => (string) $item['menu_id'],
					'price' => $price,
					'quantity' => (int) $item['quantity'],
					'name' => Str::limit((string) $item['name'], 50, ''),
				];

				$grossAmountFromItems += $price * (int) $item['quantity'];
			}

			$grossAmount = (int) round($finalTotal);

			if ($grossAmount < $grossAmountFromItems) {
				$itemDetails[] = [
					'id' => 'DISCOUNT',
					'price' => -($grossAmountFromItems - $grossAmount),
					'quantity' => 1,
					'name' => 'Diskon Promo',
				];
			}

			if ($grossAmount <= 0) {
				$grossAmount = 1;
			}

			$midtransOrderId = 'ORDER-' . $order->id . '-' . time();

			$snapToken = Snap::getSnapToken([
				'transaction_details' => [
					'order_id' => $midtransOrderId,
					'gross_amount' => $grossAmount,
				],
				'item_details' => $itemDetails,
				'customer_details' => [
					'email' => $order->customer_email ?? 'guest@japemethe.com',
					'phone' => $order->customer_phone ?? '',
				],
			]);

			Payment::query()->create([
				'order_id' => $order->id,
				'payment_method' => 'midtrans_snap',
				'status_payment' => 'pending',
				'grass_amount' => $grossAmount,
				'snap_token' => $snapToken,
				'payment_date' => null,
			]);

			DB::commit();

			$order->load([
				'table:id,table_number',
				'orderDetails.menu:id,name',
				'payment',
			]);

			return response()->json([
				'success' => true,
				'message' => 'Order berhasil dibuat.',
				'data' => $this->withQrData($order),
				'snap_token' => $snapToken,
			], 201);
		} catch (Throwable $exception) {
			DB::rollBack();
			report($exception);

			return response()->json([
				'success' => false,
				'message' => 'Gagal membuat order.',
			], 500);
		}
	}

	public function getByIds(Request $request): JsonResponse
	{
		$validated = $request->validate([
			'ids' => ['required', 'array'],
			'ids.*' => ['integer'],
		]);

		$orders = Order::query()
			->with([
				'table:id,table_number',
				'orderDetails.menu:id,name',
				'payment',
			])
			->whereIn('id', $validated['ids'])
			->orderByDesc('created_at')
			->get()
			->map(fn (Order $order) => $this->withQrData($order))
			->values();

		return response()->json([
			'success' => true,
			'data' => $orders,
		]);
	}

	public function showByNumber(string $orderNumber): JsonResponse
	{
		$order = Order::query()
			->with([
				'table:id,table_number',
				'orderDetails.menu:id,name',
				'payment',
			])
			->where('order_number', $orderNumber)
			->first();

		if (! $order) {
			return response()->json([
				'success' => false,
				'message' => 'Order tidak ditemukan.',
			], 404);
		}

		return response()->json([
			'success' => true,
			'data' => $this->withQrData($order),
		]);
	}

	public function updateStatus(Request $request, int $id): JsonResponse
	{
		$validated = $request->validate([
			'status_order' => ['required', 'in:pending,in_progress,completed,cancelled'],
		]);

		$order = Order::query()->find($id);

		if (! $order) {
			return response()->json([
				'success' => false,
				'message' => 'Order tidak ditemukan.',
			], 404);
		}

		$order->update([
			'status_order' => $validated['status_order'],
		]);

		return response()->json([
			'success' => true,
			'message' => 'Status order berhasil diperbarui.',
			'data' => $order->fresh(),
		]);
	}

	private function findValidPromo(string $code): ?Promo
	{
		return Promo::query()
			->whereRaw('UPPER(code) = ?', [Str::upper(trim($code))])
			->where('status_promo', 'active')
			->whereDate('valid_from', '<=', now()->toDateString())
			->whereDate('valid_until', '>=', now()->toDateString())
			->first();
	}

	private function calculatePromoDiscount(Promo $promo, float $baseTotal): float
	{
		$discount = $promo->type === 'percentage'
			? ($baseTotal * ((float) $promo->value / 100))
			: (float) $promo->value;

		return (float) min($baseTotal, $discount);
	}

	private function withQrData(Order $order): array
	{
		$data = $order->toArray();

		if (($order->status_order ?? null) !== 'pending') {
			$data['qr_payload'] = null;
			$data['qr_code_data_uri'] = null;

			return $data;
		}

		$qrData = $this->buildQrData($order);
		$data['qr_payload'] = $qrData['payload'];
		$data['qr_code_data_uri'] = $qrData['data_uri'];

		return $data;
	}

	private function buildQrData(Order $order): array
	{
		$order->loadMissing('orderDetails.menu:id,name');

		$payloadData = [
			'order_number' => (string) $order->order_number,
			'total_items' => (int) $order->total_items,
			'total_price' => (float) $order->total_price,
			'items' => $order->orderDetails->map(function (OrderDetail $detail): array {
				return [
					'menu_id' => (int) $detail->menu_id,
					'name' => Str::limit((string) ($detail->menu?->name ?? 'Menu'), 40, ''),
					'qty' => (int) $detail->quantity,
					'subtotal' => (float) $detail->subtotal,
				];
			})->values()->all(),
		];

		$payload = Crypt::encryptString(json_encode($payloadData, JSON_UNESCAPED_UNICODE));
		$svg = QrCode::format('svg')
			->size(220)
			->margin(1)
			->generate($payload);

		return [
			'payload' => $payload,
			'data_uri' => 'data:image/svg+xml;base64,' . base64_encode($svg),
		];
	}
}
