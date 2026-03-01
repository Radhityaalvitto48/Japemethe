<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Order')
                    ->schema([
                        Select::make('table_id')
                            ->label('Table')
                            ->relationship('table', 'table_number')
                            ->required(),

                        TextInput::make('order_number')
                            ->label('Order Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->maxLength(30),

                        Select::make('status_order')
                            ->label('Status Order')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(2)
                    ->columnSpan('full'),

                Section::make('Detail Pelanggan')
                    ->schema([
                        TextInput::make('customer_email')
                            ->label('Customer Email')
                            ->email()
                            ->maxLength(100),

                        TextInput::make('customer_phone')
                            ->label('Customer Phone')
                            ->tel()
                            ->maxLength(20),
                    ])->columns(2)
                    ->columnSpan('full'),

                Section::make('Detail Pesanan')
                    ->schema([
                        TextInput::make('total_items')
                            ->label('Total Items')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        TextInput::make('total_price')
                            ->label('Total Price')
                            ->numeric()
                            ->required()
                            ->minValue(0),

                        TextInput::make('promo_id')
                            ->label('Kode Promo')
                            ->maxLength(30)
                            ->placeholder('Masukkan kode promo (opsional)')
                            ->afterStateUpdated(fn($state, callable $set) => $set('promo_id', strtoupper($state)))
                            ->formatStateUsing(fn($state) => strtoupper($state)),

                    ])->columns(3)
                    ->columnSpan('full'),
            ]);
    }
}
