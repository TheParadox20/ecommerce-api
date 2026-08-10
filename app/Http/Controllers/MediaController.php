<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Exception;

class MediaController extends Controller
{
    /**
     * Centralized media upload handler for standalone assets 
     * (Blogs, Editor, Testimonials, etc.)
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|file|max:10240', // 10MB max
                'purpose' => 'nullable|string'
            ]);

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $destinationPath = public_path("storage/media");
                
                // Ensure directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $extension = $file->getClientOriginalExtension();
                $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                
                $file->move($destinationPath, $filename);
                $url = url("storage/media/" . $filename);

                $media = Media::create([
                    'product_id' => null, // Standalone media
                    'purpose' => $request->purpose ?? 'general',
                    'file' => $filename,
                    'url' => $url,
                ]);

                return response()->json([
                    'success' => true,
                    'url' => $url,
                    'media' => $media
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'No image provided'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
