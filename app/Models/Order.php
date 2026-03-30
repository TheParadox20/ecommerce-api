<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'total',
        'payment_method',
        'payment_status',
        'payment_reference',
        'latitude',
        'longitude',
        'shipment_id',
        'sales',
        'order_details',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->slug = $order->slug ?: Str::random(5);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function orderDetail(): HasOne
    {
        return $this->hasOne(OrderDetail::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}