<?php
// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ecom;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\EmailNotificationController;

Route::prefix('v1')->group(function () {
    
    // ==================== PUBLIC ROUTES ====================
    $controller = Ecom::class;
    
    // Auth routes
    Route::post('/auth/register', [$controller, 'register']);
    Route::post('/auth/login', [$controller, 'login']);
    
    // Telegram webhook
    Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook'])->name('telegram.webhook');
    Route::get('/test-telegram', function() {
        $bot = new App\Http\Controllers\TelegramBotController();
        return $bot->testConnection();
    });

    // Products routes (public)
    Route::get('/products', [$controller, 'getProducts']);
    Route::get('/products/featured', [$controller, 'getFeaturedProducts']);
    Route::get('/products/{slug}', [$controller, 'getProduct']);
    
    // Brands
    Route::get('/brands', [$controller, 'getBrands']);
    
    // Categories
    Route::get('/categories', [$controller, 'getCategories']);

    // Product reviews (public)
    Route::get('/products/{productId}/reviews', [ProductController::class, 'getReviews']);

    // Coupon routes (public)
    Route::get('/coupons', [$controller, 'getCoupons']);
    Route::post('/coupons/validate', [$controller, 'validateCoupon']);

    // Contact route
    Route::post('/contact', [$controller, 'contact']);

    // Email verification
    Route::get('/verify-email/{token}', [$controller, 'verifyEmail']);

    // ==================== CART ROUTES (PUBLIC) ====================
    Route::get('/cart/debug-db', [$controller, 'debugDatabaseCart']);
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

        // Cart merge
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

        // Product reviews (authenticated)
        Route::post('/products/{productId}/reviews', [ProductController::class, 'addReview']);
        Route::put('/reviews/{reviewId}/helpful', [ProductController::class, 'markHelpful']);
    });

    // ==================== ADMIN ROUTES (AUTH + ADMIN) ====================
    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
        
        // Dashboard
        Route::get('/stats', [AdminController::class, 'getStats']);
        
        // ==================== ORDER MANAGEMENT ====================
        Route::prefix('orders')->group(function () {
            Route::get('/', [AdminController::class, 'getOrders']);
            Route::get('/{id}', [AdminController::class, 'getOrder']);
            Route::put('/{id}/status', [AdminController::class, 'updateOrderStatus']);
            Route::delete('/{id}', [AdminController::class, 'deleteOrder']);
        });
        
        // ==================== PRODUCT MANAGEMENT ====================
        Route::prefix('products')->group(function () {
            Route::get('/', [AdminController::class, 'getProducts']);
            Route::get('/low-stock', [AdminController::class, 'getLowStockProducts']);
            Route::get('/{id}', [AdminController::class, 'getProduct']);
            Route::post('/', [AdminController::class, 'createProduct']);
            Route::put('/{id}', [AdminController::class, 'updateProduct']);
            Route::delete('/{id}', [AdminController::class, 'deleteProduct']);
            Route::post('/{id}/images', [AdminController::class, 'addProductImages']);
            Route::delete('/{id}/images/{imageId}', [AdminController::class, 'deleteProductImage']);
            Route::get('/export/csv', [AdminController::class, 'exportProducts']);
        });
        
        // ==================== CATEGORY MANAGEMENT ====================
        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminController::class, 'getCategories']);
            Route::get('/{id}', [AdminController::class, 'getCategory']);
            Route::post('/', [AdminController::class, 'createCategory']);
            Route::put('/{id}', [AdminController::class, 'updateCategory']);
            Route::delete('/{id}', [AdminController::class, 'deleteCategory']);
        });
        
        // ==================== CUSTOMER MANAGEMENT ====================
        Route::prefix('customers')->group(function () {
            Route::get('/', [AdminController::class, 'getCustomers']);
            Route::get('/{id}', [AdminController::class, 'getCustomer']);
            Route::put('/{id}', [AdminController::class, 'updateCustomer']);
            Route::delete('/{id}', [AdminController::class, 'deleteCustomer']);
            Route::post('/{id}/verify-email', [AdminController::class, 'verifyCustomerEmail']);
        });
        
        // ==================== COUPON MANAGEMENT ====================
        Route::prefix('coupons')->group(function () {
            Route::get('/', [AdminController::class, 'getCoupons']);
            Route::get('/{id}', [AdminController::class, 'getCoupon']);
            Route::get('/{id}/report', [AdminController::class, 'getCouponReport']);
            Route::post('/', [AdminController::class, 'createCoupon']);
            Route::put('/{id}', [AdminController::class, 'updateCoupon']);
            Route::delete('/{id}', [AdminController::class, 'deleteCoupon']);
            Route::post('/assign', [AdminController::class, 'assignCouponToCustomer']);
        });
        
        // ==================== REVIEW MODERATION ====================
        Route::prefix('reviews')->group(function () {
            Route::get('/', [AdminController::class, 'getPendingReviews']);
            Route::put('/{reviewId}/approve', [AdminController::class, 'approveReview']);
            Route::delete('/{reviewId}', [AdminController::class, 'deleteReview']);
        });
        
        // ==================== EMAIL CAMPAIGNS ====================
        Route::prefix('emails')->group(function () {
            Route::post('/send/all', [EmailNotificationController::class, 'sendToAllCustomers']);
            Route::post('/send/selected', [EmailNotificationController::class, 'sendToSelectedCustomers']);
            Route::post('/send/active', [EmailNotificationController::class, 'sendToActiveCustomers']);
            Route::post('/send/test', [EmailNotificationController::class, 'sendTestEmail']);
            Route::get('/templates', [EmailNotificationController::class, 'getEmailTemplates']);
        });
    });
});
