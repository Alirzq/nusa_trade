<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Pantau dan moderasi semua produk (role: admin).
 */
class AdminProductController extends Controller
{
    // GET /api/admin/products?q=&seller_id=&is_active=
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'seller_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $products = Product::with(['category', 'seller:id,name,farm_name,region'])
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->filled('seller_id'), fn ($q) => $q->where('seller_id', (int) $request->query('seller_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    // DELETE /api/admin/products/{product}  (soft delete, riwayat pesanan tetap aman)
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus oleh admin.']);
    }
}
