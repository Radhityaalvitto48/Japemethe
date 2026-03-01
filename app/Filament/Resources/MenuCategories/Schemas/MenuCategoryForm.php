<?php

namespace App\Filament\Resources\MenuCategories\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;


class MenuCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[A-Za-z\s]+$/')
                    ->helperText('Hanya huruf dan spasi.')
                    ->columnSpan('full'),

                FileUpload::make('image')
                    ->label('Image')
                    ->directory('menu-categories')
                    ->disk('public')
                    ->visibility('public')
                    ->image()
                    ->required()
                    ->maxSize(5 * 1024)
                    ->maxFiles(1)
                    ->imagePreviewHeight('150')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                    ->columnSpan('full'),

                Select::make('status_category')
                    ->label('Status Category')
                    ->options([
                        'visible' => 'Tampil',
                        'hidden' => 'Sembunyikan',
                        ])
                        ->default('visible')
                    ->columnSpan('full'),

                Toggle::make('display')
                    ->label('Display')
                    ->default(true),
            ]);
    }
}
