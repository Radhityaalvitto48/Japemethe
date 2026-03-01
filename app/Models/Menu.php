<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MenuImage;
use App\Models\OrderDetail;
use App\Models\MenuCategory;

class Menu extends Model
{
    protected $fillable = [
        'menu_category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'is_recommended',
        'status_menu',
    ];

    public function menuImages()
    {
        return $this->hasMany(MenuImage::class, 'menu_id');
    }

    public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class, 'menu_id');
    }

    public function menuCategory()
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

}
