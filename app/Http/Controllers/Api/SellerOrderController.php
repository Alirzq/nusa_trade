<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Pesanan yang berisi produk milik seller (role: seller).
 */
class SellerOrderController extends Controller
{
    // GET /api/seller/orders  (hanya menampilkan item milik seller ini)
    public function index(Request $request): AnonymousResourceCollection
    {
        $sellerId = $request->user()->id;

        $orders = Order::with(['items' => fn ($q) => $q->where('seller_id', $sellerId)])
            ->whereHas('items', fn ($q) => $q->where('seller_id', $sellerId))
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    // PATCH /api/seller/orders/{order}/ship
    public function ship(Request $request, Order $order): OrderResource
    {
        $sellerId = $request->user()->id;

        abort_if(
            ! $order->items()->where('seller_id', $sellerId)->exists(),
            403,
            'Pesanan ini tidak berisi produk Anda.'
        );

        $updated = Order::whereKey($order->id)
            ->where('status', Order::STATUS_PAID)
            ->update(['status' => Order::STATUS_SHIPPED]);

        abort_if($updated === 0, 422, 'Hanya pesanan berstatus paid yang bisa dikirim.');

        return new OrderResource($order->refresh()->load([
            'items' => fn ($q) => $q->where('seller_id', $sellerId),
        ]));
    }
}
