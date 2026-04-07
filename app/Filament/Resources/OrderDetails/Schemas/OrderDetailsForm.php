<?php

namespace App\Filament\Resources\OrderDetails\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use App\Models\Menu;
use Filament\Forms\Components\Repeater;

class OrderDetailsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'order_number')
                    ->required(),
                Repeater::make('details')
                    ->label('Order Details')
                    ->schema([
                        Select::make('menu_id')
                            ->label('Menu')
                            ->relationship('menu', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $menu = Menu::find($state);
                                    $set('unit_price', $menu ? $menu->price : 0);
                                } else {
                                    $set('unit_price', 0);
                                }
                            }),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->default(1)
                            ->minValue(1)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('subtotal', $state * $get('unit_price'));
                            }),
                        TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->default(0)
                            ->reactive()
                            ->afterStateHydrated(function ($set, $get) {
                                $menuId = $get('menu_id');
                                if ($menuId) {
                                    $menu = Menu::find($menuId);
                                    $set('unit_price', $menu ? $menu->price : 0);
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('subtotal', $get('quantity') * $state);
                            }),
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->afterStateHydrated(function ($set, $get) {
                                $qty = $get('quantity') ?? 1;
                                $unit = $get('unit_price') ?? 0;
                                $set('subtotal', $qty * $unit);
                            }),
                        Textarea::make('note')
                            ->label('Note')
                            ->maxLength(255),
                    ])
                    ->minItems(1),
            ]);
    }
}
