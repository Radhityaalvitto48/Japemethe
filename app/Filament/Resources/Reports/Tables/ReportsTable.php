<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Models\Report;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('generatedBy.name')
                    ->label('Dibuat Oleh')
                    ->searchable(),
                TextColumn::make('report_type')
                    ->label('Jenis Laporan')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'financial' => 'Pemasukan',
                        'sales' => 'Menu Terjual',
                        'inventory' => 'Stok Menu',
                        'customer' => 'Pelanggan/Reservasi',
                        default => ucfirst($state),
                    })
                    ->badge(),
                TextColumn::make('period_display')
                    ->label('Periode')
                    ->getStateUsing(function (Report $record): string {
                        $state = $record->filter_criteria;

                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $state = is_array($decoded) ? $decoded : [];
                        }

                        if (! is_array($state)) {
                            $state = [];
                        }

                        $start = $state['start_date'] ?? null;
                        $end = $state['end_date'] ?? null;

                        if ($start && $end) {
                            return "{$start} s/d {$end}";
                        }

                        $fileName = basename((string) ($record->file_path ?? ''));

                        if (preg_match('/report-[^-]+-(\d{8})-(\d{8})\.pdf$/i', $fileName, $matches) === 1) {
                            $startDate = substr($matches[1], 0, 4) . '-' . substr($matches[1], 4, 2) . '-' . substr($matches[1], 6, 2);
                            $endDate = substr($matches[2], 0, 4) . '-' . substr($matches[2], 4, 2) . '-' . substr($matches[2], 6, 2);

                            return "{$startDate} s/d {$endDate}";
                        }

                        return '-';
                    }),
                BadgeColumn::make('status_report')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (Report $record): bool => $record->status_report === 'completed' && Storage::disk('local')->exists($record->file_path))
                    ->action(function (Report $record) {
                        $fullPath = Storage::disk('local')->path($record->file_path);

                        return response()->download($fullPath, basename($record->file_path));
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
