<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Pantau semua pesanan (role: admin).
 */
class AdminOrderController extends Controller
{
    // GET /api/admin/orders?status=pending|paid|shipped|completed|cancelled
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', 'in:'.implode(',', Order::STATUSES)],
        ]);

        $orders = Order::with('items')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    // GET /api/admin/orders/{order}
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load('items'));
    }
}
