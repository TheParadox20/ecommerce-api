<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariation extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'sku',
        'attribute_name',
        'attribute_value',
        'price',
        'stock',
        'discount',
        'status',
        'image',
        'weight_kg',
        'min_order_quantity',
        'is_bulk',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variation_attribute_value');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
