<?php

namespace App\Filament\Resources\OrderDetails\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;

class OrderDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order Number'),
                TextColumn::make('order.table.table_number')
                    ->label('Meja'),
                TextColumn::make('order.orderDetails')
                    ->label('Menu')
                    ->formatStateUsing(fn($details) => collect($details)->map(fn($d) => $d['menu']['name'] ?? '-')->implode(', ')),
                TextColumn::make('order.orderDetails')
                    ->label('Qty')
                    ->formatStateUsing(fn($details) => collect($details)->map(fn($d) => $d['quantity'])->implode(', ')),
                TextColumn::make('order.orderDetails')
                    ->label('Subtotal')
                    ->formatStateUsing(fn($details) => collect($details)->map(fn($d) => 'Rp ' . number_format($d['subtotal'], 0, ',', '.'))->implode(', ')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
