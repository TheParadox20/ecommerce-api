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
        'voucher_id',
        'discount_amount',
        'version',
        'order_type',
        'shipping',
        'delivery_zone',
        'delivery_county',
        'carrier_type',
        'carrier_name',
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

    /**
     * Calculate the expected shipping date based on the 10:00 AM EAT cutoff.
     */
    public static function calculateShippingDate($timestamp = null)
    {
        $time = $timestamp ? \Illuminate\Support\Carbon::parse($timestamp) : now();
        $time->setTimezone('Africa/Nairobi');

        // Fetch cutoff from settings, default to 10 AM if not set
        $cutoffHour = \App\Models\WebsiteSetting::where('key', 'shipping_cutoff_hour')->value('value') ?? 10;

        // If it's before the cutoff hour, ship same day.
        if ($time->hour < (int)$cutoffHour) {
            return $time->toDateString();
        }

        // If it's at or after the cutoff hour, ship the next day.
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

    /**
     * Get the commission associated with the order.
     */
    public function commission()
    {
        return $this->hasOne(Commission::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}