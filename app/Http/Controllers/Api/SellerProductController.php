<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Kelola produk milik seller (role: seller).
 */
class SellerProductController extends Controller
{
    // GET /api/seller/products
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::with('category')
            ->where('seller_id', $request->user()->id)
            ->when($request->query('q'), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }

    // POST /api/seller/products
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $product = Product::create($data + [
            'seller_id' => $request->user()->id,
            'is_active' => true,
            'unit' => 'kg',
            'min_order' => 1,
            'is_organic' => false,
        ]);

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    // PUT/PATCH /api/seller/products/{product}
    public function update(Request $request, Product $product): ProductResource
    {
        $this->ensureOwner($request, $product);

        $product->update($request->validate($this->rules(update: true)));

        return new ProductResource($product->load('category'));
    }

    // DELETE /api/seller/products/{product}  (soft delete, riwayat pesanan tetap aman)
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureOwner($request, $product);

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }

    private function rules(bool $update = false): array
    {
        $required = $update ? ['sometimes', 'required'] : ['required'];

        return [
            'category_id' => [...$required, 'integer', 'exists:categories,id'],
            'name' => [...$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => [...$required, 'integer', 'min:0'],
            'stock' => [...$required, 'integer', 'min:0'],
            'unit' => ['sometimes', Rule::in(Product::UNITS)],            // default: kg
            'min_order' => ['sometimes', 'integer', 'min:1'],             // default: 1
            'harvest_date' => ['nullable', 'date', 'before_or_equal:today'],
            'is_organic' => ['sometimes', 'boolean'],
            'origin_region' => ['nullable', 'string', 'max:100'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function ensureOwner(Request $request, Product $product): void
    {
        abort_if($product->seller_id !== $request->user()->id, 403, 'Produk ini bukan milik Anda.');
    }
}
