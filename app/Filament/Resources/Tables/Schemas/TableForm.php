<?php

namespace App\Filament\Resources\Tables\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class TableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('seating_type')
                    ->label('Tipe Meja')
                    ->options([
                        'lesehan' => 'Lesehan',
                        'chair' => 'Kursi',
                    ])
                    ->required()
                    ->columnSpan('full'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
