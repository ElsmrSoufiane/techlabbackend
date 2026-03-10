<?php
// app/Models/Cart.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Cart extends Model
{
    protected $table = 'carts';
    
    protected $fillable = [
        'customer_id',
        'session_id'
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Helper method to get or create cart
    public static function getCart($customerId = null, $sessionId = null)
    {
        Log::info('🛒 Getting cart', [
            'customer_id' => $customerId,
            'session_id' => $sessionId
        ]);

        try {
            if ($customerId) {
                // Find or create cart for authenticated user
                $cart = self::firstOrCreate(
                    ['customer_id' => $customerId],
                    ['session_id' => null]
                );
                Log::info('🛒 Found/created cart for user', ['cart_id' => $cart->id]);
            } else {
                // Find or create cart for guest
                $cart = self::firstOrCreate(
                    ['session_id' => $sessionId],
                    ['customer_id' => null]
                );
                Log::info('🛒 Found/created cart for guest', ['cart_id' => $cart->id]);
            }
            
            // Load items with products
            $cart->load('items.product');
            
            return $cart;
            
        } catch (\Exception $e) {
            Log::error('🛒 Error in getCart: ' . $e->getMessage());
            throw $e;
        }
    }

    // Add item to cart
    public function addItem($productId, $quantity = 1, $attributes = [])
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                Log::error('🛒 Product not found', ['product_id' => $productId]);
                return false;
            }

            if ($product->stock < $quantity) {
                Log::warning('🛒 Insufficient stock', [
                    'product_id' => $productId,
                    'requested' => $quantity,
                    'available' => $product->stock
                ]);
                return false;
            }

            $attributesHash = md5(json_encode($attributes));
            
            // Find existing item in cart_items table
            $item = $this->items()
                ->where('product_id', $productId)
                ->where('attributes_hash', $attributesHash)
                ->first();

            if ($item) {
                // Check stock for total quantity
                $newQuantity = $item->quantity + $quantity;
                if ($product->stock < $newQuantity) {
                    Log::warning('🛒 Cannot add more - stock limit', [
                        'current' => $item->quantity,
                        'adding' => $quantity,
                        'max' => $product->stock
                    ]);
                    return false;
                }
                
                $item->quantity = $newQuantity;
                $item->save();
                
                Log::info('🛒 Updated existing cart item', [
                    'cart_item_id' => $item->id,
                    'new_quantity' => $newQuantity
                ]);
            } else {
                // Create new cart item
                $item = $this->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'attributes' => $attributes,
                    'attributes_hash' => $attributesHash
                ]);
                
                Log::info('🛒 Created new cart item', [
                    'cart_item_id' => $item->id
                ]);
            }

            return true;
            
        } catch (\Exception $e) {
            Log::error('🛒 Error in addItem: ' . $e->getMessage());
            return false;
        }
    }

    // Get cart summary
    public function getSummary()
    {
        $items = [];
        $total = 0;
        $count = 0;

        foreach ($this->items as $item) {
            if (!$item->product) {
                // Product doesn't exist anymore, remove from cart
                $item->delete();
                continue;
            }

            $itemTotal = $item->product->price * $item->quantity;
            $total += $itemTotal;
            $count += $item->quantity;

            $items[] = [
                'id' => $item->product->id,
                'cart_item_id' => $item->id,
                'name' => $item->product->name,
                'price' => (float) $item->product->price,
                'image' => $item->product->image,
                'slug' => $item->product->slug,
                'quantity' => $item->quantity,
                'attributes' => $item->attributes ?? [],
                'stock' => $item->product->stock,
                'total' => (float) $itemTotal
            ];
        }

        return [
            'items' => $items,
            'total' => (float) $total,
            'count' => (int) $count
        ];
    }
}