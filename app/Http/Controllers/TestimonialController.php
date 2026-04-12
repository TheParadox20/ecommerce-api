<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Exception;

class TestimonialController extends Controller
{
    public function index()
    {
        try {
            $testimonials = Testimonial::where('is_active', true)->orderBy('created_at', 'desc')->get();
            return response()->json([
                'success' => true,
                'data' => $testimonials
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function adminIndex()
    {
        return Testimonial::orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'role' => 'nullable|string|max:255',
                'comment' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
                'image' => 'nullable', // Can be file or string
                'is_active' => 'nullable|boolean'
            ]);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $destinationPath = public_path("storage/testimonials");
                if (!file_exists($destinationPath)) mkdir($destinationPath, 0755, true);

                $name = time() . '_' . str_replace(' ', '_', $validated['name']) . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $name);
                $validated['image'] = url("storage/testimonials/" . $name);
            }

            $testimonial = Testimonial::create($validated);
            return response()->json(['success' => true, 'data' => $testimonial], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $testimonial = Testimonial::findOrFail($id);
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'role' => 'nullable|string|max:255',
                'comment' => 'sometimes|required|string',
                'rating' => 'sometimes|integer|min:1|max:5',
                'image' => 'nullable',
                'is_active' => 'nullable|boolean'
            ]);

            if ($request->hasFile('image')) {
                // Delete old image if it exists and is local
                if ($testimonial->image && str_contains($testimonial->image, url('storage/testimonials'))) {
                    $oldPath = str_replace(url(''), public_path(), $testimonial->image);
                    if (file_exists($oldPath)) @unlink($oldPath);
                }

                $file = $request->file('image');
                $destinationPath = public_path("storage/testimonials");
                if (!file_exists($destinationPath)) mkdir($destinationPath, 0755, true);

                $name = time() . '_' . str_replace(' ', '_', ($validated['name'] ?? $testimonial->name)) . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $name);
                $validated['image'] = url("storage/testimonials/" . $name);
            }

            $testimonial->update($validated);
            return response()->json(['success' => true, 'data' => $testimonial]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $testimonial = Testimonial::findOrFail($id);

            // Delete image if it exists and is local
            if ($testimonial->image && str_contains($testimonial->image, url('storage/testimonials'))) {
                $oldPath = str_replace(url(''), public_path(), $testimonial->image);
                if (file_exists($oldPath)) @unlink($oldPath);
            }

            $testimonial->delete();
            return response()->json(['success' => true, 'message' => 'Testimonial deleted']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
