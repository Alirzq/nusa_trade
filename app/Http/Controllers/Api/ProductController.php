<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Katalog produk (publik, tanpa login).
 */
class ProductController extends Controller
{
    // GET /api/products?q=&category_id=&region=&organic=1&min_price=&max_price=&sort=newest|price_asc|price_desc&per_page=12
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'category_id' => ['nullable', 'integer'],
            'organic' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Product::with(['category', 'seller:id,name,farm_name,region'])
            ->where('is_active', true)
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('region'), fn ($q, $region) => $q->where('origin_region', 'like', "%{$region}%"))
            ->when($request->filled('organic'), fn ($q) => $q->where('is_organic', $request->boolean('organic')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', (int) $request->query('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', (int) $request->query('max_price')));

        match ($request->query('sort', 'newest')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        return ProductResource::collection(
            $query->paginate((int) $request->query('per_page', 12))->withQueryString()
        );
    }

    // GET /api/products/{product}
    public function show(Product $product): ProductResource
    {
        abort_if(! $product->is_active, 404, 'Produk tidak ditemukan.');

        return new ProductResource($product->load(['category', 'seller:id,name,farm_name,region']));
    }
}
