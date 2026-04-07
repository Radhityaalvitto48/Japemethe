<?php

namespace App\Filament\Resources\MenuImages\Pages;

use App\Filament\Resources\MenuImages\MenuImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenuImages extends ListRecords
{
    protected static string $resource = MenuImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
