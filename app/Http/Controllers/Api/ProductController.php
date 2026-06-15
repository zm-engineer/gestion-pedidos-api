<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * Lista el catálogo de productos, cacheado 5 minutos.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Cache::remember('products.all', now()->addMinutes(5), fn () => Product::all())
        );
    }
}
