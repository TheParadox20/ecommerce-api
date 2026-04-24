<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'influencer_id',
        'amount',
        'payment_method',
        'reference',
        'status',
    ];

    /**
     * Get the influencer associated with the payout.
     */
    public function influencer()
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    /**
     * Get the commissions associated with this payout.
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }
}
