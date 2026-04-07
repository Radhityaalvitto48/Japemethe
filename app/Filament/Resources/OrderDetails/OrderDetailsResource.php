<?php

namespace App\Filament\Resources\OrderDetails;

use App\Filament\Resources\OrderDetails\Pages\CreateOrderDetails;
use App\Filament\Resources\OrderDetails\Pages\EditOrderDetails;
use App\Filament\Resources\OrderDetails\Pages\ListOrderDetails;
use App\Filament\Resources\OrderDetails\Schemas\OrderDetailsForm;
use App\Filament\Resources\OrderDetails\Tables\OrderDetailsTable;
use App\Filament\Resources\BaseAdminResource;
use App\Models\OrderDetail;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OrderDetailsResource extends BaseAdminResource
{
    protected static ?string $model = OrderDetail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    public static function getNavigationLabel(): string
    {
        return 'Detail Pesanan';
    }

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'no';

    public static function form(Schema $schema): Schema
    {
        return OrderDetailsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderDetailsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['menu', 'order.table', 'order.orderDetails.menu']);
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
            'index' => ListOrderDetails::route('/'),
            'create' => CreateOrderDetails::route('/create'),
            'edit' => EditOrderDetails::route('/{record}/edit'),
        ];
    }
}
