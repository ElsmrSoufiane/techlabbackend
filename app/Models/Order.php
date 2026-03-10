<?php
// app/Models/Order.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_id',
        'subtotal',
        'shipping',
        'tax',
        'total',
        'status',
        'shipping_address',
        'payment_method',
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