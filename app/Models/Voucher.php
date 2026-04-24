<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_amount',
        'min_order_amount',
        'max_discount_amount',
        'influencer_id',
        'starts_at',
        'expires_at',
        'usage_limit',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
    ];

    /**
     * The influencer associated with this voucher.
     */
    public function influencer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    /**
     * The products this voucher is valid for.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_voucher');
    }

    /**
     * The orders where this voucher was used.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if the voucher is valid for use.
     */
    public function isValidFor(float $subtotal): bool
    {
        if (!$this->is_active) return false;
        
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->expires_at && $now->gt($this->expires_at)) return false;
        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) return false;
        if ($subtotal < $this->min_order_amount) return false;

        return true;
    }

    /**
     * Calculate the discount amount for a given subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        $discount = 0;

        if ($this->discount_type === 'percentage') {
            $discount = $subtotal * ($this->discount_amount / 100);
            if ($this->max_discount_amount !== null && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }
        } else {
            $discount = $this->discount_amount;
        }

        return min($discount, $subtotal);
    }
}
