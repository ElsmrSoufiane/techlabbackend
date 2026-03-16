<?php
// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductReview;
use Illuminate\Support\Facades\DB;
class ProductController extends Controller
{

/**
 * Get product reviews - Version simplifiée
 */
public function getProductReviews(Request $request, $productId)
{
    try {
        // Récupérer tous les avis approuvés
        $reviews = DB::table('product_reviews')
            ->join('customers', 'product_reviews.customer_id', '=', 'customers.id')
            ->where('product_reviews.product_id', $productId)
            ->where('product_reviews.is_approved', 1)
            ->orderBy('product_reviews.created_at', 'desc')
            ->select(
                'product_reviews.*',
                'customers.name as customer_name'
            )
            ->get();
        
        // Formater les avis
        $formattedReviews = [];
        foreach ($reviews as $review) {
            $formattedReviews[] = [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'review' => $review->review,
                'created_at' => $review->created_at,
                'customer' => [
                    'name' => $review->customer_name
                ]
            ];
        }
        
        // Calculer les statistiques
        $total = count($reviews);
        $average = $total > 0 ? $reviews->avg('rating') : 0;
        
        $distribution = [0,0,0,0,0];
        foreach ($reviews as $review) {
            $distribution[5 - $review->rating]++;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'data' => $formattedReviews,
                'total' => $total,
                'stats' => [
                    'total' => $total,
                    'average' => round($average, 1),
                    'distribution' => $distribution
                ]
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
} public function getReviews(Request $request, $productId)
{
    try {
        // 1. Vérifier si le produit existe
        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Produit non trouvé'
            ], 404);
        }
        
        // 2. Compter tous les avis pour ce produit
        $totalReviews = DB::table('product_reviews')
            ->where('product_id', $productId)
            ->count();
            
        // 3. Compter les avis approuvés
        $approvedReviews = DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('is_approved', true)
            ->count();
            
        // 4. Compter les avis non approuvés
        $unapprovedReviews = DB::table('product_reviews')
            ->where('product_id', $productId)
            ->where('is_approved', false)
            ->count();
        
        // 5. Récupérer tous les avis sans filtre pour voir
        $allReviews = DB::table('product_reviews')
            ->join('customers', 'product_reviews.customer_id', '=', 'customers.id')
            ->where('product_reviews.product_id', $productId)
            ->select(
                'product_reviews.*',
                'customers.name as customer_name',
                'customers.email as customer_email'
            )
            ->get();
        
        // 6. Retourner les informations de débogage
        return response()->json([
            'success' => true,
            'debug' => [
                'product_id' => $productId,
                'total_reviews_in_db' => $totalReviews,
                'approved_reviews' => $approvedReviews,
                'unapproved_reviews' => $unapprovedReviews,
                'all_reviews_raw' => $allReviews
            ],
            'data' => [
                'data' => [],
                'total' => 0,
                'stats' => [
                    'total' => $product->reviews_count ?? 0,
                    'average' => (float) ($product->rating ?? 0),
                    'distribution' => [0,0,0,0,0]
                ]
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Erreur: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}
  
    /**
     * Get all products with filters
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images']);

        // Apply filters
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->brands) {
            $brands = explode(',', $request->brands);
            $query->whereIn('brand', $brands);
        }

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Apply sorting
        switch ($request->sort_by) {
            case 'price-asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price-desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name-asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name-desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('featured', 'desc')->orderBy('created_at', 'desc');
        }

        $perPage = $request->per_page ?? 12;
        $products = $query->paginate($perPage);

        // Add user-specific pricing
        $user = $request->user();
        $products->getCollection()->transform(function ($product) use ($user) {
            $product->images_array = $product->getAllImagesAttribute();
            
            if ($user && $user->isPro()) {
                $product->original_price = $product->price;
                $product->price = $user->calculateProPrice($product->price);
                $product->pro_discount_applied = $user->pro_discount;
            }
            
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        $products = Product::with(['category', 'images'])
            ->where('featured', true)
            ->limit(8)
            ->get();

        $products->transform(function ($product) {
            $product->images_array = $product->getAllImagesAttribute();
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get single product by slug
     */
   /**
 * Get single product by slug
 */
public function show(Request $request, $slug)
{
    try {
        // 1. Chercher d'abord par slug (toujours une chaîne)
        $product = Product::with(['category', 'images'])
            ->where('slug', $slug)
            ->first();
        
        // 2. Si pas trouvé par slug et que le paramètre est un nombre, chercher par ID
        if (!$product && is_numeric($slug)) {
            $product = Product::with(['category', 'images'])
                ->where('id', $slug)
                ->first();
        }
        
        // 3. Si toujours pas trouvé, retourner erreur 404
        if (!$product) {
            Log::warning('Produit non trouvé', ['slug' => $slug]);
            return response()->json([
                'success' => false,
                'error' => 'Produit non trouvé'
            ], 404);
        }

        // Ajouter toutes les images au produit
        $product->images_array = $product->images->pluck('image_path')->toArray();
        
        $user = $request->user();
        $product->original_price = $product->price;
        
        if ($user && $user->isPro()) {
            $product->price = $user->calculateProPrice($product->price);
        }

        // Récupérer les produits similaires
        $related = Product::with('images')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        $related->transform(function ($item) use ($user) {
            $item->images_array = $item->images->pluck('image_path')->toArray();
            if ($user && $user->isPro()) {
                $item->original_price = $item->price;
                $item->price = $user->calculateProPrice($item->price);
            }
            return $item;
        });

        // Vérifier si le produit est en favoris
        $isFavorite = false;
        if ($user) {
            $isFavorite = $user->favorites()
                ->where('product_id', $product->id)
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'related' => $related,
                'is_favorite' => $isFavorite
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur lors du chargement du produit: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Erreur lors du chargement du produit'
        ], 500);
    }
}
    /**
     * Get products by IDs (for recently viewed)
     */
    public function byIds(Request $request)
    {
        $validator = validator($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        $products = Product::with(['category', 'images'])
            ->whereIn('id', $request->ids)
            ->get();

        $products->transform(function ($product) use ($request) {
            $product->images_array = $product->getAllImagesAttribute();
            
            $user = $request->user();
            if ($user && $user->isPro()) {
                $product->original_price = $product->price;
                $product->price = $user->calculateProPrice($product->price);
            }
            
            return $product;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Get product reviews
     */
    public function reviews(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $query = ProductReview::with('customer')
            ->where('product_id', $productId)
            ->where('is_approved', true);

        // Apply sorting
        switch ($request->sort) {
            case 'helpful':
                $query->orderBy('helpful_count', 'desc');
                break;
            case 'highest':
                $query->orderBy('rating', 'desc');
                break;
            case 'lowest':
                $query->orderBy('rating', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $reviews = $query->paginate(10);

        // Calculate stats
        $stats = [
            'total' => $product->reviews_count,
            'average' => $product->rating,
            'distribution' => [
                ProductReview::where('product_id', $productId)->where('rating', 5)->count(),
                ProductReview::where('product_id', $productId)->where('rating', 4)->count(),
                ProductReview::where('product_id', $productId)->where('rating', 3)->count(),
                ProductReview::where('product_id', $productId)->where('rating', 2)->count(),
                ProductReview::where('product_id', $productId)->where('rating', 1)->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $reviews->items(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Add a review
     */
    public function addReview(Request $request, $productId)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Authentification requise'
            ], 401);
        }

        $validator = validator($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:100',
            'review' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        // Check if user already reviewed this product
        $existing = ProductReview::where('product_id', $productId)
            ->where('customer_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => 'Vous avez déjà donné un avis sur ce produit'
            ], 400);
        }

        // Check if user purchased this product (optional)
        $hasPurchased = $user->orders()
            ->whereHas('items', function($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->exists();

        $review = ProductReview::create([
            'product_id' => $productId,
            'customer_id' => $user->id,
            'rating' => $request->rating,
            'title' => $request->title,
            'review' => $request->review,
            'images' => $request->images,
            'verified_purchase' => $hasPurchased,
            'is_approved' => false // Requires admin approval
        ]);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Votre avis a été soumis et sera publié après modération'
        ], 201);
    }
}
