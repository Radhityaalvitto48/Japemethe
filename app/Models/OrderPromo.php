<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Promo;
use App\Models\Order;

class OrderPromo extends Model
{
    protected $fillable = [
        'order_id',
        'promo_id',
        'discount_amount',
    ];


    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

}
