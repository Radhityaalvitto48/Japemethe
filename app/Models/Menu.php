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

        $imageValue = $firstImageRecord?->image;

        if (! $imageValue && $firstImageRecord) {
            $rawImage = $firstImageRecord->getRawOriginal('image');
            if (is_string($rawImage) && trim($rawImage) !== '') {
                $imageValue = $rawImage;
            }
        }

        $imagePath = $this->extractFirstImagePath($imageValue);

        return $this->resolveImageUrl($imagePath);
    }

    private function extractFirstImagePath(mixed $imageValue): ?string
    {
        if (is_array($imageValue)) {
            $first = $imageValue[0] ?? null;

            return is_string($first) && trim($first) !== '' ? $first : null;
        }

        if (! is_string($imageValue)) {
            return null;
        }

        $imageValue = trim($imageValue);
        if ($imageValue === '') {
            return null;
        }

        if (str_starts_with($imageValue, '[')) {
            $decoded = json_decode($imageValue, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $first = $decoded[0] ?? null;

                return is_string($first) && trim($first) !== '' ? $first : null;
            }
        }

        return $imageValue;
    }

    private function resolveImageUrl(?string $path): string
    {
        if (! $path) {
            return asset('images/placeholder.jpg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($normalizedPath, 'storage/')) {
            return asset($normalizedPath);
        }

        return asset('storage/' . $normalizedPath);
    }

}
