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
        'delivery_method',
        'pickup_station',
        'expected_shipping_date',
        'latitude',
        'longitude',
        'shipment_id',
        'status',
        'sales',
        'order_details',
    ];

    /**
     * Calculate the expected shipping date based on the 10:00 AM EAT cutoff.
     */
    public static function calculateShippingDate($timestamp = null)
    {
        $time = $timestamp ? Carbon::parse($timestamp) : now();
        $time->setTimezone('Africa/Nairobi');

        // If it's before 10:00 AM, ship same day.
        if ($time->hour < 10) {
            return $time->toDateString();
        }

        // If it's 10:00 AM or later, ship the next day.
        return $time->addDay()->toDateString();
    }

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