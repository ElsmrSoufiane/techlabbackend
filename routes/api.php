<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ecom;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\EmailNotificationController;

Route::prefix('v1')->group(function () {
    $controller = Ecom::class;

    // ==================== PUBLIC ROUTES ====================
    
    // Auth routes
    Route::post('/auth/register', [$controller, 'register']);
    Route::post('/auth/login', [$controller, 'login']);
    
    // Telegram webhook (public)
    Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->name('telegram.webhook');

    // Products routes (public)
    Route::get('/products', [$controller, 'getProducts']);
    Route::get('/products/featured', [$controller, 'getFeaturedProducts']);
    Route::get('/categories', [$controller, 'getCategories']);
    Route::get('/brands', [$controller, 'getBrands']);
    Route::get('/products/{slug}', [$controller, 'getProduct']);

    // Coupon routes (public)
    Route::get('/coupons', [$controller, 'getCoupons']);
    Route::post('/coupons/validate', [$controller, 'validateCoupon']);

    // Contact route
    Route::post('/contact', [$controller, 'contact']);

    // Email verification
    Route::get('/verify-email/{token}', [$controller, 'verifyEmail']);

    // ==================== CART ROUTES (PUBLIC) ====================
    // DEBUG ROUTES FIRST (more specific routes before general ones)
    Route::get('/cart/debug-db', [$controller, 'debugDatabaseCart']); // Moved BEFORE /cart
    
    // Cart routes
    Route::get('/cart', [$controller, 'getCart']);
    Route::post('/cart/add', [$controller, 'addToCart']);
    Route::put('/cart/update', [$controller, 'updateCart']);
    Route::delete('/cart/remove', [$controller, 'removeFromCart']);
    Route::delete('/cart/clear', [$controller, 'clearCart']);

    // ==================== PROTECTED ROUTES (AUTH REQUIRED) ====================
    
    Route::middleware('auth:sanctum')->group(function () use ($controller) {
        // Auth
        Route::post('/auth/logout', [$controller, 'logout']);
        Route::get('/auth/me', [$controller, 'me']);
        Route::put('/auth/profile', [$controller, 'updateProfile']);
        Route::post('/resend-verification', [$controller, 'resendVerification']);

        // Cart merge (requires auth)
        Route::post('/cart/merge', [$controller, 'mergeCart']);

        // Orders
        Route::get('/orders', [$controller, 'getOrders']);
        Route::post('/orders', [$controller, 'createOrder']);
        Route::get('/orders/{id}', [$controller, 'getOrder']);
        Route::put('/orders/{id}/cancel', [$controller, 'cancelOrder']);

        // Favorites
        Route::get('/favorites', [$controller, 'getFavorites']);
        Route::post('/favorites/{productId}', [$controller, 'addToFavorites']);
        Route::delete('/favorites/{productId}', [$controller, 'removeFromFavorites']);
        Route::get('/favorites/check/{productId}', [$controller, 'checkFavorite']);
    });

    // ==================== ADMIN ROUTES ====================
    
    Route::middleware('auth:sanctum')->prefix('admin')->group(function () use ($controller) {
        // Order management
        Route::get('/orders', [$controller, 'adminGetOrders']);
        Route::put('/orders/{id}/status', [$controller, 'adminUpdateOrderStatus']);
        Route::get('/stats', [$controller, 'adminGetStats']);
        
        // Product management
        Route::post('/products', [$controller, 'adminCreateProduct']);
        Route::put('/products/{id}', [$controller, 'adminUpdateProduct']);
        Route::delete('/products/{id}', [$controller, 'adminDeleteProduct']);
        
        // Category management
        Route::post('/categories', [$controller, 'adminCreateCategory']);
        Route::put('/categories/{id}', [$controller, 'adminUpdateCategory']);
        Route::delete('/categories/{id}', [$controller, 'adminDeleteCategory']);
        
        // Customer management
        Route::get('/customers', [$controller, 'adminGetCustomers']);
        
        // Coupon management
        Route::get('/coupons', [$controller, 'adminGetCoupons']);
        Route::post('/coupons', [$controller, 'adminCreateCoupon']);
        Route::put('/coupons/{id}', [$controller, 'adminUpdateCoupon']);
        Route::delete('/coupons/{id}', [$controller, 'adminDeleteCoupon']);
        Route::post('/coupons/assign', [$controller, 'adminAssignCouponToCustomer']);
    });

    // ==================== EMAIL NOTIFICATION ROUTES (ADMIN ONLY) ====================
    
    Route::middleware('auth:sanctum')->prefix('admin/emails')->group(function () use ($controller) {
        Route::post('/send/all', [EmailNotificationController::class, 'sendToAllCustomers']);
        Route::post('/send/selected', [EmailNotificationController::class, 'sendToSelectedCustomers']);
        Route::post('/send/active', [EmailNotificationController::class, 'sendToActiveCustomers']);
        Route::post('/send/test', [EmailNotificationController::class, 'sendTestEmail']);
        Route::get('/templates', [EmailNotificationController::class, 'getEmailTemplates']);
    });
    Route::get('/test-telegram', function() {
    $bot = new App\Http\Controllers\TelegramBotController();
    return $bot->testConnection();
});
});