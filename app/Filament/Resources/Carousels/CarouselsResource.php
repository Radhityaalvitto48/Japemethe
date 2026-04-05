<?php

namespace App\Filament\Resources\Carousels;

use App\Filament\Resources\Carousels\Pages\CreateCarousels;
use App\Filament\Resources\Carousels\Pages\EditCarousels;
use App\Filament\Resources\Carousels\Pages\ListCarousels;
use App\Filament\Resources\Carousels\Schemas\CarouselsForm;
use App\Filament\Resources\Carousels\Tables\CarouselsTable;
use App\Filament\Resources\BaseAdminResource;
use App\Models\Carousel;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CarouselsResource extends BaseAdminResource
{
    protected static ?string $model = Carousel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Banner';
    }

    protected static ?string $recordTitleAttribute = 'no';

    public static function form(Schema $schema): Schema
    {
        return CarouselsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CarouselsTable::configure($table);
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
            'index' => ListCarousels::route('/'),
            'create' => CreateCarousels::route('/create'),
            'edit' => EditCarousels::route('/{record}/edit'),
        ];
    }
}
