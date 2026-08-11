<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferBundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_bundle_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'override_price',
        'choice_group',
        'is_required',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'override_price' => 'float',
        'choice_group' => 'integer',
        'is_required' => 'boolean',
    ];

    public function offerBundle()
    {
        return $this->belongsTo(OfferBundle::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
