<?php
// app/Http/Controllers/Ecom.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Cart;
use App\Models\CartItem;
use App\Http\Controllers\TelegramBotController; 
 
class Ecom extends Controller
{
    // ==================== HELPER METHODS ====================

    private function configureGmail()
    {
        $gmailUsername = 'eemssoufiane@gmail.com';
        $gmailPassword = 'hmjdcatkbgledfhl';
        $fromName = 'TECLAB - Laboratoire Maroc';
        
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => $gmailUsername,
            'password' => $gmailPassword,
            'timeout' => 30,
        ]);
        Config::set('mail.from', [
            'address' => $gmailUsername,
            'name' => $fromName,
        ]);
        app('mail.manager')->forgetMailers();
    }

    private function authorizeAdmin(Request $request)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403, 'Accès non autorisé');
        }
    }

    private function successResponse($data, $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ], $code);
    }

    private function errorResponse($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'error' => $message
        ], $code);
    }

    /**
     * Manually authenticate user from bearer token
     */
    private function authenticateFromToken(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            Log::info('🔑 No bearer token found');
            return null;
        }
        
        // Extract token ID (format: "1|jwtTokenString")
        $tokenParts = explode('|', $token);
        $tokenId = $tokenParts[0] ?? null;
        
        if (!$tokenId || !is_numeric($tokenId)) {
            Log::info('🔑 Invalid token format', ['token' => $token]);
            return null;
        }
        
        // Find the token in personal_access_tokens table
        $accessToken = DB::table('personal_access_tokens')
            ->where('id', $tokenId)
            ->first();
        
        if (!$accessToken) {
            Log::info('🔑 Token not found in database', ['token_id' => $tokenId]);
            return null;
        }
        
        // Get the user
        $user = Customer::find($accessToken->tokenable_id);
        
        Log::info('🔑 Manual auth result:', [
            'found' => $user ? 'YES' : 'NO',
            'user_id' => $user ? $user->id : null
        ]);
        
        return $user;
    }

    /**
     * Get or create session ID for guest users
     */
    private function getSessionId(Request $request)
    {
        // First check if user is authenticated via manual method
        $user = $this->authenticateFromToken($request);
        if ($user) {
            return null; // Authenticated users don't need session
        }
        
        // For guests, get from cookie or header
        $sessionId = $request->cookie('cart_session');
        
        if (!$sessionId) {
            $sessionId = $request->header('X-Cart-Session');
        }
        
        if (!$sessionId) {
            $sessionId = Str::random(40);
            cookie()->queue('cart_session', $sessionId, 60 * 24 * 30);
            Log::info('🔑 Created new guest session', ['session_id' => $sessionId]);
        }
        
        return $sessionId;
    }

    // ==================== AUTHENTICATION METHODS ====================
    
    public function register(Request $request)
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $verificationToken = Str::random(60);

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => 'customer',
            'verification_token' => $verificationToken,
        ]);

        // Send verification email
        try {
            $this->configureGmail();
            
            $verificationUrl = url('/api/v1/verify-email/' . $verificationToken);
            
            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Vérification d'email - TECLAB</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #6d9eeb; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                    .button { display: inline-block; background: #6d9eeb; color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; margin: 20px 0; }
                    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class='header'><h1>TECLAB</h1></div>
                <div class='content'>
                    <h2>Bonjour {$customer->name} !</h2>
                    <p>Merci de vous être inscrit sur TECLAB. Veuillez vérifier votre email en cliquant sur le bouton ci-dessous :</p>
                    <div style='text-align: center;'><a href='{$verificationUrl}' class='button'>Vérifier mon email</a></div>
                    <p>Ou copiez ce lien : {$verificationUrl}</p>
                    <p>Ce lien expirera dans 24 heures.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " TECLAB. Tous droits réservés.</p>
                    <p>Rue 7 N° 184/Q4, Fès, Maroc</p>
                </div>
            </body>
            </html>";
            
            Mail::html($html, function ($message) use ($customer) {
                $message->to($customer->email)
                        ->subject('Vérification de votre email - TECLAB');
            });

            Log::info('Verification email sent', ['to' => $customer->email]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'message' => 'Inscription réussie. Veuillez vérifier votre email.',
            'customer' => $customer,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return $this->errorResponse('Email ou mot de passe incorrect', 401);
        }

        $token = $customer->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'message' => 'Connexion réussie',
            'customer' => $customer,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        if ($user) {
            $user->currentAccessToken()->delete();
        }
        return $this->successResponse('Déconnexion réussie');
    }

    public function me(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Non authentifié', 401);
        }
        return $this->successResponse($user->load('favorites'));
    }

    public function updateProfile(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $validator = validator($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:customers,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('phone')) $user->phone = $request->phone;
        if ($request->has('address')) $user->address = $request->address;

        if ($request->has('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Mot de passe actuel incorrect', 400);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return $this->successResponse([
            'message' => 'Profil mis à jour',
            'customer' => $user
        ]);
    }

    public function verifyEmail($token)
    {
        try {
            $customer = Customer::where('verification_token', $token)->first();

            if (!$customer) {
                return redirect('http://localhost:3000/verify-email/error?message=Lien de vérification invalide ou expiré');
            }

            if (!$customer->email_verified_at) {
                $customer->email_verified_at = now();
                $customer->verification_token = null;
                $customer->save();
                
                Log::info('Email verified successfully', ['customer_id' => $customer->id, 'email' => $customer->email]);
            }

            return redirect('http://localhost:3000/verify-email/success');

        } catch (\Exception $e) {
            Log::error('Email verification error: ' . $e->getMessage());
            return redirect('http://localhost:3000/verify-email/error?message=' . urlencode($e->getMessage()));
        }
    }

    public function resendVerification(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        if ($user->email_verified_at) {
            return $this->errorResponse('Email déjà vérifié', 400);
        }

        $user->verification_token = Str::random(60);
        $user->save();

        try {
            $this->configureGmail();
            
            $verificationUrl = url('/api/v1/verify-email/' . $user->verification_token);
            
            $html = "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Vérification d'email - TECLAB</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #6d9eeb; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                    .button { display: inline-block; background: #6d9eeb; color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; margin: 20px 0; }
                    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class='header'><h1>TECLAB</h1></div>
                <div class='content'>
                    <h2>Bonjour {$user->name} !</h2>
                    <p>Voici un nouveau lien pour vérifier votre email :</p>
                    <div style='text-align: center;'><a href='{$verificationUrl}' class='button'>Vérifier mon email</a></div>
                    <p>Ou copiez ce lien : {$verificationUrl}</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " TECLAB. Tous droits réservés.</p>
                </div>
            </body>
            </html>";
            
            Mail::html($html, function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Vérification de votre email - TECLAB');
            });

            return $this->successResponse('Email de vérification renvoyé avec succès');

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'envoi de l\'email', 500);
        }
    }

    public function checkVerification(Request $request)
    {
        $email = $request->input('email');

        if (!$email) {
            return $this->errorResponse('Email requis', 400);
        }

        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return $this->errorResponse('Utilisateur non trouvé', 404);
        }

        return $this->successResponse([
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'verified' => $customer->email_verified_at ? true : false,
            'verified_at' => $customer->email_verified_at
        ]);
    }

    // ==================== PRODUCT METHODS ====================

    public function getProducts(Request $request)
    {
        $query = Product::with('category');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && !empty($request->category)) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('brands') && !empty($request->brands)) {
            $brands = explode(',', $request->brands);
            $query->whereIn('brand', $brands);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('featured') && $request->featured) {
            $query->where('featured', true);
        }

        switch ($request->sort_by) {
            case 'price-asc': $query->orderBy('price', 'asc'); break;
            case 'price-desc': $query->orderBy('price', 'desc'); break;
            case 'name-asc': $query->orderBy('name', 'asc'); break;
            case 'name-desc': $query->orderBy('name', 'desc'); break;
            default: $query->orderBy('featured', 'desc')->orderBy('id', 'desc');
        }

        $perPage = $request->per_page ?? 12;
        $products = $query->paginate($perPage);

        return $this->successResponse($products);
    }

    public function getProduct(Request $request, $slug)
    {
        $product = Product::with('category')
            ->where('slug', $slug)
            ->orWhere('id', $slug)
            ->firstOrFail();

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        $user = $this->authenticateFromToken($request);
        $isFavorite = false;
        if ($user) {
            $isFavorite = $user->favorites()
                ->where('product_id', $product->id)
                ->exists();
        }

        return $this->successResponse([
            'product' => $product,
            'related' => $related,
            'is_favorite' => $isFavorite
        ]);
    }

    public function getFeaturedProducts()
    {
        $products = Product::where('featured', true)
            ->with('category')
            ->limit(8)
            ->get();

        return $this->successResponse($products);
    }

    public function getCategories()
    {
        $categories = Category::withCount('products')->get();
        return $this->successResponse($categories);
    }

    public function getBrands()
    {
        $brands = Product::distinct('brand')->whereNotNull('brand')->pluck('brand');
        return $this->successResponse($brands);
    }

    // ==================== CART METHODS ====================

    /**
     * Get current cart contents
     */
    public function getCart(Request $request)
    {
        try {
            // Manually authenticate user
            $user = $this->authenticateFromToken($request);
            $customerId = $user ? $user->id : null;
            
            Log::info('📋 [CART] Getting cart', [
                'customer_id' => $customerId,
                'is_authenticated' => $user ? 'yes' : 'no'
            ]);

            // FOR AUTHENTICATED USERS - Find by customer_id ONLY
            if ($customerId) {
                $cart = Cart::where('customer_id', $customerId)->first();
                
                if (!$cart) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'items' => [],
                            'total' => 0,
                            'count' => 0
                        ]
                    ]);
                }
                
                // Get cart items
                $items = DB::table('cart_items')
                    ->where('cart_id', $cart->id)
                    ->join('products', 'cart_items.product_id', '=', 'products.id')
                    ->select(
                        'products.id',
                        'cart_items.id as cart_item_id',
                        'products.name',
                        'products.price',
                        'products.image',
                        'products.slug',
                        'cart_items.quantity',
                        'products.stock'
                    )
                    ->get();
                
                $formattedItems = [];
                $total = 0;
                $count = 0;
                
                foreach ($items as $item) {
                    $itemTotal = $item->price * $item->quantity;
                    $total += $itemTotal;
                    $count += $item->quantity;
                    
                    $formattedItems[] = [
                        'id' => (int) $item->id,
                        'cart_item_id' => (int) $item->cart_item_id,
                        'name' => $item->name,
                        'price' => (float) $item->price,
                        'image' => $item->image,
                        'slug' => $item->slug,
                        'quantity' => (int) $item->quantity,
                        'stock' => (int) $item->stock,
                        'total' => (float) $itemTotal
                    ];
                }
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'items' => $formattedItems,
                        'total' => (float) $total,
                        'count' => (int) $count
                    ]
                ]);
            }
            
            // FOR GUESTS - Find by session_id
            $sessionId = $this->getSessionId($request);
            
            if ($sessionId) {
                $cart = Cart::where('session_id', $sessionId)->first();
                
                if ($cart) {
                    $items = DB::table('cart_items')
                        ->where('cart_id', $cart->id)
                        ->join('products', 'cart_items.product_id', '=', 'products.id')
                        ->select(
                            'products.id',
                            'cart_items.id as cart_item_id',
                            'products.name',
                            'products.price',
                            'products.image',
                            'products.slug',
                            'cart_items.quantity',
                            'products.stock'
                        )
                        ->get();
                    
                    $formattedItems = [];
                    $total = 0;
                    $count = 0;
                    
                    foreach ($items as $item) {
                        $itemTotal = $item->price * $item->quantity;
                        $total += $itemTotal;
                        $count += $item->quantity;
                        
                        $formattedItems[] = [
                            'id' => (int) $item->id,
                            'cart_item_id' => (int) $item->cart_item_id,
                            'name' => $item->name,
                            'price' => (float) $item->price,
                            'image' => $item->image,
                            'slug' => $item->slug,
                            'quantity' => (int) $item->quantity,
                            'stock' => (int) $item->stock,
                            'total' => (float) $itemTotal
                        ];
                    }
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'items' => $formattedItems,
                            'total' => (float) $total,
                            'count' => (int) $count
                        ]
                    ]);
                }
            }
            
            // No cart found
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total' => 0,
                    'count' => 0
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('📋 [CART] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du chargement du panier'
            ], 500);
        }
    }

    /**
     * Add item to cart
     */
    public function addToCart(Request $request)
    {
        try {
            Log::info('➕ [CART] Adding item', $request->all());

            $validator = validator($request->all(), [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $product = Product::find($request->product_id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Produit non trouvé'
                ], 404);
            }

            if ($product->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'error' => 'Stock insuffisant'
                ], 400);
            }

            // Manually authenticate user
            $user = $this->authenticateFromToken($request);
            $customerId = $user ? $user->id : null;
            $sessionId = $this->getSessionId($request);

            Log::info('➕ [CART] Using:', [
                'customer_id' => $customerId,
                'session_id' => $sessionId
            ]);

            // Find or create cart based on authentication
            $cart = null;
            if ($customerId) {
                // AUTHENTICATED: Use customer_id
                $cart = Cart::firstOrCreate(
                    ['customer_id' => $customerId],
                    ['session_id' => null]
                );
                Log::info('➕ [CART] Using authenticated cart', ['cart_id' => $cart->id]);
            } else {
                // GUEST: Use session_id
                $cart = Cart::firstOrCreate(
                    ['session_id' => $sessionId],
                    ['customer_id' => null]
                );
                Log::info('➕ [CART] Using guest cart', ['cart_id' => $cart->id]);
            }

            // Check if item exists
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $request->product_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $request->quantity;
                $existingItem->save();
                Log::info('➕ [CART] Updated existing item', ['item_id' => $existingItem->id]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'attributes' => '[]',
                    'attributes_hash' => md5('[]')
                ]);
                Log::info('➕ [CART] Created new item');
            }

            return $this->getCart($request);

        } catch (\Exception $e) {
            Log::error('➕ [CART] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'ajout au panier'
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateCart(Request $request)
    {
        try {
            Log::info('🔄 [CART] Updating cart', $request->all());

            $validator = validator($request->all(), [
                'cart_item_id' => 'required|exists:cart_items,id',
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            $cartItem = CartItem::find($request->cart_item_id);
            $cartItem->quantity = $request->quantity;
            $cartItem->save();

            return $this->getCart($request);

        } catch (\Exception $e) {
            Log::error('🔄 [CART] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(Request $request)
    {
        try {
            Log::info('❌ [CART] Removing from cart', $request->all());

            $validator = validator($request->all(), [
                'cart_item_id' => 'required|exists:cart_items,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 422);
            }

            CartItem::where('id', $request->cart_item_id)->delete();

            return $this->getCart($request);

        } catch (\Exception $e) {
            Log::error('❌ [CART] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du retrait'
            ], 500);
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart(Request $request)
    {
        try {
            Log::info('🗑️ [CART] Clearing cart');

            $user = $this->authenticateFromToken($request);
            $customerId = $user ? $user->id : null;
            $sessionId = $this->getSessionId($request);

            $cart = null;
            if ($customerId) {
                $cart = Cart::where('customer_id', $customerId)->first();
            } else {
                $cart = Cart::where('session_id', $sessionId)->first();
            }

            if ($cart) {
                $cart->items()->delete();
                $cart->delete();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total' => 0,
                    'count' => 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('🗑️ [CART] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors du vidage du panier'
            ], 500);
        }
    }

    /**
     * Merge guest cart with user cart after login
     */
    public function mergeCart(Request $request)
    {
        try {
            $user = $this->authenticateFromToken($request);
            if (!$user) {
                return $this->errorResponse('Authentification requise', 401);
            }
            
            $sessionId = $request->input('session_id');
            if (!$sessionId) {
                return $this->successResponse(['message' => 'No session to merge']);
            }
            
            Log::info('🔄 [CART] Merging cart for user', [
                'user_id' => $user->id,
                'session_id' => $sessionId
            ]);
            
            $guestCart = Cart::where('session_id', $sessionId)->first();
            
            if (!$guestCart) {
                return $this->successResponse(['message' => 'No guest cart to merge']);
            }
            
            $userCart = Cart::firstOrCreate(
                ['customer_id' => $user->id],
                ['session_id' => null]
            );
            
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->first();
                    
                if ($existingItem) {
                    $existingItem->quantity += $guestItem->quantity;
                    $existingItem->save();
                    $guestItem->delete();
                } else {
                    $guestItem->cart_id = $userCart->id;
                    $guestItem->save();
                }
            }
            
            if ($guestCart->items()->count() == 0) {
                $guestCart->delete();
            }
            
            return $this->successResponse(['message' => 'Cart merged successfully']);
            
        } catch (\Exception $e) {
            Log::error('🔄 [CART] Merge error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la fusion du panier', 500);
        }
    }

    /**
     * Debug endpoint
     */
    public function debugDatabaseCart(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        $customerId = $user ? $user->id : null;
        $sessionId = $this->getSessionId($request);
        
        $carts = DB::table('carts')->get();
        $cartItems = DB::table('cart_items')->get();
        
        $cart = null;
        if ($customerId) {
            $cart = DB::table('carts')->where('customer_id', $customerId)->first();
        } else {
            $cart = DB::table('carts')->where('session_id', $sessionId)->first();
        }
        
        $items = [];
        if ($cart) {
            $items = DB::table('cart_items')
                ->where('cart_id', $cart->id)
                ->join('products', 'cart_items.product_id', '=', 'products.id')
                ->select('cart_items.*', 'products.name', 'products.price', 'products.image', 'products.slug')
                ->get();
        }
        
        return response()->json([
            'success' => true,
            'debug' => [
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'all_carts' => $carts,
                'all_cart_items' => $cartItems,
                'found_cart' => $cart,
                'cart_items' => $items,
                'items_count' => count($items)
            ]
        ]);
    }

    // ==================== FAVORITES METHODS ====================

    public function getFavorites(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $favorites = $user->favorites()->with('category')->paginate(12);
        return $this->successResponse($favorites);
    }

    public function addToFavorites(Request $request, $productId)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $product = Product::findOrFail($productId);
        $user->favorites()->syncWithoutDetaching([$productId]);

        return $this->successResponse([
            'message' => 'Produit ajouté aux favoris',
            'favorite' => $product
        ]);
    }

    public function removeFromFavorites(Request $request, $productId)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $user->favorites()->detach($productId);
        return $this->successResponse('Produit retiré des favoris');
    }

    public function checkFavorite(Request $request, $productId)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $isFavorite = $user->favorites()
            ->where('product_id', $productId)
            ->exists();

        return $this->successResponse(['is_favorite' => $isFavorite]);
    }

    // ==================== ORDER METHODS ====================

    public function getOrders(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $orders = $user->orders()
            ->with('items', 'coupon')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->successResponse($orders);
    }

   /**
 * Create a new order
 */
public function createOrder(Request $request)
{
    $user = $this->authenticateFromToken($request);
    if (!$user) {
        return $this->errorResponse('Authentification requise', 401);
    }

    $validator = validator($request->all(), [
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'shipping_address' => 'required|string',
        'payment_method' => 'required|in:carte,espèces',
        'coupon_code' => 'nullable|string|exists:coupons,code',
    ]);

    if ($validator->fails()) {
        return $this->errorResponse($validator->errors()->first(), 422);
    }

    try {
        DB::beginTransaction();

        $subtotal = 0;
        $orderItems = [];

        // Calculate subtotal and prepare items
        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            
            if ($product->stock < $item['quantity']) {
                return $this->errorResponse("Stock insuffisant pour {$product->name}", 400);
            }

            $itemTotal = $product->price * $item['quantity'];
            $subtotal += $itemTotal;

            $orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'attributes' => json_encode($item['attributes'] ?? []),
            ];

            // Decrement stock
            $product->decrement('stock', $item['quantity']);
        }

        // Apply coupon if provided
        $discountAmount = 0;
        $couponId = null;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();
            
            if ($coupon && $coupon->isValid($user->id, $subtotal)) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
                $couponId = $coupon->id;
                
                // Mark coupon as used for this customer
                if ($coupon->customers()->where('customer_id', $user->id)->exists()) {
                    $coupon->customers()->updateExistingPivot($user->id, [
                        'is_used' => true,
                        'used_at' => now()
                    ]);
                }
                
                $coupon->increment('used_count');
            }
        }

        // Calculate totals
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $shipping = $subtotalAfterDiscount > 1000 ? 0 : 50;
        $tax = $subtotalAfterDiscount * 0.20; // 20% TVA
        $total = $subtotalAfterDiscount + $shipping + $tax;

        // Create order
        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_id' => $user->id,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'status' => 'en cours',
            'shipping_address' => $request->shipping_address,
            'payment_method' => $request->payment_method,
            'coupon_id' => $couponId,
        ]);

        // Create order items
        foreach ($orderItems as $item) {
            $item['order_id'] = $order->id;
            OrderItem::create($item);
        }

        // Clear user's cart
        $cart = Cart::where('customer_id', $user->id)->first();
        if ($cart) {
            $cart->items()->delete();
            $cart->delete();
        }

        DB::commit();

        // ========== SEND TELEGRAM NOTIFICATION ==========
        try {
            $telegramBot = new \App\Http\Controllers\TelegramBotController();
            
            $message = "🛍️ <b>NOUVELLE COMMANDE</b>\n\n";
            $message .= "📦 <b>Commande #{$order->order_number}</b>\n";
            $message .= "👤 <b>Client:</b> {$user->name}\n";
            $message .= "📧 <b>Email:</b> {$user->email}\n";
            $message .= "📞 <b>Téléphone:</b> " . ($user->phone ?? 'Non renseigné') . "\n";
            $message .= "📍 <b>Adresse:</b> {$request->shipping_address}\n";
            $message .= "💰 <b>Total:</b> {$total} MAD\n";
            $message .= "💳 <b>Paiement:</b> " . ($request->payment_method === 'carte' ? 'Carte' : 'Espèces (COD)') . "\n\n";
            $message .= "📋 <b>Articles:</b>\n";
            
            foreach ($orderItems as $item) {
                $message .= "• {$item['product_name']} x{$item['quantity']} - " . ($item['price'] * $item['quantity']) . " MAD\n";
            }
            
            if ($discountAmount > 0) {
                $message .= "\n💰 <b>Réduction:</b> -{$discountAmount} MAD";
            }
            
            // Send to admin chat
            $telegramBot->sendMessage('-5051267768', $message);
            
            Log::info('Telegram notification sent for order', ['order_id' => $order->id]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification: ' . $e->getMessage());
        }

        // Return success response
        return $this->successResponse([
            'message' => 'Commande créée avec succès',
            'order' => $order->load('items', 'coupon')
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Order creation error: ' . $e->getMessage());
        Log::error('Order creation trace: ' . $e->getTraceAsString());
        return $this->errorResponse('Erreur lors de la création de la commande: ' . $e->getMessage(), 500);
    }
}
    public function getOrder(Request $request, $id)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $order = Order::with(['items', 'customer', 'coupon'])
            ->where('id', $id)
            ->where('customer_id', $user->id)
            ->firstOrFail();

        return $this->successResponse($order);
    }

    public function cancelOrder(Request $request, $id)
    {
        $user = $this->authenticateFromToken($request);
        if (!$user) {
            return $this->errorResponse('Authentification requise', 401);
        }

        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->firstOrFail();

        if ($order->status !== 'en cours') {
            return $this->errorResponse('Cette commande ne peut pas être annulée', 400);
        }

        try {
            DB::beginTransaction();

            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'annulée']);
            DB::commit();

            return $this->successResponse('Commande annulée avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erreur lors de l\'annulation', 500);
        }
    }

    // ==================== COUPON METHODS ====================

    public function getCoupons(Request $request)
    {
        $user = $this->authenticateFromToken($request);
        
        $query = Coupon::where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });

        if ($user) {
            $query->where(function($q) use ($user) {
                $q->where('is_public', true)
                  ->orWhere('customer_id', $user->id);
            });
        } else {
            $query->where('is_public', true);
        }

        $coupons = $query->get();
        return $this->successResponse($coupons);
    }

    public function validateCoupon(Request $request)
    {
        $validator = validator($request->all(), [
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return $this->errorResponse('Code promo invalide', 404);
        }

        $user = $this->authenticateFromToken($request);
        $customerId = $user ? $user->id : null;

        if (!$coupon->isValid($customerId, $request->order_amount)) {
            return $this->errorResponse('Ce code promo n\'est pas valide', 400);
        }

        $discount = $coupon->calculateDiscount($request->order_amount);

        return $this->successResponse([
            'coupon' => $coupon,
            'discount' => $discount,
            'new_total' => $request->order_amount - $discount
        ]);
    }

    // ==================== CONTACT METHOD ====================

    public function contact(Request $request)
    {
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $this->configureGmail();
            
            $admins = Customer::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $contactHtml = "
                <!DOCTYPE html>
                <html>
                <head><title>Nouveau message de contact - TECLAB</title></head>
                <body>
                    <h2>Nouveau message de contact</h2>
                    <p><strong>Nom:</strong> {$request->name}</p>
                    <p><strong>Email:</strong> {$request->email}</p>
                    <p><strong>Sujet:</strong> {$request->subject}</p>
                    <p><strong>Message:</strong></p>
                    <p>{$request->message}</p>
                </body>
                </html>";
                
                Mail::html($contactHtml, function ($message) use ($admin) {
                    $message->to($admin->email)
                            ->subject('Nouveau message de contact - TECLAB');
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to send contact email: ' . $e->getMessage());
        }

        return $this->successResponse('Message envoyé avec succès');
    }

    // ==================== ADMIN METHODS ====================

    public function adminGetOrders(Request $request)
    {
        $this->authorizeAdmin($request);
        $orders = Order::with(['customer', 'items', 'coupon'])->orderBy('created_at', 'desc')->paginate(20);
        return $this->successResponse($orders);
    }

    public function adminUpdateOrderStatus(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $validator = validator($request->all(), [
            'status' => 'required|in:en cours,expédiée,livré,annulée'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        return $this->successResponse([
            'message' => 'Statut mis à jour',
            'order' => $order
        ]);
    }

    public function adminGetStats(Request $request)
    {
        $this->authorizeAdmin($request);
        $stats = [
            'total_orders' => Order::count(),
            'total_revenue' => Order::sum('total'),
            'pending_orders' => Order::where('status', 'en cours')->count(),
            'completed_orders' => Order::where('status', 'livré')->count(),
            'total_customers' => Customer::count(),
            'total_products' => Product::count(),
            'recent_orders' => Order::with('customer')->latest()->limit(5)->get()
        ];

        return $this->successResponse($stats);
    }

    public function adminCreateProduct(Request $request)
    {
        $this->authorizeAdmin($request);
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'required|string',
            'image' => 'required|string',
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $product = Product::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'sku' => $request->sku,
            'price' => $request->price,
            'category_id' => $request->category_id,
            'brand' => $request->brand,
            'image' => $request->image,
            'stock' => $request->stock,
        ]);

        return $this->successResponse([
            'message' => 'Produit créé avec succès',
            'product' => $product
        ], 201);
    }

    public function adminUpdateProduct(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $product = Product::findOrFail($id);
        $product->update($request->all());
        return $this->successResponse([
            'message' => 'Produit mis à jour',
            'product' => $product
        ]);
    }

    public function adminDeleteProduct(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $product = Product::findOrFail($id);
        $product->delete();
        return $this->successResponse('Produit supprimé avec succès');
    }

    public function adminGetCustomers(Request $request)
    {
        $this->authorizeAdmin($request);
        $customers = Customer::withCount('orders')->paginate(20);
        return $this->successResponse($customers);
    }

    public function adminGetCoupons(Request $request)
    {
        $this->authorizeAdmin($request);
        $coupons = Coupon::with('customer')->paginate(20);
        return $this->successResponse($coupons);
    }

    public function adminCreateCoupon(Request $request)
    {
        $this->authorizeAdmin($request);
        $validator = validator($request->all(), [
            'code' => 'required|string|unique:coupons',
            'name' => 'required|string|max:255',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $coupon = Coupon::create($request->all());
        return $this->successResponse([
            'message' => 'Coupon créé avec succès',
            'coupon' => $coupon
        ], 201);
    }

    public function adminUpdateCoupon(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->all());
        return $this->successResponse([
            'message' => 'Coupon mis à jour',
            'coupon' => $coupon
        ]);
    }

    public function adminDeleteCoupon(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        return $this->successResponse('Coupon supprimé avec succès');
    }

    public function adminCreateCategory(Request $request)
    {
        $this->authorizeAdmin($request);
        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return $this->successResponse([
            'message' => 'Catégorie créée avec succès',
            'category' => $category
        ], 201);
    }

    public function adminUpdateCategory(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $category = Category::findOrFail($id);
        $category->update($request->all());
        return $this->successResponse([
            'message' => 'Catégorie mise à jour',
            'category' => $category
        ]);
    }

    public function adminDeleteCategory(Request $request, $id)
    {
        $this->authorizeAdmin($request);
        $category = Category::findOrFail($id);
        if ($category->products()->count() > 0) {
            return $this->errorResponse('Impossible de supprimer une catégorie qui contient des produits', 400);
        }
        $category->delete();
        return $this->successResponse('Catégorie supprimée avec succès');
    }
}