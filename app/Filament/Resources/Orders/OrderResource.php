<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\BaseAdminResource;
use App\Models\Order;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends BaseAdminResource
{
    protected static ?string $model = Order::class;

        protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

        protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

        protected static ?int $navigationSort = 1;

        public static function getNavigationLabel(): string
        {
            return 'Pesanan';
        }

        protected static ?string $recordTitleAttribute = 'no';

        public static function form(Schema $schema): Schema
        {
            return OrderForm::configure($schema);
        }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['table', 'orderDetails.menu']);
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
