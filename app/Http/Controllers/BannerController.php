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
            $page = request()->query('page', 'homepage');
            $banners = HomepageBanner::where('page', $page)
                ->where('is_active', true)
                ->orderBy('order', 'asc')
                ->get();
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
        $page = request()->query('page', 'homepage');
        return HomepageBanner::where('page', $page)->orderBy('order', 'asc')->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'image' => 'required', // Can be a file or a string path
                'page' => 'required|string',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'link_text' => 'nullable|string|max:100',
                'link_url' => 'nullable|string|max:255',
                'order' => 'nullable|integer',
                'is_active' => 'nullable|boolean'
            ]);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $file->move(public_path('storage/banners'), $name);
                $validated['image'] = url('storage/banners/' . $name);
            }

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
                'image' => 'sometimes|required',
                'page' => 'sometimes|string',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'link_text' => 'nullable|string|max:100',
                'link_url' => 'nullable|string|max:255',
                'order' => 'nullable|integer',
                'is_active' => 'nullable|boolean'
            ]);

            if ($request->hasFile('image')) {
                // Delete old image if it exists and is a local storage path
                if ($banner->image && str_contains($banner->image, url('storage/banners'))) {
                    try {
                        $oldPath = str_replace(url(''), public_path(), $banner->image);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    } catch (Exception $e) {
                        \Log::error('Failed to delete old banner image: ' . $e->getMessage());
                    }
                }

                $file = $request->file('image');
                $name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                $file->move(public_path('storage/banners'), $name);
                $validated['image'] = url('storage/banners/' . $name);
            }

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
