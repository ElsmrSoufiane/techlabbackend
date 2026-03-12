<?php
// app/Models/Customer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'pro_discount' => 'decimal:2',
    ];

    // Relationships
    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_customer')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPro()
    {
        return $this->tier === 'pro';
    }

    public function isRegular()
    {
        return $this->tier === 'regular' || $this->tier === null;
    }

    public function getProDiscountPercentage()
    {
        return $this->isPro() ? $this->pro_discount : 0;
    }

    public function calculateProPrice($originalPrice)
    {
        if ($this->isPro() && $this->pro_discount > 0) {
            $discount = ($originalPrice * $this->pro_discount) / 100;
            return round($originalPrice - $discount, 2);
        }
        return $originalPrice;
    }
}