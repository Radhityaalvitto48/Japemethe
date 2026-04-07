<?php

namespace App\Filament\Resources\Reservations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')->label('Nama Pelanggan')->searchable()->sortable(),
                TextColumn::make('customer_phone')->label('Nomor Telepon')->searchable()->sortable(),
                TextColumn::make('table.table_number')->label('Nomor Meja')->sortable(),
                TextColumn::make('seating_type')->label('Tipe Tempat Duduk')->sortable(),
                TextColumn::make('reservation_date')->label('Tanggal Reservasi')->date()->sortable(),
                TextColumn::make('reservation_time')->label('Waktu Reservasi')->sortable(),
                SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->sortable(),
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
