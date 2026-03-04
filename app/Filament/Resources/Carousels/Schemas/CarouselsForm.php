<?php

namespace App\Filament\Resources\Carousels\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Models\Carousel;
use Filament\Notifications\Notification;

class CarouselsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Upload gambar dengan ratio 16:9 landscape. Maksimal 3 carousel yang aktif.')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Carousel Image')
                            ->image()
                            ->directory('carousel-images')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(5 * 1024)
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg'])
                            ->columnSpan('full')
                            ->required()
                            ->helperText('Gambar harus memiliki rasio 16:9 landscape (misalnya: 1600x900 px)'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(false)
                            ->helperText('Maksimal 3 carousel yang dapat aktif bersamaan. Jika lebih dari 3, carousel terlama akan dinonaktifkan otomatis.')
                            ->afterStateUpdated(function ($state, $record) {
                                if ($state) {
                                    $activeCount = Carousel::where('is_active', true)
                                        ->when($record, fn($query) => $query->where('id', '!=', $record->id))
                                        ->count();

                                    if ($activeCount >= 3) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Info')
                                            ->body('Sudah ada 3 carousel aktif. Carousel terlama akan dinonaktifkan otomatis.')
                                            ->send();
                                    }
                                }
                            })
                            ->reactive()
                    ])->columnSpan('full'),
            ]);
    }
}
