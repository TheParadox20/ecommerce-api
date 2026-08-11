<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferBundle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'banner_image',
        'bundle_price',
        'original_price',
        'is_active',
        'display_order',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'bundle_price' => 'float',
        'original_price' => 'float',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OfferBundleItem::class);
    }
}
