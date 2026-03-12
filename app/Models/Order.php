<?php
// app/Models/Order.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
   // In app/Models/Order.php
protected $fillable = [
    'order_number',
    'customer_id',
    'subtotal',
    'discount_amount',
    'shipping',
    'tax',
    'total',
    'status',
    'shipping_address',
    'phone',
    'notes',
    'payment_method',
    'coupon_id',
];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
// app/Models/Order.php (add relationship)
public function coupon()
{
    return $this->belongsTo(Coupon::class);
}
}