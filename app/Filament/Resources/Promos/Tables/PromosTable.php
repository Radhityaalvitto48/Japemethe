<?php

namespace App\Filament\Resources\Promos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PromosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode Promo')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama Promo')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipe Diskon')->sortable(),
                TextColumn::make('value')->label('Nilai Diskon')->sortable(),
                TextColumn::make('minimum_price')->label('Harga Minimum')->sortable(),
                TextColumn::make('status_promo')
                    ->label('Status Promo')
                    ->formatStateUsing(fn($state) => $state === 'active' ? 'Aktif' : 'Tidak Aktif')
                    ->sortable(),
                ToggleColumn::make('status_promo')
                    ->label('Status Aktif')
                    ->getStateUsing(fn ($record) => $record->status_promo === 'active')
                    ->updateStateUsing(function ($record, $state) {
                        $record->update([
                            'status_promo' => $state ? 'active' : 'inactive',
                        ]);
                    })
                    ->sortable(),
                TextColumn::make('valid_from')->label('Berlaku Mulai')->dateTime()->sortable(),
                TextColumn::make('valid_until')->label('Berlaku Sampai')->dateTime()->sortable(),
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
