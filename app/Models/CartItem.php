<?php
// app/Models/CartItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $table = 'cart_items';
    
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'attributes',
        'attributes_hash'
    ];

    protected $casts = [
        'attributes' => 'array',
        'quantity' => 'integer'
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}