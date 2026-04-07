<?php

namespace App\Filament\Resources\Payments\Schemas;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;


class PaymentsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'order_number')
                    ->required(),

                TextInput::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->required()
                    ->maxLength(50),

                Select::make('status_payment')
                    ->label('Status Pembayaran')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ])
                    ->required(),

                TextInput::make('grass_amount')
                    ->label('Jumlah Pembayaran')
                    ->numeric()
                    ->required()
                    ->minValue(0),

                TextInput::make('snap_token')
                    ->label('Snap Token')
                    ->maxLength(255),
            ]);
    }
}
