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
    \Log::info('🔍 Checking coupon validity', [
        'code' => $this->code,
        'customer_id' => $customerId,
        'order_amount' => $orderAmount,
        'is_active' => $this->is_active,
        'starts_at' => $this->starts_at,
        'expires_at' => $this->expires_at,
        'now' => now(),
        'max_uses' => $this->max_uses,
        'used_count' => $this->used_count,
        'coupon_customer_id' => $this->customer_id,
        'min_order' => $this->min_order_amount
    ]);

    // Check if active
    if (!$this->is_active) {
        \Log::info('❌ Failed: not active');
        return false;
    }

    // Check dates
    $now = now();
    if ($this->starts_at && $now < $this->starts_at) {
        \Log::info('❌ Failed: not started yet', ['starts_at' => $this->starts_at]);
        return false;
    }
    if ($this->expires_at && $now > $this->expires_at) {
        \Log::info('❌ Failed: expired', ['expires_at' => $this->expires_at]);
        return false;
    }

    // Check max uses
    if ($this->max_uses && $this->used_count >= $this->max_uses) {
        \Log::info('❌ Failed: max uses reached');
        return false;
    }

    // Check if assigned to specific customer
    if ($this->customer_id && $this->customer_id != $customerId) {
        \Log::info('❌ Failed: wrong customer', ['expected' => $this->customer_id, 'got' => $customerId]);
        return false;
    }

    // Check minimum order amount
    if ($orderAmount && $this->min_order_amount && $orderAmount < $this->min_order_amount) {
        \Log::info('❌ Failed: order too low', ['required' => $this->min_order_amount, 'got' => $orderAmount]);
        return false;
    }

    \Log::info('✅ Coupon is valid');
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