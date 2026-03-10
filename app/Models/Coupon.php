<?php
// app/Models/Coupon.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'customer_id',
        'is_public',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_coupon')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isValid($customerId = null, $orderAmount = null)
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check dates
        $now = now();
        if ($this->starts_at && $now < $this->starts_at) {
            return false;
        }
        if ($this->expires_at && $now > $this->expires_at) {
            return false;
        }

        // Check max uses
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        // Check if assigned to specific customer
        if ($this->customer_id && $this->customer_id != $customerId) {
            return false;
        }

        // Check if customer has already used this coupon
        if ($customerId && $this->customers()->where('customer_id', $customerId)->wherePivot('is_used', true)->exists()) {
            return false;
        }

        // Check minimum order amount
        if ($orderAmount && $this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($amount)
    {
        if ($this->type === 'fixed') {
            return min($this->value, $amount);
        } else {
            return ($amount * $this->value) / 100;
        }
    }
}