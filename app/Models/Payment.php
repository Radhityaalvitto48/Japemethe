<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method',
        'status_payment',
        'grass_amount',
        'snap_token',
        'status',
        'payment_date',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($payment) {
            if (empty($payment->payment_date)) {
                $payment->payment_date = $payment->created_at ?? now()->setTimezone('Asia/Jakarta');
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
