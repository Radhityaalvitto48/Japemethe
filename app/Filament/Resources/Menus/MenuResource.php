<?php

namespace App\Filament\Resources\Menus;

use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\Menus\Schemas\MenuForm;
use App\Filament\Resources\Menus\Tables\MenusTable;
use App\Filament\Resources\BaseAdminResource;
use App\Models\Menu;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends BaseAdminResource
{
    protected static ?string $model = Menu::class;

        protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

        protected static string|UnitEnum|null $navigationGroup = 'Menu';

        protected static ?int $navigationSort = 2;

        public static function getNavigationLabel(): string
        {
            return 'Menu';
        }

        protected static ?string $recordTitleAttribute = 'Menu';

        public static function form(Schema $schema): Schema
        {
            return MenuForm::configure($schema);
        }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
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
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
