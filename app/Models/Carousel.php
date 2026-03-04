<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class Carousel extends Model
{
    protected $fillable = [
        'image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        // Validasi maksimal 3 carousel aktif sebelum menyimpan
        static::saving(function ($carousel) {
            if ($carousel->is_active) {
                $activeCount = static::where('is_active', true)
                    ->when($carousel->exists, function ($query) use ($carousel) {
                        return $query->where('id', '!=', $carousel->id);
                    })
                    ->count();

                if ($activeCount >= 3) {
                    // Set carousel terlama sebagai inactive
                    $oldestActive = static::where('is_active', true)
                        ->when($carousel->exists, function ($query) use ($carousel) {
                            return $query->where('id', '!=', $carousel->id);
                        })
                        ->orderBy('updated_at', 'asc')
                        ->first();

                    if ($oldestActive) {
                        $oldestActive->update(['is_active' => false]);
                    }
                }
            }
        });

        // Konversi image ke WebP setelah disimpan
        static::saved(function ($carousel) {
            if ($carousel->image && !str_ends_with($carousel->image, '.webp')) {
                $imagePath = public_path('storage/' . $carousel->image);

                if (file_exists($imagePath)) {
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($imagePath);

                    // Resize ke ratio 16:9 dengan lebar optimal untuk web (1200px untuk performa)
                    $image->coverDown(1200, 675);

                    // Konversi ke WebP dengan kualitas optimal untuk LCP
                    $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $imagePath);
                    $encodedImage = $image->toWebp(75);
                    file_put_contents($webpPath, $encodedImage);

                    // Hapus file lama jika berbeda
                    if ($imagePath !== $webpPath) {
                        unlink($imagePath);
                    }

                    // Update path di database
                    $webpFileName = str_replace(public_path('storage/'), '', $webpPath);
                    $carousel->updateQuietly(['image' => $webpFileName]);
                }
            }
        });

        // Hapus file image saat record dihapus
        static::deleting(function ($carousel) {
            if ($carousel->image) {
                $imagePath = public_path('storage/' . $carousel->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        });
    }

    // Scope untuk carousel aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk carousel terbaru
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
