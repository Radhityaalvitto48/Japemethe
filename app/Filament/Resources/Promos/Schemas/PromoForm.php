<?php

namespace App\Filament\Resources\Promos\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class PromoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Promo')
                    ->required()
                    ->maxLength(30),

                TextInput::make('name')
                    ->label('Nama Promo')
                    ->maxLength(255),

                Select::make('type')
                    ->label('Tipe Diskon')
                    ->options([
                        'percentage' => 'Persentase',
                        'fixed_amount' => 'Jumlah Tetap',
                    ])
                    ->required()
                    ->reactive(),

                TextInput::make('value')
                    ->label(fn ($get) => $get('type') === 'percentage' ? 'Persentase (%)' : 'Jumlah Tetap (Rp)')
                    ->numeric()
                    ->required()
                    ->minValue(fn ($get) => $get('type') === 'percentage' ? 1 : 0)
                    ->maxValue(fn ($get) => $get('type') === 'percentage' ? 100 : null)
                    ->helperText(fn ($get) => $get('type') === 'percentage' ? 'Masukkan nilai diskon dalam persen (1-100)' : 'Masukkan jumlah diskon dalam rupiah'),

                TextInput::make('minimum_price')
                    ->label('Harga Minimum')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('Masukkan harga minimum untuk menggunakan promo ini'),

                Select::make('status_promo')
                    ->label('Status Promo')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->required(),

                DatePicker::make('valid_from')
                    ->label('Berlaku Mulai')
                    ->required(),

                DatePicker::make('valid_until')
                    ->label('Berlaku Hingga')
                    ->required(),
            ]);
    }
}
