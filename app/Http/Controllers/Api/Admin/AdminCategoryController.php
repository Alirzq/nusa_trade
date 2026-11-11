<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Kelola kategori (role: admin). Daftar kategori untuk publik ada di GET /api/categories.
 */
class AdminCategoryController extends Controller
{
    // POST /api/admin/categories
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $category = Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    // PUT/PATCH /api/admin/categories/{category}
    public function update(Request $request, Category $category): CategoryResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $category->id),
        ]);

        return new CategoryResource($category);
    }

    // DELETE /api/admin/categories/{category}
    public function destroy(Category $category): JsonResponse
    {
        // Produk yang sudah dihapus (soft delete) tetap menahan kategorinya, jadi ikut dihitung.
        abort_if(
            $category->products()->withTrashed()->exists(),
            422,
            'Kategori masih dipakai produk, tidak bisa dihapus.'
        );

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);

        $taken = Category::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($slug === '' || $taken) {
            throw ValidationException::withMessages([
                'name' => ['Nama kategori terlalu mirip dengan kategori yang sudah ada.'],
            ]);
        }

        return $slug;
    }
}
