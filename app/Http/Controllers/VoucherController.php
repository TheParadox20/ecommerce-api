<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers (Admin only).
     */
    public function index()
    {
        $vouchers = Voucher::with(['influencer:id,name', 'products:id,name'])->latest()->get();
        return response()->json(['success' => true, 'vouchers' => $vouchers]);
    }

    /**
     * Store a new voucher (Admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers,code|max:20',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_amount' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'influencer_id' => 'nullable|exists:users,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $voucher = Voucher::create($validated);

        if ($request->has('product_ids')) {
            $voucher->products()->sync($validated['product_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully.',
            'voucher' => $voucher->load('products'),
        ], 201);
    }

    /**
     * Update the specified voucher (Admin only).
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $validated = $request->validate([
            'code' => ['sometimes', 'string', Rule::unique('vouchers')->ignore($voucher->id), 'max:20'],
            'discount_type' => 'sometimes|in:fixed,percentage',
            'discount_amount' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'influencer_id' => 'nullable|exists:users,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $voucher->update($validated);

        if ($request->has('product_ids')) {
            $voucher->products()->sync($validated['product_ids']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher updated successfully.',
            'voucher' => $voucher->load('products'),
        ]);
    }

    /**
     * Delete a voucher (Admin only).
     */
    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();
        return response()->json(['success' => true, 'message' => 'Voucher deleted successfully.']);
    }

    /**
     * Validate a code and calculate discount (Public).
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid voucher code.'
            ], 404);
        }

        if (!$voucher->isValidFor($request->subtotal)) {
            return response()->json([
                'success' => false,
                'message' => 'This voucher cannot be used at this time. It may be expired, used up, or the order total is too low.'
            ], 422);
        }

        $discount = $voucher->calculateDiscount($request->subtotal);

        return response()->json([
            'success' => true,
            'message' => 'Code applied successfully.',
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'discount_type' => $voucher->discount_type,
                'discount_amount' => $voucher->discount_amount,
            ],
            'discount' => $discount,
            'new_total' => $request->subtotal - $discount,
        ]);
    }
}
