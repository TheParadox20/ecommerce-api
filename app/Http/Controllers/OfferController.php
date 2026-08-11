<?php

namespace App\Http\Controllers;

use App\Models\OfferBundle;
use App\Models\OfferBundleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class OfferController extends Controller
{
    /**
     * Public API: Get active offer bundles for storefront popup slideshow
     */
    public function indexActive()
    {
        try {
            $now = now();
            $offers = OfferBundle::with(['items.product.productImages', 'items.variation'])
                ->where('is_active', true)
                ->where(function ($query) use ($now) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
                })
                ->orderBy('display_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $offers
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active offer bundles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin API: Get all offer bundles
     */
    public function indexAdmin()
    {
        try {
            $offers = OfferBundle::with(['items.product', 'items.variation'])
                ->orderBy('display_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $offers
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin API: Create offer bundle
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'banner_image' => 'required|string',
            'bundle_price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.override_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $offer = OfferBundle::create([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'banner_image' => $request->banner_image,
                'bundle_price' => $request->bundle_price,
                'original_price' => $request->original_price,
                'is_active' => $request->is_active ?? true,
                'display_order' => $request->display_order ?? 0,
                'starts_at' => $request->starts_at,
                'expires_at' => $request->expires_at,
            ]);

            foreach ($request->items as $item) {
                OfferBundleItem::create([
                    'offer_bundle_id' => $offer->id,
                    'product_id' => $item['product_id'],
                    'product_variation_id' => $item['product_variation_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'override_price' => $item['override_price'] ?? null,
                    'choice_group' => $item['choice_group'] ?? null,
                    'is_required' => $item['is_required'] ?? true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offer bundle created successfully',
                'data' => $offer->load('items.product')
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create offer bundle: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin API: Update offer bundle
     */
    public function update(Request $request, $id)
    {
        $offer = OfferBundle::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'banner_image' => 'required|string',
            'bundle_price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.override_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $offer->update([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'banner_image' => $request->banner_image,
                'bundle_price' => $request->bundle_price,
                'original_price' => $request->original_price,
                'is_active' => $request->is_active ?? $offer->is_active,
                'display_order' => $request->display_order ?? $offer->display_order,
                'starts_at' => $request->starts_at,
                'expires_at' => $request->expires_at,
            ]);

            // Replace items
            $offer->items()->delete();
            foreach ($request->items as $item) {
                OfferBundleItem::create([
                    'offer_bundle_id' => $offer->id,
                    'product_id' => $item['product_id'],
                    'product_variation_id' => $item['product_variation_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'override_price' => $item['override_price'] ?? null,
                    'choice_group' => $item['choice_group'] ?? null,
                    'is_required' => $item['is_required'] ?? true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offer bundle updated successfully',
                'data' => $offer->load('items.product')
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update offer bundle: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin API: Delete offer bundle
     */
    public function destroy($id)
    {
        try {
            $offer = OfferBundle::findOrFail($id);
            $offer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Offer bundle deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public API: Resolve offer bundle items and return cart payload
     */
    public function getBundleCartPayload(Request $request, $id)
    {
        try {
            $offer = OfferBundle::with(['items.product.productImages', 'items.variation'])->findOrFail($id);

            $chosenProductIds = $request->input('choices', $request->query('choices', []));
            if (is_string($chosenProductIds)) {
                $chosenProductIds = json_decode($chosenProductIds, true) ?: explode(',', $chosenProductIds);
            }

            $itemsToInclude = [];
            $chosenIdsNormalized = array_map('strval', (array)$chosenProductIds);

            if (!empty($chosenIdsNormalized)) {
                // Choices provided: include required items + ONLY the explicitly chosen product(s)
                foreach ($offer->items as $item) {
                    $isChoice   = !empty($item->choice_group) || ($item->is_required !== null && !$item->is_required);
                    $isRequired = !$isChoice;
                    $isChosen   = in_array((string)$item->product_id, $chosenIdsNormalized);

                    if ($isRequired || $isChosen) {
                        $itemsToInclude[] = $item;
                    }
                    // All other choice-group items are intentionally excluded
                }
            } else {
                // No choices provided: include required items + first item of each choice group as default
                $processedChoiceGroups = [];
                foreach ($offer->items as $item) {
                    $isChoice   = !empty($item->choice_group) || ($item->is_required !== null && !$item->is_required);
                    $isRequired = !$isChoice;

                    if ($isRequired) {
                        $itemsToInclude[] = $item;
                    } else {
                        $groupId = $item->choice_group ?: 'default_group';
                        if (!in_array($groupId, $processedChoiceGroups)) {
                            $itemsToInclude[] = $item;
                            $processedChoiceGroups[] = $groupId;
                        }
                    }
                }
            }

            $cartItems = [];
            foreach ($itemsToInclude as $item) {
                $product = $item->product;
                $variation = $item->variation;

                $itemPrice = $item->override_price;
                if ($itemPrice === null) {
                    $itemPrice = $variation ? ($variation->price ?? $product->price) : $product->price;
                }

                $cartItems[] = [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'product_variation_id' => $item->product_variation_id,
                    'name' => $product->name,
                    'price' => (float)$itemPrice,
                    'quantity' => (int)$item->quantity,
                    'image' => $product->productImages->first()?->image_url ?? $offer->banner_image,
                    'slug' => $product->slug,
                    'bundle_id' => $offer->id,
                    'bundle_title' => $offer->title,
                ];
            }

            return response()->json([
                'success' => true,
                'bundle' => [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'banner_image' => $offer->banner_image,
                    'bundle_price' => (float)$offer->bundle_price,
                    'original_price' => (float)($offer->original_price ?? 0),
                ],
                'cart_items' => $cartItems
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve bundle cart payload: ' . $e->getMessage()
            ], 500);
        }
    }
}
