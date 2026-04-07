<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class AdminTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Pendapatan, Order, Reservasi';

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = '1/2';

    protected function getFilters(): ?array
    {
        return [
            '7d' => '7 hari',
            '30d' => '30 hari',
            '12m' => '12 bulan',
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? '30d';

        return Cache::remember("admin:trend-chart:{$filter}:v1", now()->addSeconds(60), function () use ($filter): array {
            if ($filter === '12m') {
                return $this->buildMonthlyData();
            }

            $days = $filter === '7d' ? 7 : 30;
            return $this->buildDailyData($days);
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array | RawJs | null
    {
        return RawJs::make(<<<'JS'
            (() => {
                const styles = getComputedStyle(document.documentElement)
                const textColor = styles.getPropertyValue('--gray-500').trim() || '#6b7280'
                const gridColor = styles.getPropertyValue('--gray-200').trim() || '#e5e7eb'

                return {
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor },
                            grid: { color: gridColor },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor },
                            grid: { color: gridColor },
                        },
                    },
                    elements: {
                        line: { tension: 0.3 },
                        point: { radius: 2 },
                    },
                }
            })()
        JS);
    }

    private function buildDailyData(int $days): array
    {
        $start = now()->startOfDay()->subDays($days - 1);
        $end = now()->endOfDay();

        $paymentRows = Payment::query()
            ->selectRaw('DATE(payment_date) as period, SUM(grass_amount) as total')
            ->where('status_payment', 'completed')
            ->whereBetween('payment_date', [$start, $end])
            ->groupBy('period')
            ->get()
            ->keyBy('period')
            ->map(fn ($row) => $row->total);

        $orderRows = Order::query()
            ->selectRaw('DATE(ordered_at) as period, COUNT(*) as total')
            ->whereBetween('ordered_at', [$start, $end])
            ->groupBy('period')
            ->get()
            ->keyBy('period')
            ->map(fn ($row) => $row->total);

        $reservationRows = Reservation::query()
            ->selectRaw('DATE(reservation_date) as period, COUNT(*) as total')
            ->whereBetween('reservation_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('period')
            ->get()
            ->keyBy('period')
            ->map(fn ($row) => $row->total);

        $labels = [];
        $revenues = [];
        $orders = [];
        $reservations = [];

        // Use immutable period generator untuk avoid memory leak dari Carbon loop
        $period = $start->copy();
        while ($period->lte($end)) {
            $key = $period->toDateString();
            $labels[] = $period->format('d M');
            $revenues[] = (float) ($paymentRows[$key] ?? 0);
            $orders[] = (int) ($orderRows[$key] ?? 0);
            $reservations[] = (int) ($reservationRows[$key] ?? 0);
            $period = $period->addDay();
        }
        unset($period);

        return $this->toDataset($labels, $revenues, $orders, $reservations);
    }

    private function buildMonthlyData(): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $end = now()->endOfMonth();

        $paymentRows = Payment::query()
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as period, SUM(grass_amount) as total")
            ->where('status_payment', 'completed')
            ->whereBetween('payment_date', [$start, $end])
            ->groupBy('period')
            ->get()
            ->keyBy('period')
            ->map(fn ($row) => $row->total);

        $orderRows = Order::query()
            ->selectRaw("DATE_FORMAT(ordered_at, '%Y-%m') as period, COUNT(*) as total")
            ->whereBetween('ordered_at', [$start, $end])
            ->groupBy('period')
            ->get()
            ->keyBy('period')
            ->map(fn ($row) => $row->total);

        $reservationRows = Reservation::query()
            ->selectRaw("DATE_FORMAT(reservation_date, '%Y-%m') as period, COUNT(*) as total")
            ->whereBetween('reservation_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('period')
            ->get()
            ->keyBy('period')
            ->map(fn ($row) => $row->total);

        $labels = [];
        $revenues = [];
        $orders = [];
        $reservations = [];

        // Use while loop untuk avoid memory leak dari addMonth() dalam for loop
        $period = $start->copy();
        $count = 0;
        while ($period->lte($end) && $count < 12) {
            $key = $period->format('Y-m');
            $labels[] = $period->translatedFormat('M Y');
            $revenues[] = (float) ($paymentRows[$key] ?? 0);
            $orders[] = (int) ($orderRows[$key] ?? 0);
            $reservations[] = (int) ($reservationRows[$key] ?? 0);
            $period = $period->addMonth();
            $count++;
        }
        unset($period);

        return $this->toDataset($labels, $revenues, $orders, $reservations);
    }

    private function toDataset(array $labels, array $revenues, array $orders, array $reservations): array
    {
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $revenues,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                ],
                [
                    'label' => 'Order',
                    'data' => $orders,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                ],
                [
                    'label' => 'Reservasi',
                    'data' => $reservations,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                ],
            ],
        ];
    }
}
