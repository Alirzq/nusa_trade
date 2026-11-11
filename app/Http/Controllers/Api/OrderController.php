<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pesanan oleh pembeli. Pembeli TIDAK punya akun (hanya admin dan seller yang punya akun).
 *
 * Saat pesanan dibuat, server mengembalikan `access_token` SEKALI SAJA. Token itu dikirim
 * lewat header `X-Order-Token` (atau field `token`) untuk melihat, membayar, membatalkan,
 * dan menyelesaikan pesanan. Di database hanya hash-nya yang disimpan.
 */
class OrderController extends Controller
{
    // POST /api/orders  (publik)
    // Body: { "buyer_name": "...", "buyer_phone": "...", "shipping_address": "...",
    //         "items": [ {"product_id": 1, "quantity": 2} ] }
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        // Gabungkan jika produk yang sama muncul lebih dari sekali
        $quantities = collect($data['items'])
            ->groupBy('product_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        $plainToken = Str::random(40);

        $order = DB::transaction(function () use ($data, $quantities, $plainToken) {
            // Kunci baris produk agar stok tidak bisa dibeli ganda secara bersamaan
            $products = Product::whereIn('id', $quantities->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $lines = [];

            foreach ($quantities as $productId => $qty) {
                $product = $products->get($productId);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => ["Produk #{$productId} tidak tersedia."],
                    ]);
                }

                if ($qty < $product->min_order) {
                    throw ValidationException::withMessages([
                        'items' => ["Pembelian minimum {$product->name} adalah {$product->min_order} {$product->unit}."],
                    ]);
                }

                if ($product->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$product->name} tidak mencukupi (tersisa {$product->stock} {$product->unit})."],
                    ]);
                }

                $subtotal = $product->price * $qty;
                $total += $subtotal;

                $lines[] = [
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'quantity' => $qty,
                    'price' => $product->price,
                    'subtotal' => $subtotal,
                ];

                $product->decrement('stock', $qty);
            }

            $order = Order::create([
                'code' => 'NT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'access_token' => hash('sha256', $plainToken),
                'buyer_name' => $data['buyer_name'],
                'buyer_phone' => $data['buyer_phone'],
                'status' => Order::STATUS_PENDING,
                'total' => $total,
                'shipping_address' => $data['shipping_address'],
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        return (new OrderResource($order->load('items')))
            ->additional(['access_token' => $plainToken])
            ->response()
            ->setStatusCode(201);
    }

    // GET /api/orders/{code}  (header X-Order-Token)
    public function show(Request $request, string $code): OrderResource
    {
        $order = $this->findOrder($request, $code);

        return new OrderResource($order->load('items'));
    }

    // POST /api/orders/{code}/pay  (simulasi pembayaran untuk demo)
    public function pay(Request $request, string $code): OrderResource
    {
        $order = $this->findOrder($request, $code);

        $updated = Order::whereKey($order->id)
            ->where('status', Order::STATUS_PENDING)
            ->update(['status' => Order::STATUS_PAID, 'paid_at' => now()]);

        abort_if($updated === 0, 422, 'Hanya pesanan berstatus pending yang bisa dibayar.');

        return new OrderResource($order->refresh()->load('items'));
    }

    // POST /api/orders/{code}/cancel  (stok dikembalikan)
    public function cancel(Request $request, string $code): OrderResource
    {
        $order = $this->findOrder($request, $code);

        DB::transaction(function () use ($order) {
            $updated = Order::whereKey($order->id)
                ->where('status', Order::STATUS_PENDING)
                ->update(['status' => Order::STATUS_CANCELLED]);

            abort_if($updated === 0, 422, 'Hanya pesanan berstatus pending yang bisa dibatalkan.');

            foreach ($order->items as $item) {
                Product::withTrashed()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        });

        return new OrderResource($order->refresh()->load('items'));
    }

    // POST /api/orders/{code}/complete  (pembeli konfirmasi pesanan diterima)
    public function complete(Request $request, string $code): OrderResource
    {
        $order = $this->findOrder($request, $code);

        $updated = Order::whereKey($order->id)
            ->where('status', Order::STATUS_SHIPPED)
            ->update(['status' => Order::STATUS_COMPLETED]);

        abort_if($updated === 0, 422, 'Hanya pesanan berstatus shipped yang bisa diselesaikan.');

        return new OrderResource($order->refresh()->load('items'));
    }

    /**
     * Cari pesanan berdasarkan kode dan cocokkan token pembeli.
     * Kode tidak ada dan token salah sengaja memberi respons yang sama (404)
     * agar orang tidak bisa menebak-nebak kode pesanan.
     */
    private function findOrder(Request $request, string $code): Order
    {
        $order = Order::where('code', $code)->first();
        $token = (string) ($request->header('X-Order-Token') ?: $request->input('token', ''));

        $valid = $order && $token !== '' && hash_equals($order->access_token, hash('sha256', $token));

        abort_unless($valid, 404, 'Pesanan tidak ditemukan atau token salah.');

        return $order;
    }
}
