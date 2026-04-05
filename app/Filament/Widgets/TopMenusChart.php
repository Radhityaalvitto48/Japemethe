<?php

namespace App\Filament\Widgets;

use App\Models\OrderDetail;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class TopMenusChart extends ChartWidget
{
    protected ?string $heading = 'Menu Paling Sering Dibeli';

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 3;

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

        return Cache::remember("admin:top-menus:{$filter}:v1", now()->addSeconds(60), function () use ($filter): array {
            $query = OrderDetail::query()
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('menus', 'menus.id', '=', 'order_details.menu_id')
                ->selectRaw('menus.name as menu_name, SUM(order_details.quantity) as total_qty')
                ->groupBy('menus.name')
                ->orderByDesc('total_qty')
                ->limit(8);

            if ($filter === '12m') {
                $query->whereBetween('orders.ordered_at', [now()->startOfMonth()->subMonths(11), now()->endOfMonth()]);
            } else {
                $days = $filter === '7d' ? 7 : 30;
                $query->whereBetween('orders.ordered_at', [now()->startOfDay()->subDays($days - 1), now()->endOfDay()]);
            }

            $rows = $query->get();

            return [
                'labels' => $rows->pluck('menu_name')->all(),
                'datasets' => [
                    [
                        'label' => 'Qty Terjual',
                        'data' => $rows->pluck('total_qty')->map(fn ($qty): int => (int) $qty)->all(),
                        'backgroundColor' => [
                            '#f59e0b', '#f97316', '#84cc16', '#22c55e',
                            '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1',
                        ],
                        'borderRadius' => 8,
                    ],
                ],
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
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
                            labels: { color: textColor },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { color: textColor },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor },
                            grid: { color: gridColor },
                        },
                    },
                }
            })()
        JS);
    }
}
