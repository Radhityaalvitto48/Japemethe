<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
                ->columns([
                    TextColumn::make('ordered_at')
                        ->label('Tanggal Order')
                        ->dateTime('d/m/Y H:i')
                        ->sortable(),
                    TextColumn::make('order_number')
                        ->label('Order Number')
                        ->searchable(),
                    TextColumn::make('table.table_number')
                        ->label('Table')
                        ->sortable(),
                    TextColumn::make('status_order')
                        ->label('Status')
                        ->badge(),
                    TextColumn::make('total_items')
                        ->label('Items'),
                    TextColumn::make('total_price')
                        ->label('Total Price')
                        ->money('IDR'),
                ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalHeading(fn (Order $record): string => "Detail Pesanan {$record->order_number}")
                    ->modalContent(fn (Order $record): HtmlString => new HtmlString(self::buildDetailModalHtml($record))),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function buildDetailModalHtml(Order $record): string
    {
        if ($record->orderDetails->isEmpty()) {
            return '<p>Tidak ada detail menu pada pesanan ini.</p>';
        }

        $rows = $record->orderDetails->map(function ($detail): string {
            $menuName = e($detail->menu?->name ?? 'Menu tidak ditemukan');
            $qty = (int) $detail->quantity;
            $unitPrice = self::formatRupiah((float) $detail->unit_price);
            $subtotal = self::formatRupiah((float) $detail->subtotal);

            return "<tr>\n"
                . "<td style='padding:6px 8px; border-bottom:1px solid #e5e7eb;'>{$menuName}</td>\n"
                . "<td style='padding:6px 8px; border-bottom:1px solid #e5e7eb; text-align:center;'>{$qty}</td>\n"
                . "<td style='padding:6px 8px; border-bottom:1px solid #e5e7eb; text-align:right;'>{$unitPrice}</td>\n"
                . "<td style='padding:6px 8px; border-bottom:1px solid #e5e7eb; text-align:right;'>{$subtotal}</td>\n"
                . "</tr>";
        })->implode('');

        $total = self::formatRupiah((float) $record->total_price);

        return "
            <div style='overflow:auto;'>
                <table style='width:100%; border-collapse:collapse; font-size:13px;'>
                    <thead>
                        <tr>
                            <th style='text-align:left; padding:6px 8px; border-bottom:1px solid #d1d5db;'>Menu</th>
                            <th style='text-align:center; padding:6px 8px; border-bottom:1px solid #d1d5db;'>Qty</th>
                            <th style='text-align:right; padding:6px 8px; border-bottom:1px solid #d1d5db;'>Harga</th>
                            <th style='text-align:right; padding:6px 8px; border-bottom:1px solid #d1d5db;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
                <p style='margin-top:10px; text-align:right; font-weight:600;'>Total: {$total}</p>
            </div>
        ";
    }

    private static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
