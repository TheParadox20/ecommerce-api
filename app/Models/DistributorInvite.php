<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributorInvite extends Model
{
    protected $fillable = [
        'email',
        'token',
        'invited_by',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    /**
     * The admin who sent this invite.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Whether this invite has already been used.
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Whether this invite is still valid (not expired, not accepted).
     */
    public function isValid(): bool
    {
        return ! $this->isAccepted() && $this->expires_at->isFuture();
    }
}
