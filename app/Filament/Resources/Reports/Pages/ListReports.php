<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Filament\Resources\Reports\ReportsResource;
use App\Models\Menu;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ListReports extends ListRecords
{
    protected static string $resource = ReportsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateReport')
                ->label('Generate PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->form([
                    Select::make('report_type')
                        ->label('Jenis Laporan')
                        ->required()
                        ->options([
                            'financial' => 'Pemasukan',
                            'sales' => 'Menu Terjual',
                            'inventory' => 'Stok Menu',
                            'customer' => 'Pelanggan/Reservasi',
                        ]),
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false),
                    DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('start_date'),
                ])
                ->action(function (array $data) {
                    $startDate = Carbon::parse($data['start_date'])->startOfDay();
                    $endDate = Carbon::parse($data['end_date'])->endOfDay();

                    if ($endDate->lt($startDate)) {
                        Notification::make()
                            ->title('Tanggal selesai harus setelah tanggal mulai.')
                            ->danger()
                            ->send();

                        return null;
                    }

                    $reportPayload = $this->buildReportPayload($data['report_type'], $startDate, $endDate);

                    $pdf = Pdf::loadView('filament.reports.generated-pdf', [
                        'reportType' => $data['report_type'],
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'payload' => $reportPayload,
                        'generatedAt' => now(),
                        'generatedBy' => Auth::user()?->name ?? 'System',
                    ])->setPaper('a4', 'portrait');

                    $fileName = sprintf(
                        'report-%s-%s-%s.pdf',
                        $data['report_type'],
                        $startDate->format('Ymd'),
                        $endDate->format('Ymd'),
                    );

                    $filePath = 'reports/' . $fileName;
                    Storage::disk('local')->put($filePath, $pdf->output());

                    Report::query()->create([
                        'generated_by' => Auth::id(),
                        'report_type' => $data['report_type'],
                        'filter_criteria' => [
                            'start_date' => $startDate->toDateString(),
                            'end_date' => $endDate->toDateString(),
                        ],
                        'file_path' => $filePath,
                        'status_report' => 'completed',
                    ]);

                    Notification::make()
                        ->title('Report berhasil dibuat. Silakan klik tombol Download PDF pada histori laporan.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function buildReportPayload(string $reportType, Carbon $startDate, Carbon $endDate): array
    {
        return match ($reportType) {
            'financial' => $this->buildFinancialPayload($startDate, $endDate),
            'sales' => $this->buildSalesPayload($startDate, $endDate),
            'inventory' => $this->buildInventoryPayload(),
            'customer' => $this->buildCustomerPayload($startDate, $endDate),
            default => [],
        };
    }

    private function buildFinancialPayload(Carbon $startDate, Carbon $endDate): array
    {
        $payments = Payment::query()
            ->where('status_payment', 'completed')
            ->whereBetween(DB::raw('COALESCE(payment_date, created_at)'), [$startDate, $endDate]);

        $totalIncome = (float) $payments->sum('grass_amount');

        $paymentByMethod = Payment::query()
            ->where('status_payment', 'completed')
            ->whereBetween(DB::raw('COALESCE(payment_date, created_at)'), [$startDate, $endDate])
            ->selectRaw('payment_method, COUNT(*) as total_transactions, SUM(grass_amount) as total_amount')
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get();

        $dailyIncome = Payment::query()
            ->where('status_payment', 'completed')
            ->whereBetween(DB::raw('COALESCE(payment_date, created_at)'), [$startDate, $endDate])
            ->selectRaw('DATE(COALESCE(payment_date, created_at)) as day, SUM(grass_amount) as total_amount')
            ->groupByRaw('DATE(COALESCE(payment_date, created_at))')
            ->orderBy('day')
            ->get();

        return [
            'summary' => [
                'total_income' => $totalIncome,
                'total_transactions' => (int) $payments->count(),
            ],
            'payment_by_method' => $paymentByMethod,
            'daily_income' => $dailyIncome,
        ];
    }

    private function buildSalesPayload(Carbon $startDate, Carbon $endDate): array
    {
        $baseQuery = OrderDetail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereBetween(DB::raw('COALESCE(orders.ordered_at, orders.created_at)'), [$startDate, $endDate])
            ->where('orders.status_order', '!=', 'cancelled');

        $topMenus = (clone $baseQuery)
            ->join('menus', 'menus.id', '=', 'order_details.menu_id')
            ->selectRaw('menus.name as menu_name, SUM(order_details.quantity) as total_qty, SUM(order_details.subtotal) as total_amount')
            ->groupBy('menus.name')
            ->orderByDesc('total_qty')
            ->limit(20)
            ->get();

        $summaryData = (clone $baseQuery)
            ->selectRaw('SUM(order_details.quantity) as total_qty, SUM(order_details.subtotal) as gross_sales, COUNT(DISTINCT order_details.order_id) as total_orders')
            ->first();

        return [
            'summary' => [
                'total_qty' => (int) ($summaryData?->total_qty ?? 0),
                'gross_sales' => (float) ($summaryData?->gross_sales ?? 0),
                'total_orders' => (int) ($summaryData?->total_orders ?? 0),
            ],
            'top_menus' => $topMenus,
        ];
    }

    private function buildInventoryPayload(): array
    {
        $menus = Menu::query()
            ->select(['name', 'stock', 'status_menu'])
            ->orderBy('stock')
            ->limit(100)
            ->get();

        $lowStockCount = Menu::query()->where('stock', '<=', 10)->count();

        return [
            'summary' => [
                'total_menu' => (int) Menu::query()->count(),
                'low_stock_count' => (int) $lowStockCount,
            ],
            'items' => $menus,
        ];
    }

    private function buildCustomerPayload(Carbon $startDate, Carbon $endDate): array
    {
        $reservations = Reservation::query()
            ->whereBetween('reservation_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $bySeatingType = Reservation::query()
            ->whereBetween('reservation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('seating_type, COUNT(*) as total')
            ->groupBy('seating_type')
            ->orderByDesc('total')
            ->get();

        $byStatus = Reservation::query()
            ->whereBetween('reservation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return [
            'summary' => [
                'total_reservations' => (int) $reservations->count(),
            ],
            'by_seating_type' => $bySeatingType,
            'by_status' => $byStatus,
        ];
    }
}
