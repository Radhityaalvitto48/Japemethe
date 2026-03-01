<?php

namespace App\Filament\Resources\Reservations\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use App\Models\Table;

class ReservationsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pelanggan')->schema([
                    TextInput::make('customer_name')
                        ->label('Nama Pelanggan')
                        ->required()
                        ->disabled()
                        ->maxLength(255),

                    TextInput::make('customer_phone')
                        ->label('Nomor Telepon')
                        ->required()
                        ->disabled()
                        ->maxLength(20),
                ])->columnSpan('full'),

                Section::make('Detail Reservasi')->schema([
                    Select::make('seating_type')
                        ->label('Tipe Tempat Duduk')
                        ->options([
                            'lesehan' => 'Lesehan',
                            'kursi' => 'Kursi',
                        ])
                        ->required()
                        ->disabled()
                        ->reactive(),

                    Select::make('id_table')
                        ->label('Meja')
                        ->options(fn ($get) => Table::where('seating_type', $get('seating_type'))
                            ->pluck('table_number', 'id'))
                        ->searchable()
                        ->disabled()
                        ->required(),

                    DatePicker::make('reservation_date')
                        ->label('Tanggal Reservasi')
                        ->disabled()
                        ->required(),

                    TimePicker::make('reservation_time')
                        ->label('Waktu Reservasi')
                        ->required(),
                ])->columns(2)
                ->columnSpan('full'),

                Section::make('Status Reservasi')->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'cancelled' => 'Cancelled',
                            'completed' => 'Completed',
                        ])
                        ->disabled()
                        ->required(),
                ])->columnSpan('full'),
            ]);
    }
}
