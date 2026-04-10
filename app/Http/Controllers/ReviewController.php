<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Exception;

class ReviewController extends Controller
{
    /**
     * Display a listing of all reviews and testimonials (Public).
     */
    public function index(Request $request)
    {
        try {
            $productId = $request->query('product');
            
            if ($productId) {
                // Fetch product to get ID if slug/name was passed
                $product = \App\Models\Product::where('id', $productId)
                    ->orWhere('slug', $productId)
                    ->orWhere('name', $productId)
                    ->first();

                if (!$product) {
                    return response()->json(['success' => false, 'message' => 'Product not found'], 404);
                }

                $allApproved = Review::where('product_id', $product->id)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $total = $allApproved->count();
                $avg = $total > 0 ? round($allApproved->avg('rate'), 1) : 0;
                
                // Calculate distribution (Percentages for 5, 4, 3, 2, 1 stars)
                $distribution = [0, 0, 0, 0, 0];
                if ($total > 0) {
                    for ($i = 5; $i >= 1; $i--) {
                        $count = $allApproved->where('rate', '>=', $i)->where('rate', '<', $i + 1)->count();
                        // For distribution bars, we use exactly that rating
                        $count = $allApproved->where('rate', $i)->count();
                        $distribution[5 - $i] = round(($count / $total) * 100);
                    }
                }

                return response()->json([
                    'success' => true,
                    'rating' => $avg,
                    'reviews' => $total,
                    'ratings' => $distribution,
                    'reviewers' => $allApproved->map(function($review) {
                        return [
                            'name' => $review->reviewer_name ?: 'Verified Customer',
                            'rating' => $review->rate,
                            'comment' => $review->review,
                            'date' => $review->created_at->diffForHumans(),
                        ];
                    })
                ]);
            }

            // Fetch only approved product reviews with their associated product
            $productReviews = Review::with('product')
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($review) {
                    return [
                        'id' => 'review_' . $review->id,
                        'name' => $review->reviewer_name ?: 'Verified Customer',
                        'role' => 'Customer',
                        'comment' => $review->review,
                        'rating' => $review->rate,
                        'type' => 'review',
                        'product' => $review->product,
                        'created_at' => $review->created_at,
                    ];
                });

            // Fetch all active testimonials
            $testimonials = Testimonial::where('is_active', true)->orderBy('created_at', 'desc')->get()->map(function($testimonial) {
                return [
                    'id' => 'testimonial_' . $testimonial->id,
                    'name' => $testimonial->name,
                    'role' => $testimonial->role,
                    'comment' => $testimonial->comment,
                    'rating' => $testimonial->rating,
                    'type' => 'testimonial',
                    'product' => null,
                    'created_at' => $testimonial->created_at,
                ];
            });
            
            // Combine and sort by date
            $combined = $productReviews->concat($testimonials)->sortByDesc('created_at')->values();

            return response()->json([
                'success' => true,
                'data' => $combined
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reviews: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new review (as pending/draft).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'reviewer_name' => 'required|string|max:255',
                'review' => 'required|string',
                'rate' => 'required|numeric|min:1|max:5',
            ]);

            $validated['status'] = 'pending';

            $review = Review::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully and is awaiting moderation.',
                'data' => $review
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display a listing of all product reviews for administration.
     */
    public function adminIndex()
    {
        return Review::with('product')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Approve and publish a review.
     */
    public function approve($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->update(['status' => 'approved']);

            return response()->json([
                'success' => true,
                'message' => 'Review approved and published successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->delete();
            return response()->json([
                'success' => true, 
                'message' => 'Review deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to delete review: ' . $e->getMessage()
            ], 500);
        }
    }
}
