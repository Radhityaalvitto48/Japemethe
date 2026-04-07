u<?php

use App\Models\Carousel;
use App\Models\MenuCategory;
use App\Models\MenuImage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('images:optimize-lcp {--dry-run : Hanya simulasi tanpa menulis file}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $storageRoot = storage_path('app/public');

    $targets = [
        [
            'label' => 'carousel',
            'dir' => $storageRoot . DIRECTORY_SEPARATOR . 'carousel-images',
            'width' => 960,
            'height' => 540,
            'quality' => 62,
        ],
        [
            'label' => 'menu-images',
            'dir' => $storageRoot . DIRECTORY_SEPARATOR . 'menu-images',
            'width' => 640,
            'height' => 640,
            'quality' => 68,
        ],
        [
            'label' => 'menu-categories',
            'dir' => $storageRoot . DIRECTORY_SEPARATOR . 'menu-categories',
            'width' => 512,
            'height' => 512,
            'quality' => 68,
        ],
    ];

    $manager = new ImageManager(new Driver());

    $processed = 0;
    $skipped = 0;
    $failed = 0;
    $beforeTotal = 0;
    $afterTotal = 0;
    $replacedPaths = [];

    foreach ($targets as $target) {
        if (! File::isDirectory($target['dir'])) {
            $this->warn("Lewati {$target['label']} (folder tidak ditemukan).");
            continue;
        }

        foreach (File::allFiles($target['dir']) as $file) {
            $sourcePath = $file->getPathname();
            $extension = strtolower($file->getExtension());

            if (! in_array($extension, ['webp', 'jpg', 'jpeg', 'png'], true)) {
                $skipped++;
                continue;
            }

            $beforeBytes = filesize($sourcePath) ?: 0;
            $beforeTotal += $beforeBytes;

            if ($beforeBytes < (15 * 1024)) {
                $afterTotal += $beforeBytes;
                $skipped++;
                continue;
            }

            try {
                $image = $manager->read($sourcePath);
                $image->coverDown($target['width'], $target['height']);

                $encoded = (string) $image->toWebp($target['quality']);
                $targetPath = preg_replace('/\.(jpe?g|png|webp)$/i', '.webp', $sourcePath) ?? $sourcePath;

                $afterBytes = strlen($encoded);
                $afterTotal += $afterBytes;

                if (! $dryRun) {
                    file_put_contents($targetPath, $encoded);

                    if ($targetPath !== $sourcePath && File::exists($sourcePath)) {
                        File::delete($sourcePath);
                    }
                }

                $processed++;

                $sourceRelative = str_replace('\\', '/', ltrim(str_replace($storageRoot, '', $sourcePath), DIRECTORY_SEPARATOR));
                $targetRelative = str_replace('\\', '/', ltrim(str_replace($storageRoot, '', $targetPath), DIRECTORY_SEPARATOR));

                if ($sourceRelative !== $targetRelative) {
                    $replacedPaths[$sourceRelative] = $targetRelative;
                }
            } catch (\Throwable $exception) {
                $failed++;
                $skipped++;
                $afterTotal += $beforeBytes;
                $this->warn('Gagal optimize: ' . $sourcePath . ' (' . $exception->getMessage() . ')');
            }
        }
    }

    if (! $dryRun && $replacedPaths !== []) {
        Carousel::query()
            ->whereIn('image', array_keys($replacedPaths))
            ->get()
            ->each(function (Carousel $carousel) use ($replacedPaths): void {
                $newPath = $replacedPaths[$carousel->image] ?? null;
                if ($newPath) {
                    $carousel->update(['image' => $newPath]);
                }
            });

        MenuCategory::query()
            ->whereIn('image', array_keys($replacedPaths))
            ->get()
            ->each(function (MenuCategory $menuCategory) use ($replacedPaths): void {
                $newPath = $replacedPaths[$menuCategory->image] ?? null;
                if ($newPath) {
                    $menuCategory->update(['image' => $newPath]);
                }
            });

        MenuImage::query()
            ->get()
            ->each(function (MenuImage $menuImage) use ($replacedPaths): void {
                $images = is_array($menuImage->image) ? $menuImage->image : [];
                $updated = array_map(fn (string $path) => $replacedPaths[$path] ?? $path, $images);

                if ($updated !== $images) {
                    $menuImage->update(['image' => $updated]);
                }
            });
    }

    $savedBytes = max(0, $beforeTotal - $afterTotal);
    $savedPercent = $beforeTotal > 0
        ? round(($savedBytes / $beforeTotal) * 100, 2)
        : 0;

    $this->newLine();
    $this->info($dryRun ? '[DRY-RUN] Simulasi optimize selesai.' : 'Optimize image selesai.');
    $this->line('Diproses: ' . $processed . ' file');
    $this->line('Dilewati: ' . $skipped . ' file');
    $this->line('Gagal: ' . $failed . ' file');
    $this->line('Ukuran awal: ' . round($beforeTotal / 1024, 2) . ' KB');
    $this->line('Ukuran akhir: ' . round($afterTotal / 1024, 2) . ' KB');
    $this->line('Hemat: ' . round($savedBytes / 1024, 2) . ' KB (' . $savedPercent . '%)');
})->purpose('Optimize existing uploaded images for better LCP performance.');
