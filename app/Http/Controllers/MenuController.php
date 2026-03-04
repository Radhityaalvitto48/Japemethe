<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\MenuCategory;
use Inertia\Inertia;

use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Menu::with(['menuCategory', 'menuImages'])
            ->where('status_menu', 'available')
            ->whereHas('menuCategory', function ($q) {
                $q->where('status_category', 'visible');
            });

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('menu_category_id', $request->category_id);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $menus = $query->orderBy('name')->get();

        // Get categories for filter
        $categories = MenuCategory::where('status_category', 'visible')
            ->get();

        // Get recommended menus
        $recommendedMenus = Menu::with(['menuCategory', 'menuImages'])
            ->where('status_menu', 'available')
            ->where('is_recommended', true)
            ->whereHas('menuCategory', function ($q) {
                $q->where('status_category', 'visible');
            })
            ->limit(6)
            ->get();

        return Inertia::render('Menu', [
            'menus' => $menus,
            'categories' => $categories,
            'promos' => [],
            'recommendedMenus' => $recommendedMenus,
            'selectedCategory' => $request->category_id,
            'searchQuery' => $request->search,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $menu = Menu::with(['menuCategory', 'menuImages'])->find($id);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $menu
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
