<?php

namespace App\Filament\Resources\MenuImages;

use App\Filament\Resources\MenuImages\Pages\CreateMenuImage;
use App\Filament\Resources\MenuImages\Pages\EditMenuImage;
use App\Filament\Resources\MenuImages\Pages\ListMenuImages;
use App\Filament\Resources\MenuImages\Schemas\MenuImageForm;
use App\Filament\Resources\MenuImages\Tables\MenuImagesTable;
use App\Filament\Resources\BaseAdminResource;
use App\Models\MenuImage;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MenuImageResource extends BaseAdminResource
{
    protected static ?string $model = MenuImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Menu';

    public static function getNavigationLabel(): string
    {
        return 'Gambar Menu';
    }

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'no';

    public static function form(Schema $schema): Schema
    {
        return MenuImageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuImagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuImages::route('/'),
            'create' => CreateMenuImage::route('/create'),
            'edit' => EditMenuImage::route('/{record}/edit'),
        ];
    }
}
