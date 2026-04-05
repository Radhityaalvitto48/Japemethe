<?php

namespace App\Filament\Resources\OrderDetails\Tables;

use App\Models\OrderDetail;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OrderDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order Number'),
                TextColumn::make('order.table.table_number')
                    ->label('Meja'),
                TextColumn::make('order_menu_summary')
                    ->label('Menu Dipesan')
                    ->getStateUsing(function (OrderDetail $record): string {
                        $details = $record->order?->orderDetails;

                        if (! $details || $details->isEmpty()) {
                            return '-';
                        }

                        return $details
                            ->map(function (OrderDetail $detail): string {
                                $menuName = $detail->menu?->name ?? 'Menu tidak ditemukan';
                                $qty = (int) $detail->quantity;

                                return e("{$menuName} x{$qty}");
                            })
                            ->implode('<br>');
                    })
                    ->html()
                    ->wrap(),
                TextColumn::make('menu.name')
                    ->label('Menu Item')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(),
                TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->money('IDR'),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
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
                    ->modalHeading(fn (OrderDetail $record): string => 'Detail Pesanan ' . ($record->order?->order_number ?? '-'))
                    ->modalContent(fn (OrderDetail $record): HtmlString => new HtmlString(self::buildDetailModalHtml($record))),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function buildDetailModalHtml(OrderDetail $record): string
    {
        $order = $record->order;
        $details = $order?->orderDetails ?? collect();

        if ($details->isEmpty()) {
            return '<p>Tidak ada detail menu pada pesanan ini.</p>';
        }

        $rows = $details->map(function (OrderDetail $detail): string {
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

        $total = self::formatRupiah((float) ($order?->total_price ?? 0));
        $tableNumber = e((string) ($order?->table?->table_number ?? '-'));

        return "
            <div style='overflow:auto;'>
                <p style='margin-bottom:10px;'>Meja: <strong>{$tableNumber}</strong></p>
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
