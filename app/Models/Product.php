<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'brand_id',
        'about',
        'price',
        'discount',
        'stock',
        'version',
    ];
    
    protected $casts = [
        'stock' => 'integer',
        'price' => 'decimal:2',
        'discount' => 'integer',
        'version' => 'integer',
    ];

    public function updateOptimistically(array $attributes, $expectedVersion = null)
    {
        $expectedVersion = $expectedVersion ?? $this->version;
        $attributes['version'] = $expectedVersion + 1;

        $updated = static::where('id', $this->id)
                         ->where('version', $expectedVersion)
                         ->update($attributes);

        if (!$updated) {
            throw new \Exception('Conflict detected: This record has been updated by another user.');
        }
        
        return $this->refresh();
    }

    protected $with = ['category', 'brand'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function productVariations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'product_recipe');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFAQ::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function description(): HasOne
    {
        return $this->hasOne(Description::class);
    }
}