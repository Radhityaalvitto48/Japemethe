<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\Str;



class MenuForm
{
    public static function configure(Schema $schema): Schema
{
        return $schema
            ->components([
                Select::make('menu_category_id')
                    ->label('Menu Category')
                    ->relationship('menuCategory', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan('full'),
                TextInput::make('name')
                    ->label('Name')
                    ->live()
                    ->afterStateUpdated(fn($set, ?string $state) => $set('slug', Str::slug($state)))
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[A-Za-z\s]+$/')
                    ->helperText('Hanya huruf dan spasi.'),
                TextInput::make('slug')
                    ->label('Slug')
                    ->readOnly()
                    ->required(),
                RichEditor::make('description')
                    ->label('Description')
                    ->columnSpan('full'),
                TextInput::make('price')
                    ->label('Price')
                    ->label('Price')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->suffix(',00'),
                TextInput::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->required(),
                Select::make('status_menu')
                    ->label('Status Menu')
                    ->options([
                        'available' => 'Tampil',
                        'unavailable' => 'Sembunyikan',
                ])->columnSpan('full'),
                Toggle::make('is_recommended')
                    ->label('Recommended'),
            ]);
    }
}
