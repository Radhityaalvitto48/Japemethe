<?php

namespace App\Filament\Resources\OrderDetails\Pages;

use App\Filament\Resources\OrderDetails\OrderDetailsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderDetails extends CreateRecord
{
    protected static string $resource = OrderDetailsResource::class;
}
