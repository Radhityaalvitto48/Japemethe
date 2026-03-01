<?php

namespace App\Filament\Resources\MenuImages\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;

class MenuImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'name', fn($query) => $query->where('status_menu', 'available'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->columnSpan('full'),
                FileUpload::make('image')
                    ->multiple()
                    ->label('Image')
                    ->directory('menu-images')
                    ->disk('public')
                    ->visibility(visibility: 'public')
                    ->maxFiles(5)
                    ->reorderable()
                    ->appendFiles()
                    ->image()
                    ->required()
                    ->maxSize(5 * 1024)
                    ->imagePreviewHeight('150')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                    ->columnSpan('full'),
            ]);
    }
}
