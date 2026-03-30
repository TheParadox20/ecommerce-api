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
    public function index()
    {
        try {
            $blogs = Blog::where('status', 'published')
                ->withCount(['comments' => function ($query) {
                    $query->where('is_approved', true);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(12);

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
                // Fallback for admin if slug is not found as published
                $blog = Blog::where('slug', $slug)->with(['recipes', 'comments.user'])->first();
                
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
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:blogs,title',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|string',
            'youtube_url' => 'nullable|string|url',
            'status' => 'required|in:draft,published',
            'allow_comments' => 'required|boolean',
            'recipe_ids' => 'nullable|array',
            'recipe_ids.*' => 'exists:recipes,id',
        ]);

        DB::beginTransaction();
        try {
            // Auto-generate slug if not provided
            $validated['slug'] = Str::slug($validated['title']);
            
            // Basic HTML sanitization for content (allow common tags)
            $validated['content'] = $this->sanitizeHtml($validated['content']);

            $blog = Blog::create($validated);

            if (!empty($validated['recipe_ids'])) {
                $blog->recipes()->sync($validated['recipe_ids']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog created successfully',
                'blog' => $blog
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

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255|unique:blogs,title,' . $id,
            'excerpt' => 'nullable|string',
            'content' => 'sometimes|required|string',
            'featured_image' => 'nullable|string',
            'youtube_url' => 'nullable|string|url',
            'status' => 'sometimes|required|in:draft,published',
            'allow_comments' => 'sometimes|required|boolean',
            'recipe_ids' => 'nullable|array',
            'recipe_ids.*' => 'exists:recipes,id',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['title'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            if (isset($validated['content'])) {
                $validated['content'] = $this->sanitizeHtml($validated['content']);
            }

            $blog->update($validated);

            if (isset($validated['recipe_ids'])) {
                $blog->recipes()->sync($validated['recipe_ids']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Blog updated successfully',
                'blog' => $blog
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
        ]);

        try {
            $comment = BlogComment::create([
                'blog_id' => $blog->id,
                'user_id' => auth()->id(),
                'comment' => $validated['comment'],
                'is_approved' => true, // Default to true as per requirements, but can be changed later
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Comment submitted successfully',
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
        // Allow common rich text tags
        $allowedTags = '<p><a><b><i><u><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6><img><iframe><div><span>';
        return strip_tags($html, $allowedTags);
    }
}
