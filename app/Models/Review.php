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
        'review',
        'title',
        'images',
        'verified_purchase',
        'is_approved'
    ];

    protected $casts = [
        'images' => 'array',
        'verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'rating' => 'integer'
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
