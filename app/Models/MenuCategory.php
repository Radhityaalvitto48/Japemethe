<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Menu;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MenuCategory extends Model
{
    protected $fillable = [
        'name',
        'image',
        'display',
        'status_category',
    ];

    protected $casts = [
        'display' => 'boolean',
    ];

    protected static function booted()
    {
        static::deleting(function ($menuCategory) {
            if ($menuCategory->image) {
                $imagePath = public_path('storage/' . $menuCategory->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        });

        static::saved(function ($menuCategory) {
            if ($menuCategory->isDirty('image') && $menuCategory->image) {
                $imagePath = public_path('storage/' . $menuCategory->image);

                if (file_exists($imagePath) && !str_ends_with($imagePath, '.webp')) {
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($imagePath);
                    $image->cover(800, 800);
                    $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $imagePath);
                    $encodedImage = $image->toWebp(90);
                    file_put_contents($webpPath, $encodedImage);
                    if ($imagePath !== $webpPath) {
                        unlink($imagePath);
                    }
                    $webpFileName = str_replace(public_path('storage/'), '', $webpPath);
                    $menuCategory->updateQuietly(['image' => $webpFileName]);
                } else {
                    throw new \Exception('Gambar tidak berubah atau file tidak ditemukan.');
                }
            }
        });
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'menu_category_id');
    }
}
