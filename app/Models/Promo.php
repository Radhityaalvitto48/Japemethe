<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderPromo;

class Promo extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'minimum_price',
        'status_promo',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'status_promo' => 'string',
    ];

    function orderPromos()
    {
        return $this->hasMany(OrderPromo::class, 'promo_id');
    }
}
