<?php

namespace App\Http\Controllers;

use App\Models\NavMenu;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

class NavMenuController extends Controller
{
    /**
     * Display a listing of active nav menus (Public).
     */
    public function index()
    {
        try {
            $menus = NavMenu::where('is_active', true)
                ->orderBy('order_position', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $menus
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing for Admin.
     */
    public function adminIndex()
    {
        try {
            $menus = NavMenu::orderBy('order_position', 'asc')->get();

            return response()->json([
                'success' => true,
                'menus' => $menus
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new menu item (Admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'url' => 'required|string',
            'order_position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_external' => 'nullable|boolean',
        ]);

        try {
            // Default order to end of list if not provided
            if (!isset($validated['order_position'])) {
                $validated['order_position'] = NavMenu::max('order_position') + 1;
            }

            $menu = NavMenu::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully',
                'menu' => $menu
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a menu item (Admin).
     */
    public function update(Request $request, $id)
    {
        $menu = NavMenu::findOrFail($id);

        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|string',
            'order_position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'is_external' => 'nullable|boolean',
        ]);

        try {
            $menu->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully',
                'menu' => $menu
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a menu item (Admin).
     */
    public function destroy($id)
    {
        try {
            $menu = NavMenu::findOrFail($id);
            $menu->delete();

            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder menu items (Admin).
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:nav_menus,id',
            'orders.*.order_position' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['orders'] as $item) {
                NavMenu::where('id', $item['id'])->update(['order_position' => $item['order_position']]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Menu reordered successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
