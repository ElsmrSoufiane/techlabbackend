<?php
// app/Http/Controllers/AdminController.php

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
use App\Models\ProductImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Cart;
use App\Models\CartItem;

class AdminController extends Controller
{
    // ==================== HELPER METHODS ====================

    private function authorizeAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Accès non autorisé');
        }
        return $user;
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

    // ==================== DASHBOARD STATS ====================

    public function getStats(Request $request)
    {
        $this->authorizeAdmin($request);

        try {
            $stats = [
                'total_orders' => Order::count(),
                'total_revenue' => Order::sum('total'),
                'pending_orders' => Order::where('status', 'en cours')->count(),
                'completed_orders' => Order::where('status', 'livré')->count(),
                'total_customers' => Customer::count(),
                'total_products' => Product::count(),
                'low_stock_products' => Product::where('stock', '<', 10)->count(),
                'total_categories' => Category::count(),
                'total_coupons' => Coupon::count(),
                'revenue_today' => Order::whereDate('created_at', today())->sum('total'),
                'revenue_month' => Order::whereMonth('created_at', now()->month)->sum('total'),
            ];

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            Log::error('Admin stats error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du chargement des statistiques', 500);
        }
    }

    // ==================== ORDER MANAGEMENT ====================

    public function getOrders(Request $request)
    {
        $this->authorizeAdmin($request);

        try {
            $query = Order::with(['customer', 'items', 'coupon']);

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('order_number', 'like', '%' . $request->search . '%')
                      ->orWhereHas('customer', function($cq) use ($request) {
                          $cq->where('name', 'like', '%' . $request->search . '%')
                             ->orWhere('email', 'like', '%' . $request->search . '%');
                      });
                });
            }

            $sortField = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortField, $sortOrder);

            $perPage = $request->per_page ?? 20;
            $orders = $query->paginate($perPage);

            return $this->successResponse($orders);
        } catch (\Exception $e) {
            Log::error('Admin orders error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du chargement des commandes', 500);
        }
    }

    public function getOrder(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $order = Order::with(['customer', 'items', 'coupon'])
                ->findOrFail($id);

            return $this->successResponse($order);
        } catch (\Exception $e) {
            Log::error('Admin order error: ' . $e->getMessage());
            return $this->errorResponse('Commande non trouvée', 404);
        }
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'status' => 'required|in:en cours,expédiée,livré,annulée'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $order = Order::findOrFail($id);
            $order->status = $request->status;
            $order->save();

            return $this->successResponse([
                'message' => 'Statut mis à jour avec succès',
                'order' => $order->load('customer', 'items', 'coupon')
            ]);
        } catch (\Exception $e) {
            Log::error('Admin update order status error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour', 500);
        }
    }

    public function deleteOrder(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $order = Order::findOrFail($id);
            
            if (!in_array($order->status, ['annulée', 'livré'])) {
                return $this->errorResponse('Seules les commandes annulées ou livrées peuvent être supprimées', 400);
            }

            $order->items()->delete();
            $order->delete();

            return $this->successResponse('Commande supprimée avec succès');
        } catch (\Exception $e) {
            Log::error('Admin delete order error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression', 500);
        }
    }

    // ==================== PRODUCT MANAGEMENT ====================

   /**
 * Get all products with images
 */
public function getProducts(Request $request)
{
    $this->authorizeAdmin($request);

    try {
        $query = Product::with(['category', 'images']);

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%')
                  ->orWhere('brand', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by category
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Sort
        $sortField = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        $perPage = $request->per_page ?? 20;
        $products = $query->paginate($perPage);

        // Transformer pour ajouter images_array et formater les données
        $products->getCollection()->transform(function ($product) {
            // S'assurer que les données de base existent
            $productData = [
                'id' => $product->id,
                'name' => $product->name ?? '',
                'sku' => $product->sku ?? '',
                'price' => $product->price ?? 0,
                'original_price' => $product->original_price ?? null,
                'stock' => $product->stock ?? 0,
                'brand' => $product->brand ?? '',
                'category_id' => $product->category_id,
                'category' => $product->category,
                'featured' => $product->featured ?? false,
                'badge' => $product->badge ?? '',
                'description' => $product->description ?? '',
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
            
            // Gestion des images - FORMAT STANDARDISÉ
            $images = [];
            
            // Récupérer depuis la relation images
            if ($product->images && $product->images->count() > 0) {
                foreach ($product->images as $img) {
                    $images[] = [
                        'id' => $img->id,
                        'image_path' => $img->image_path,
                        'is_primary' => $img->is_primary,
                        'sort_order' => $img->sort_order
                    ];
                }
            }
            
            // Ajouter l'image principale si elle existe
            $mainImage = $product->image;
            if (empty($images) && $mainImage) {
                $images[] = [
                    'id' => null,
                    'image_path' => $mainImage,
                    'is_primary' => true,
                    'sort_order' => 0
                ];
            }
            
            // Trier par sort_order
            usort($images, function($a, $b) {
                return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
            });
            
            // Ajouter les données formatées
            $productData['images'] = $images;
            $productData['image'] = !empty($images) ? $images[0]['image_path'] : ($product->image ?? 'https://via.placeholder.com/300');
            $productData['images_array'] = array_column($images, 'image_path');
            $productData['images_count'] = count($images);
            
            return (object) $productData;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
        
    } catch (\Exception $e) {
        Log::error('Admin get products error: ' . $e->getMessage());
        Log::error('Admin get products trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'error' => 'Erreur lors du chargement des produits: ' . $e->getMessage()
        ], 500);
    }
}
    public function getLowStockProducts(Request $request)
    {
        $this->authorizeAdmin($request);

        try {
            $products = Product::with('category')
                ->where('stock', '<', 10)
                ->orderBy('stock', 'asc')
                ->paginate($request->per_page ?? 20);

            return $this->successResponse($products);
        } catch (\Exception $e) {
            Log::error('Admin low stock error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du chargement', 500);
        }
    }

    public function getProduct(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $product = Product::with(['category', 'images'])->findOrFail($id);
            $product->images_array = $product->images->pluck('image_path')->toArray();

            return $this->successResponse($product);
        } catch (\Exception $e) {
            Log::error('Admin get product error: ' . $e->getMessage());
            return $this->errorResponse('Produit non trouvé', 404);
        }
    }

    public function createProduct(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'badge' => 'nullable|string',
            'featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            // Déterminer l'image principale
            $mainImage = $request->image;
            if (!$mainImage && $request->has('images') && count($request->images) > 0) {
                $mainImage = $request->images[0];
            }
            if (!$mainImage) {
                $mainImage = 'https://via.placeholder.com/300';
            }

            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'sku' => $request->sku,
                'price' => $request->price,
                'original_price' => $request->original_price ?? $request->price,
                'category_id' => $request->category_id,
                'brand' => $request->brand,
                'image' => $mainImage,
                'description' => $request->description,
                'features' => $request->features,
                'stock' => $request->stock,
                'badge' => $request->badge,
                'featured' => $request->featured ?? false,
            ]);

            // Ajouter les images multiples
            if ($request->has('images') && is_array($request->images)) {
                foreach ($request->images as $index => $imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'sort_order' => $index,
                        'is_primary' => $index === 0
                    ]);
                }
            }

            DB::commit();

            return $this->successResponse([
                'message' => 'Produit créé avec succès',
                'product' => $product->load('category', 'images')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin create product error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la création: ' . $e->getMessage(), 500);
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $product = Product::findOrFail($id);

        $validator = validator($request->all(), [
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|unique:products,sku,' . $id,
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'brand' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'stock' => 'sometimes|integer|min:0',
            'description' => 'nullable|string',
            'features' => 'nullable|array',
            'badge' => 'nullable|string',
            'featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            
            if ($request->has('name') && $request->name !== $product->name) {
                $data['slug'] = Str::slug($request->name);
            }

            // Mettre à jour l'image principale si des images sont fournies
            if ($request->has('images') && is_array($request->images) && count($request->images) > 0) {
                $data['image'] = $request->images[0];
                
                // Supprimer les anciennes images
                $product->images()->delete();
                
                // Ajouter les nouvelles images
                foreach ($request->images as $index => $imagePath) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'sort_order' => $index,
                        'is_primary' => $index === 0
                    ]);
                }
            }

            $product->update($data);

            DB::commit();

            return $this->successResponse([
                'message' => 'Produit mis à jour avec succès',
                'product' => $product->load('category', 'images')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin update product error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour: ' . $e->getMessage(), 500);
        }
    }

    public function deleteProduct(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $product = Product::findOrFail($id);
            
            $orderCount = OrderItem::where('product_id', $id)->count();
            if ($orderCount > 0) {
                return $this->errorResponse(
                    "Ce produit a $orderCount commande(s) associée(s). Impossible de le supprimer.",
                    400
                );
            }

            DB::beginTransaction();

            DB::table('favorites')->where('product_id', $id)->delete();
            CartItem::where('product_id', $id)->delete();
            $product->images()->delete();
            $product->delete();

            DB::commit();

            return $this->successResponse('Produit supprimé avec succès');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin delete product error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression', 500);
        }
    }

    public function addProductImages(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'images' => 'required|array',
            'images.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $product = Product::findOrFail($id);
            
            $currentCount = $product->images()->count();

            foreach ($request->images as $index => $imagePath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imagePath,
                    'sort_order' => $currentCount + $index,
                    'is_primary' => $currentCount === 0 && $index === 0
                ]);
            }

            $product->load('images');
            $product->images_array = $product->images->pluck('image_path')->toArray();

            return $this->successResponse([
                'message' => 'Images ajoutées avec succès',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Admin add images error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'ajout des images', 500);
        }
    }

    public function deleteProductImage(Request $request, $id, $imageId)
    {
        $this->authorizeAdmin($request);

        try {
            $product = Product::findOrFail($id);
            $image = ProductImage::where('product_id', $id)
                ->where('id', $imageId)
                ->firstOrFail();

            // Si c'était l'image principale, réassigner
            if ($image->is_primary) {
                $firstImage = $product->images()
                    ->where('id', '!=', $imageId)
                    ->orderBy('sort_order')
                    ->first();
                
                if ($firstImage) {
                    $firstImage->is_primary = true;
                    $firstImage->save();
                    
                    $product->image = $firstImage->image_path;
                    $product->save();
                } else {
                    $product->image = null;
                    $product->save();
                }
            }

            $image->delete();

            // Réorganiser les sort_order
            $remainingImages = $product->images()->orderBy('sort_order')->get();
            foreach ($remainingImages as $index => $img) {
                $img->sort_order = $index;
                $img->save();
            }

            $product->load('images');
            $product->images_array = $product->images->pluck('image_path')->toArray();

            return $this->successResponse([
                'message' => 'Image supprimée avec succès',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Admin delete image error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression', 500);
        }
    }

    // ==================== CATEGORY MANAGEMENT ====================

    public function getCategories(Request $request)
    {
        $this->authorizeAdmin($request);

        try {
            $categories = Category::withCount('products')
                ->orderBy('name')
                ->get();

            return $this->successResponse($categories);
        } catch (\Exception $e) {
            Log::error('Admin categories error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du chargement', 500);
        }
    }

    public function getCategory(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $category = Category::withCount('products')->findOrFail($id);
            return $this->successResponse($category);
        } catch (\Exception $e) {
            Log::error('Admin get category error: ' . $e->getMessage());
            return $this->errorResponse('Catégorie non trouvée', 404);
        }
    }

    public function createCategory(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'name' => 'required|string|max:255|unique:categories',
            'color' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $category = Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'color' => $request->color,
                'image' => $request->image,
            ]);

            return $this->successResponse([
                'message' => 'Catégorie créée avec succès',
                'category' => $category
            ], 201);
        } catch (\Exception $e) {
            Log::error('Admin create category error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la création', 500);
        }
    }

    public function updateCategory(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $category = Category::findOrFail($id);

        $validator = validator($request->all(), [
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'color' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $data = $request->all();
            
            if ($request->has('name') && $request->name !== $category->name) {
                $data['slug'] = Str::slug($request->name);
            }

            $category->update($data);

            return $this->successResponse([
                'message' => 'Catégorie mise à jour avec succès',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            Log::error('Admin update category error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour', 500);
        }
    }

    public function deleteCategory(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $category = Category::findOrFail($id);
            
            $productCount = $category->products()->count();
            if ($productCount > 0) {
                return $this->errorResponse(
                    "Cette catégorie contient $productCount produit(s). Impossible de la supprimer.",
                    400
                );
            }
            
            $category->delete();
            
            return $this->successResponse('Catégorie supprimée avec succès');
            
        } catch (\Exception $e) {
            Log::error('Admin delete category error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression', 500);
        }
    }

    // ==================== CUSTOMER MANAGEMENT ====================

    public function getCustomers(Request $request)
    {
        $this->authorizeAdmin($request);

        try {
            $query = Customer::withCount('orders');

            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%')
                      ->orWhere('phone', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->tier) {
                $query->where('tier', $request->tier);
            }

            if ($request->verified !== null) {
                if ($request->verified) {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            }

            $sortField = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortField, $sortOrder);

            $perPage = $request->per_page ?? 20;
            $customers = $query->paginate($perPage);

            return $this->successResponse($customers);
        } catch (\Exception $e) {
            Log::error('Admin customers error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du chargement', 500);
        }
    }

    public function getCustomer(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $customer = Customer::with(['orders' => function($q) {
                $q->latest()->limit(10);
            }])->withCount('orders')->findOrFail($id);

            $customer->favorites_count = $customer->favorites()->count();
            $customer->coupons_count = $customer->coupons()->count();

            return $this->successResponse($customer);
        } catch (\Exception $e) {
            Log::error('Admin get customer error: ' . $e->getMessage());
            return $this->errorResponse('Client non trouvé', 404);
        }
    }

    public function updateCustomer(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $customer = Customer::findOrFail($id);

        $validator = validator($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:customers,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'tier' => 'sometimes|in:regular,pro',
            'pro_discount' => 'required_if:tier,pro|nullable|integer|min:0|max:100',
            'company_name' => 'nullable|string|max:255',
            'role' => 'sometimes|in:admin,customer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $customer->update($request->all());

            return $this->successResponse([
                'message' => 'Client mis à jour avec succès',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Admin update customer error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour', 500);
        }
    }

    public function deleteCustomer(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $customer = Customer::findOrFail($id);
            
            if ($customer->orders()->count() > 0) {
                return $this->errorResponse('Impossible de supprimer un client qui a des commandes', 400);
            }

            $customer->delete();

            return $this->successResponse('Client supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('Admin delete customer error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression', 500);
        }
    }

    public function verifyCustomerEmail(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $customer = Customer::findOrFail($id);
            
            if ($customer->email_verified_at) {
                return $this->errorResponse('Email déjà vérifié', 400);
            }

            $customer->email_verified_at = now();
            $customer->verification_token = null;
            $customer->save();

            return $this->successResponse([
                'message' => 'Email vérifié avec succès',
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Admin verify email error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la vérification', 500);
        }
    }

    // ==================== COUPON MANAGEMENT ====================

    public function getCoupons(Request $request)
    {
        $this->authorizeAdmin($request);

        try {
            $query = Coupon::with('customer');

            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('code', 'like', '%' . $request->search . '%')
                      ->orWhere('name', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->is_active !== null) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->type) {
                $query->where('type', $request->type);
            }

            $perPage = $request->per_page ?? 20;
            $coupons = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successResponse($coupons);
        } catch (\Exception $e) {
            Log::error('Admin coupons error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du chargement', 500);
        }
    }

    public function getCoupon(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $coupon = Coupon::with('customer')->findOrFail($id);
            return $this->successResponse($coupon);
        } catch (\Exception $e) {
            Log::error('Admin get coupon error: ' . $e->getMessage());
            return $this->errorResponse('Coupon non trouvé', 404);
        }
    }

    public function createCoupon(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'code' => 'required|string|unique:coupons|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'is_public' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $coupon = Coupon::create($request->all());

            if ($request->customer_id) {
                $coupon->customers()->attach($request->customer_id, [
                    'is_used' => false,
                    'used_at' => null
                ]);
            }

            return $this->successResponse([
                'message' => 'Coupon créé avec succès',
                'coupon' => $coupon->load('customer')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Admin create coupon error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la création', 500);
        }
    }

    public function updateCoupon(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        $coupon = Coupon::findOrFail($id);

        $validator = validator($request->all(), [
            'code' => 'sometimes|string|unique:coupons,code,' . $id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:fixed,percentage',
            'value' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'is_public' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $coupon->update($request->all());

            if ($request->has('customer_id')) {
                $coupon->customers()->detach();
                
                if ($request->customer_id) {
                    $coupon->customers()->attach($request->customer_id, [
                        'is_used' => false,
                        'used_at' => null
                    ]);
                }
            }

            return $this->successResponse([
                'message' => 'Coupon mis à jour avec succès',
                'coupon' => $coupon->load('customer')
            ]);
        } catch (\Exception $e) {
            Log::error('Admin update coupon error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la mise à jour', 500);
        }
    }

    public function deleteCoupon(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $coupon = Coupon::findOrFail($id);
            
            if ($coupon->orders()->count() > 0) {
                $coupon->update(['is_active' => false]);
                return $this->successResponse('Coupon désactivé (utilisé dans des commandes)');
            }

            $coupon->customers()->detach();
            $coupon->delete();

            return $this->successResponse('Coupon supprimé avec succès');
        } catch (\Exception $e) {
            Log::error('Admin delete coupon error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression', 500);
        }
    }

    public function assignCouponToCustomer(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'coupon_id' => 'required|exists:coupons,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $coupon = Coupon::findOrFail($request->coupon_id);
            $customer = Customer::findOrFail($request->customer_id);

            if ($coupon->customers()->where('customer_id', $customer->id)->exists()) {
                return $this->errorResponse('Coupon déjà assigné à ce client', 400);
            }

            $coupon->customers()->attach($customer->id, [
                'is_used' => false,
                'used_at' => null
            ]);

            $coupon->customer_id = $customer->id;
            $coupon->is_public = false;
            $coupon->save();

            return $this->successResponse([
                'message' => 'Coupon assigné au client avec succès',
                'coupon' => $coupon->load('customer')
            ]);
        } catch (\Exception $e) {
            Log::error('Admin assign coupon error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'assignation', 500);
        }
    }

    public function getCouponReport(Request $request, $id)
    {
        $this->authorizeAdmin($request);

        try {
            $coupon = Coupon::with(['orders' => function($q) {
                $q->with('customer')->latest()->limit(50);
            }])->findOrFail($id);

            $report = [
                'coupon' => $coupon,
                'total_uses' => $coupon->used_count,
                'total_discount' => $coupon->orders()->sum('discount_amount'),
                'recent_orders' => $coupon->orders,
                'assigned_customers' => $coupon->customers()->count(),
            ];

            return $this->successResponse($report);
        } catch (\Exception $e) {
            Log::error('Admin coupon report error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du chargement du rapport', 500);
        }
    }

    // ==================== EXPORT ====================

    public function exportProducts(Request $request)
    {
        $this->authorizeAdmin($request);

        try {
            $products = Product::with('category')->get();

            $csvData = [];
            $csvData[] = ['ID', 'Nom', 'SKU', 'Prix', 'Stock', 'Catégorie', 'Marque', 'Description'];

            foreach ($products as $product) {
                $csvData[] = [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->price,
                    $product->stock,
                    $product->category->name ?? '',
                    $product->brand,
                    strip_tags($product->description)
                ];
            }

            $filename = 'products_export_' . date('Y-m-d') . '.csv';
            $handle = fopen('php://temp', 'w');
            
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            
            rewind($handle);
            $content = stream_get_contents($handle);
            fclose($handle);

            return response($content)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            Log::error('Admin export error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'export', 500);
        }
    }
}
