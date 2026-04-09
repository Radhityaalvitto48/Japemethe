<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends \App\Http\Controllers\Controller
{
    public function indexPage(Request $request): Response
    {
        return Inertia::render('Menu', $this->buildMenuPagePayload($request));
    }

    public function scanTable(Request $request, string $scanHash): Response
    {
        $tableNumber = Table::decodeScanToken($scanHash);

        abort_if(! is_string($tableNumber) || $tableNumber === '', 404);

        $table = Table::query()
            ->where('table_number', $tableNumber)
            ->where('is_active', true)
            ->firstOrFail();

        return Inertia::render('Menu', $this->buildMenuPagePayload($request, $table));
    }

    public function index(Request $request): JsonResponse
    {
        $menus = $this->baseMenuQuery($request)->get();

        return response()->json([
            'success' => true,
            'data' => $menus,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $menu = Menu::query()
            ->with([
                'menuCategory:id,name,image,display,status_category',
                'menuImages:id,menu_id,image',
            ])
            ->find($id);

        if (! $menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    private function buildMenuPagePayload(Request $request, ?Table $table = null): array
    {
        $menus = $this->menuListQuery($request)
            ->get()
            ->map(fn (Menu $menu) => $this->transformMenuListItem($menu))
            ->values();

        $categories = MenuCategory::query()
            ->where('status_category', 'visible')
            ->where('display', true)
            ->orderBy('display')
            ->orderBy('name')
            ->get();

        $banners = Carousel::query()
            ->active()
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Carousel $carousel) => [
                'id' => $carousel->id,
                'image' => $this->resolveStorageUrl($carousel->image),
                'title' => null,
            ])
            ->values();

        return [
            'menus' => $menus,
            'categories' => $categories,
            'banners' => $banners,
            'selectedCategory' => $request->filled('category_id')
                ? (int) $request->input('category_id')
                : null,
            'searchQuery' => $request->string('search')->toString(),
            'currentTable' => $table ? $this->formatTableForMenu($table) : null,
        ];
    }

    private function menuListQuery(Request $request)
    {
        $search = trim($request->string('search')->toString());
        $categoryId = $request->input('category_id');

        return Menu::query()
            ->select([
                'id',
                'menu_category_id',
                'name',
                'slug',
                'price',
                'stock',
                'is_recommended',
                'status_menu',
            ])
            ->with([
                'menuCategory:id,name',
                'menuImages:id,menu_id,image',
            ])
            ->where('status_menu', 'available')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(is_numeric($categoryId), fn (Builder $query) => $query->where('menu_category_id', (int) $categoryId))
            ->orderByDesc('is_recommended')
            ->orderBy('name');
    }

    private function baseMenuQuery(Request $request)
    {
        $search = trim($request->string('search')->toString());
        $categoryId = $request->input('category_id');

        return Menu::query()
            ->with([
                'menuCategory:id,name,image,display,status_category',
                'menuImages:id,menu_id,image',
            ])
            ->where('status_menu', 'available')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(is_numeric($categoryId), fn (Builder $query) => $query->where('menu_category_id', (int) $categoryId))
            ->orderByDesc('is_recommended')
            ->orderBy('name');
    }

    private function transformMenuListItem(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'id_menu' => (int) $menu->id,
            'menu_category_id' => $menu->menu_category_id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'price' => $menu->price,
            'stock' => $menu->stock,
            'is_recommended' => (bool) $menu->is_recommended,
            'status_menu' => $menu->status_menu,
            'image_url' => $menu->image_url,
            'menu_images' => $menu->relationLoaded('menuImages')
                ? $menu->menuImages->map(fn ($image) => [
                    'id' => $image->id,
                    'menu_id' => $image->menu_id,
                    'image' => $image->image,
                ])->values()
                : [],
            'menu_category' => [
                'id' => $menu->menuCategory?->id,
                'name' => $menu->menuCategory?->name,
            ],
        ];
    }

    private function formatTableForMenu(Table $table): array
    {
        return [
            'id' => $table->id,
            'table_number' => $table->table_number,
            'seating_type' => $table->seating_type === 'chair' ? 'kursi' : $table->seating_type,
            'qr_code' => $table->qr_code ? $this->resolveStorageUrl($table->qr_code) : null,
            'is_active' => (bool) $table->is_active,
        ];
    }

    private function resolveStorageUrl(?string $path): string
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
