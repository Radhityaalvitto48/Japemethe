<?php

namespace App\Filament\Resources\MenuImages\Pages;

use App\Filament\Resources\MenuImages\MenuImageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenuImage extends EditRecord
{
    protected static string $resource = MenuImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
