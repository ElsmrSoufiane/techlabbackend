<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'sku', 'price', 'original_price', 'category_id',
        'brand', 'image', 'description', 'features', 'attributes',
        'stock', 'rating', 'reviews_count', 'badge', 'featured'
    ];

    protected $casts = [
        'features' => 'array',
        'attributes' => 'array',
        'featured' => 'boolean',
        'price' => 'float',
        'original_price' => 'float',
        'stock' => 'integer',
        'rating' => 'float'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function getPrimaryImageAttribute()
    {
        $primary = $this->images->where('is_primary', true)->first();
        return $primary ? $primary->image_path : $this->image;
    }

    public function getAllImagesAttribute()
    {
        if ($this->images->count() > 0) {
            return $this->images->pluck('image_path')->toArray();
        }
        return [$this->image];
    }
}