<?php
// app/Models/ProductReview.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $table = 'product_reviews';
    
    protected $fillable = [
        'product_id',
        'customer_id',
        'rating',
        'title',
        'review',
        'images',
        'verified_purchase',
        'is_approved',
        'helpful_count'
    ];

    protected $casts = [
        'images' => 'array',
        'verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'rating' => 'integer',
        'helpful_count' => 'integer'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
