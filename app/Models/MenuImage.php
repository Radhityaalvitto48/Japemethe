<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Menu;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MenuImage extends Model
{
    protected $fillable = [
        'menu_id',
        'image',
    ];

    protected $casts = [
        'image' => 'array',
    ];

    protected static function booted()
    {
        static::saved(function ($menuImage) {
            if (is_array($menuImage->image)) {
                $updatedImages = [];
                foreach ($menuImage->image as $img) {
                    $imagePath = public_path('storage/' . $img);

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
                        $updatedImages[] = $webpFileName;
                    } else {
                        $updatedImages[] = $img;
                    }
                }
                $menuImage->updateQuietly(['image' => $updatedImages]);
            }
        });

        static::deleting(function ($menuImage) {
            if (is_array($menuImage->image)) {
                foreach ($menuImage->image as $img) {
                    $imagePath = public_path('storage/' . $img);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }
        });
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
