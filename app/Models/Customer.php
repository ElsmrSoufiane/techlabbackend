<?php
// app/Models/Customer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
        'tier',
        'pro_discount',
        'company_name',
        'verification_token',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'pro_discount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the favorites for the customer.
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')
                    ->withTimestamps();
    }

    /**
     * Get the orders for the customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get the coupons for the customer.
     */
    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_customer')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is pro customer
     */
    public function isPro(): bool
    {
        return $this->tier === 'pro';
    }

    /**
     * Check if user is regular customer
     */
    public function isRegular(): bool
    {
        return $this->tier === 'regular' || $this->tier === null;
    }

    /**
     * Get pro discount percentage
     */
    public function getProDiscountPercentage(): int
    {
        return $this->isPro() ? (int) $this->pro_discount : 0;
    }

    /**
     * Calculate pro price for a given original price
     */
    public function calculateProPrice(float $originalPrice): float
    {
        if ($this->isPro() && $this->pro_discount > 0) {
            $discount = ($originalPrice * $this->pro_discount) / 100;
            return round($originalPrice - $discount, 2);
        }
        return $originalPrice;
    }

    /**
     * Get company name if pro
     */
    public function getCompanyNameAttribute($value)
    {
        return $this->isPro() ? $value : null;
    }
}