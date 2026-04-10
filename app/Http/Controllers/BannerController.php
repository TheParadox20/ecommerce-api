<?php

namespace App\Http\Controllers;

use App\Models\HomepageBanner;
use Illuminate\Http\Request;
use Exception;

class BannerController extends Controller
{
    public function index()
    {
        try {
            $banners = HomepageBanner::where('is_active', true)->orderBy('order', 'asc')->get();
            return response()->json([
                'success' => true,
                'data' => $banners
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function adminIndex()
    {
        return HomepageBanner::orderBy('order', 'asc')->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required|string',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'link_text' => 'nullable|string|max:100',
                'link_url' => 'nullable|string|max:255',
                'order' => 'integer',
                'is_active' => 'boolean'
            ]);

            $banner = HomepageBanner::create($validated);
            return response()->json(['success' => true, 'data' => $banner], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $banner = HomepageBanner::findOrFail($id);
            $validated = $request->validate([
                'image' => 'sometimes|required|string',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'link_text' => 'nullable|string|max:100',
                'link_url' => 'nullable|string|max:255',
                'order' => 'integer',
                'is_active' => 'boolean'
            ]);

            $banner->update($validated);
            return response()->json(['success' => true, 'data' => $banner]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $banner = HomepageBanner::findOrFail($id);
            $banner->delete();
            return response()->json(['success' => true, 'message' => 'Banner deleted']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
