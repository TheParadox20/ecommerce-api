<?php

namespace App\Http\Controllers;

use App\Models\BlogComment;
use Illuminate\Http\Request;
use Exception;

class BlogCommentController extends Controller
{
    /**
     * Display a listing of all comments for Admin.
     */
    public function index()
    {
        try {
            $comments = BlogComment::with(['blog', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'comments' => $comments
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve/Unapprove a comment.
     */
    public function approve($id)
    {
        try {
            $comment = BlogComment::findOrFail($id);
            $comment->is_approved = !$comment->is_approved;
            $comment->save();

            return response()->json([
                'success' => true,
                'message' => 'Comment approval toggled successfully',
                'comment' => $comment
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified comment.
     */
    public function destroy($id)
    {
        try {
            $comment = BlogComment::findOrFail($id);
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
