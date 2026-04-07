<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = Cache::remember('admin:stats-overview:v1', now()->addSeconds(60), function (): array {
            $now = now();
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();
            $startOfPrevMonth = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $endOfPrevMonth = $now->copy()->subMonthNoOverflow()->endOfMonth();

            $topMenu = OrderDetail::query()
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('menus', 'menus.id', '=', 'order_details.menu_id')
                ->whereBetween('orders.ordered_at', [$startOfMonth, $endOfMonth])
                ->selectRaw('menus.name as menu_name, SUM(order_details.quantity) as total_qty')
                ->groupBy('order_details.menu_id', 'menus.name')
                ->orderByDesc('total_qty')
                ->limit(1)
                ->first();

            return [
                'revenue_current' => (float) Payment::query()
                    ->where('status_payment', 'completed')
                    ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                    ->sum('grass_amount'),
                'revenue_previous' => (float) Payment::query()
                    ->where('status_payment', 'completed')
                    ->whereBetween('payment_date', [$startOfPrevMonth, $endOfPrevMonth])
                    ->sum('grass_amount'),
                'sales_current' => (int) Payment::query()
                    ->where('status_payment', 'completed')
                    ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                    ->count(),
                'orders_current' => (int) Order::query()
                    ->whereBetween('ordered_at', [$startOfMonth, $endOfMonth])
                    ->count(),
                'reservations_current' => (int) Reservation::query()
                    ->whereBetween('reservation_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                    ->count(),
                'top_menu_name' => $topMenu?->menu_name,
                'top_menu_qty' => (int) ($topMenu?->total_qty ?? 0),
            ];
        });

        $revenueCurrent = (float) ($stats['revenue_current'] ?? 0);
        $revenuePrevious = (float) ($stats['revenue_previous'] ?? 0);
        $salesCurrent = (int) ($stats['sales_current'] ?? 0);
        $ordersCurrent = (int) ($stats['orders_current'] ?? 0);
        $reservationsCurrent = (int) ($stats['reservations_current'] ?? 0);
        $topMenuName = (string) ($stats['top_menu_name'] ?? '-');
        $topMenuQty = (int) ($stats['top_menu_qty'] ?? 0);

        return [
            Stat::make('Pendapatan', 'Rp ' . number_format($revenueCurrent, 0, ',', '.'))
                ->description($this->buildDeltaDescription($revenueCurrent, $revenuePrevious))
                ->descriptionIcon($revenueCurrent >= $revenuePrevious ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueCurrent >= $revenuePrevious ? 'success' : 'danger'),

            Stat::make('Penjualan', number_format($salesCurrent))
                ->description('transaksi selesai')
                ->color('success'),

            Stat::make('Order', number_format($ordersCurrent))
                ->description('bulan ini')
                ->color('primary'),

            Stat::make('Reservasi', number_format($reservationsCurrent))
                ->description('bulan ini')
                ->color('info'),
        ];
    }

    private function buildDeltaDescription(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? 'Naik dari 0 pada periode sebelumnya' : 'Belum ada data periode sebelumnya';
        }

        $deltaPercent = (($current - $previous) / $previous) * 100;

        return sprintf('%s %.1f%% vs bulan lalu', $deltaPercent >= 0 ? 'Naik' : 'Turun', abs($deltaPercent));
    }
}
