<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'influencer_id',
        'payout_id',
        'amount',
        'status',
    ];

    /**
     * Get the order associated with the commission.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the influencer associated with the commission.
     */
    public function influencer()
    {
        return $this->belongsTo(User::class, 'influencer_id');
    }

    /**
     * Get the payout associated with the commission.
     */
    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
