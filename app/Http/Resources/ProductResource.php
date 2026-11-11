<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'unit' => $this->unit,
            'min_order' => $this->min_order,
            'harvest_date' => $this->harvest_date?->toDateString(),
            'is_organic' => $this->is_organic,
            'origin_region' => $this->origin_region,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id,
                'name' => $this->seller->name,
                'farm_name' => $this->seller->farm_name,
                'region' => $this->seller->region,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
