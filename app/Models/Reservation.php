<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'id_table',
        'seating_type',
        'reservation_date',
        'reservation_time',
        'status',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class, 'id_table');
    }
}
