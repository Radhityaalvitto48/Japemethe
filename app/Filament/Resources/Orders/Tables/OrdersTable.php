<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
