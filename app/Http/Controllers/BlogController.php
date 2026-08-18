<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogComment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class BlogController extends Controller
{
    /**
     * Display a listing of blogs (Public).
     */
    public function index(Request $request)
    {
        try {
            $query = Blog::where('status', 'published');

            if ($request->has('brand_id')) {
                $query->whereHas('brands', function($q) use ($request) {
                    $q->where('brands.id', $request->brand_id);
                });
            }

            if ($request->has('product_id')) {
                $query->whereHas('products', function($q) use ($request) {
                    $q->where('products.id', $request->product_id);
                });
            }

            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('excerpt', 'like', '%' . $request->search . '%');
            }

            $blogs = $query->withCount(['comments' => function ($query) {
                    $query->where('is_approved', true);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate($request->query('per_page', 12));

            return response()->json([
                'success' => true,
                'data' => $blogs
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of blogs for Admin.
     */
    public function adminIndex()
    {
        try {
            $blogs = Blog::withCount('comments')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'blogs' => $blogs
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified blog for Admin.
     */
    public function adminShow($id)
    {
        try {
            $blog = Blog::with(['recipes', 'brands', 'products'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'blog' => $blog
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found'
            ], 404);
        }
    }

    /**
     * Display the specified blog (Public).
     */
    public function show($slug)
    {
        try {
            $blog = Blog::where('slug', $slug)
                ->where('status', 'published')
                ->with(['recipes', 'comments' => function ($query) {
                    $query->where('is_approved', true)->with('user');
                }])
                ->first();

            if (!$blog) {
                // Try finding by ID as fallback for admin edit
                $blog = Blog::with(['recipes', 'brands', 'products', 'comments.user'])->find($slug);
                
                if (!$blog || ($blog->status != 'published' && (!auth()->user() || !auth()->user()->isAdmin()))) {
                     return response()->json([
                        'success' => false,
                        'message' => 'Blog not found'
                    ], 404);
                }
            }

            return response()->json([
                'success' => true,
                'blog' => $blog
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new blog (Admin).
     */
    public function store(Request $request)
    {
        // Decode IDs if they come as JSON strings from FormData
        if (is_string($request->recipe_ids)) $request->merge(['recipe_ids' => json_decode($request->recipe_ids, true)]);
        if (is_string($request->brand_ids)) $request->merge(['brand_ids' => json_decode($request->brand_ids, true)]);
        if (is_string($request->product_ids)) $request->merge(['product_ids' => json_decode($request->product_ids, true)]);

        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:blogs,title',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable', // Can be file or string
            'youtube_url' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'allow_comments' => 'required', // Handle boolean from FormData
            'recipe_ids' => 'nullable|array',
            'recipe_ids.*' => 'exists:recipes,id',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:brands,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            // SEO fields
            'seo_title'       => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:165',
            'seo_keywords'    => 'nullable|string|max:500',
            'canonical_url'   => 'nullable|string|max:500',
            'noindex'         => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $validated['slug'] = Str::slug($validated['title']);
            $validated['content'] = $this->sanitizeHtml($validated['content']);
            $validated['allow_comments'] = filter_var($request->allow_comments, FILTER_VALIDATE_BOOLEAN);

            if ($request->hasFile('featured_image')) {
                $file = $request->file('featured_image');
                $destinationPath = public_path("storage/blogs");
                
                // Ensure directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $extension = $file->getClientOriginalExtension();
                $name = time() . '_' . str_replace(' ', '_', $validated['slug']) . '.' . $extension;
                $file->move($destinationPath, $name);
                $validated['featured_image'] = url("storage/blogs/" . $name);
            }

            $blog = Blog::create($validated);

            if (!empty($request->recipe_ids)) $blog->recipes()->sync($request->recipe_ids);
            if (!empty($request->brand_ids)) $blog->brands()->sync($request->brand_ids);
            if (!empty($request->product_ids)) $blog->products()->sync($request->product_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog created successfully',
                'blog' => $blog->load(['brands', 'products'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create blog: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified blog (Admin).
     */
    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        // Decode IDs if they come as JSON strings from FormData
        if (is_string($request->recipe_ids)) $request->merge(['recipe_ids' => json_decode($request->recipe_ids, true)]);
        if (is_string($request->brand_ids)) $request->merge(['brand_ids' => json_decode($request->brand_ids, true)]);
        if (is_string($request->product_ids)) $request->merge(['product_ids' => json_decode($request->product_ids, true)]);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255|unique:blogs,title,' . $id,
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|required|string',
            'featured_image' => 'nullable',
            'youtube_url' => 'nullable|string',
            'status' => 'sometimes|required|in:draft,published',
            'allow_comments' => 'sometimes|required',
            'recipe_ids' => 'nullable|array',
            'recipe_ids.*' => 'exists:recipes,id',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:brands,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            // SEO fields
            'seo_title'       => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:165',
            'seo_keywords'    => 'nullable|string|max:500',
            'canonical_url'   => 'nullable|string|max:500',
            'noindex'         => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['title'])) $validated['slug'] = Str::slug($validated['title']);
            if (isset($validated['content'])) $validated['content'] = $this->sanitizeHtml($validated['content']);
            if (isset($request->allow_comments)) $validated['allow_comments'] = filter_var($request->allow_comments, FILTER_VALIDATE_BOOLEAN);

            if ($request->hasFile('featured_image')) {
                // Delete old image if it exists and is local
                if ($blog->featured_image && str_contains($blog->featured_image, url('storage/blogs'))) {
                    $oldPath = public_path(str_replace(url('/'), '', $blog->featured_image));
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                $file = $request->file('featured_image');
                $destinationPath = public_path("storage/blogs");

                // Ensure directory exists
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $extension = $file->getClientOriginalExtension();
                $slug = $validated['slug'] ?? $blog->slug;
                $name = time() . '_' . str_replace(' ', '_', $slug) . '.' . $extension;
                $file->move($destinationPath, $name);
                $validated['featured_image'] = url("storage/blogs/" . $name);
            }

            $blog->update($validated);

            if (isset($request->recipe_ids)) $blog->recipes()->sync($request->recipe_ids);
            if (isset($request->brand_ids)) $blog->brands()->sync($request->brand_ids);
            if (isset($request->product_ids)) $blog->products()->sync($request->product_ids);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog updated successfully',
                'blog' => $blog->load(['brands', 'products'])
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update blog: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified blog (Admin).
     */
    public function destroy($id)
    {
        try {
            $blog = Blog::findOrFail($id);
            
            // Delete image if it exists and is local
            if ($blog->featured_image && str_contains($blog->featured_image, url('storage/blogs'))) {
                $oldPath = public_path(str_replace(url('/'), '', $blog->featured_image));
                if (file_exists($oldPath)) unlink($oldPath);
            }

            $blog->delete(); // Cascade handles junction and comments

            return response()->json([
                'success' => true,
                'message' => 'Blog deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a comment (Public).
     */
    public function storeComment(Request $request, $blogId)
    {
        $blog = Blog::findOrFail($blogId);
        
        if (!$blog->allow_comments) {
            return response()->json([
                'success' => false,
                'message' => 'Comments are disabled for this blog'
            ], 403);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
            'name' => auth()->check() ? 'nullable|string|max:255' : 'required|string|max:255',
        ]);

        try {
            $comment = BlogComment::create([
                'blog_id' => $blog->id,
                'user_id' => auth()->id(),
                'guest_name' => auth()->check() ? null : $validated['name'],
                'comment' => $validated['comment'],
                'is_approved' => false, // Require admin approval
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment submitted and is awaiting approval',
                'comment' => $comment->load('user')
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Private helper to sanitize HTML content.
     */
    private function sanitizeHtml($html)
    {
        // Use mews/purifier to properly sanitize HTML, preventing XSS
        // while allowing safe tags and attributes
        return clean($html);
    }
}
