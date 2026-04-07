<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderDetail;
use App\Models\Promo;
use App\Models\Payment;
use App\Models\Table;

class Order extends Model
{
    protected $fillable = [
        'table_id',
        'order_number',
        'total_items',
        'total_price',
        'status_order',
        'customer_email',
        'customer_phone',
        'ordered_at',
        'promo_id',
    ];

    // Enable timestamps (created_at, updated_at)
    public $timestamps = true;

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function orderPromo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_id');
    }


    protected $dates = ['ordered_at'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            // ordered_at otomatis
            if (empty($order->ordered_at)) {
                $order->ordered_at = $order->created_at ?? now()->setTimezone('Asia/Jakarta');
            }

            // Generate order_number otomatis
            if (empty($order->order_number)) {
                $date = ($order->ordered_at ?? now())->format('Ymd');
                $count = self::whereDate('ordered_at', ($order->ordered_at ?? now())->toDateString())->count() + 1;
                $order->order_number = $date . str_pad($count, 3, '0', STR_PAD_LEFT);
            }

            // Pastikan promo_id integer atau null
            if (!empty($order->promo_id) && !is_numeric($order->promo_id)) {
                $order->promo_id = null;
            }
        });
        static::updating(function ($order) {
            if (!empty($order->promo_id) && !is_numeric($order->promo_id)) {
                $order->promo_id = null;
            }
        });
    }

}
