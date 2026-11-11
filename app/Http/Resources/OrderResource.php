<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'total' => $this->total,
            'shipping_address' => $this->shipping_address,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'buyer' => [
                'name' => $this->buyer_name,
                'phone' => $this->buyer_phone,
            ],
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
            ])->values()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
