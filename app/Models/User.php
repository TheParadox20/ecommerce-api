<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    
    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'status',
        'role',
        'profile_details',
    ];

    /**
     * Get the commissions earned by the influencer.
     */
    public function influencerCommissions()
    {
        return $this->hasMany(Commission::class, 'influencer_id');
    }

    /**
     * Get the payouts made to the influencer.
     */
    public function influencerPayouts()
    {
        return $this->hasMany(Payout::class, 'influencer_id');
    }

    /**
     * Get the vouchers associated with the influencer.
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'influencer_id');
    }

    /**
     * Generate an initial voucher for the partner upon approval.
     */
    public function generateInitialVoucher()
    {
        // Don't generate if they already have one
        if ($this->vouchers()->exists()) {
            return $this->vouchers()->first();
        }

        $baseCode = strtoupper(Str::slug($this->name, ''));
        $baseCode = substr($baseCode, 0, 6);
        $code = $baseCode;
        
        // Ensure uniqueness
        while (Voucher::where('code', $code)->exists()) {
            $code = $baseCode . rand(10, 99);
        }

        // Get default discount from settings or fallback to 5%
        $discountAmount = WebsiteSetting::where('key', 'default_partner_discount')->first()?->value ?? 5;

        return Voucher::create([
            'code' => $code,
            'discount_type' => 'percentage',
            'discount_amount' => $discountAmount,
            'influencer_id' => $this->id,
            'is_active' => true,
            'starts_at' => now(),
            'usage_limit' => null, // Unlimited usage for partner codes
        ]);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_details' => 'array',
        ];
    }

    /**
     * Check if the user is a super admin (Spatie role check).
     */
    public function isSuperAdmin(): bool
    {
        return $this->status === 'active' && $this->hasRole('super_admin');
    }

    /**
     * Check if the user is a buyer.
     */
    public function isBuyer(): bool
    {
        return $this->status === 'active' && $this->hasRole('buyer');
    }

    /**
     * Check if the user is a distributor.
     */
    public function isDistributor(): bool
    {
        return $this->status === 'active' && $this->hasRole('distributor');
    }

    /**
     * Check if the user is an influencer.
     */
    public function isInfluencer(): bool
    {
        return $this->status === 'active' && $this->hasRole('influencer');
    }

    /**
     * Legacy helper — kept for backward compat during transition.
     * @deprecated Use isSuperAdmin() or hasRole() instead.
     */
    public function isAdmin(): bool
    {
        return $this->status === 'active' && ($this->hasRole('super_admin') || $this->hasRole('admin'));
    }
}
