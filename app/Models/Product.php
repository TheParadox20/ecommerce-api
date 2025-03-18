<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'category_id',
        'brand_id',
        'about',
        'price',
        'discount',
    ];
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function description(): HasOne
    {
        return $this->hasOne(Description::class);
    }

    public function variation(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    public function faq(): HasMany
    {
        return $this->hasMany(ProductFAQ::class);
    }

    public function review(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
