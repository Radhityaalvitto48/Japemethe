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

    protected $appends = [
        'id_menu',
        'image_url',
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

    public function getIdMenuAttribute(): int
    {
        return (int) $this->attributes['id'];
    }

    public function getImageUrlAttribute(): string
    {
        $firstImageRecord = $this->relationLoaded('menuImages')
            ? $this->menuImages->first()
            : $this->menuImages()->select(['id', 'menu_id', 'image'])->first();

        $imagePath = null;
        if ($firstImageRecord && is_array($firstImageRecord->image) && isset($firstImageRecord->image[0])) {
            $imagePath = $firstImageRecord->image[0];
        }

        return $this->resolveImageUrl($imagePath);
    }

    private function resolveImageUrl(?string $path): string
    {
        if (! $path) {
            return asset('images/placeholder.jpg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

}
