<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    // Get all orders
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => $this->orderService->getOrders()
        ]);
    }

    // Show single order
    public function show($id)
    {
        return \App\Models\Order::with(['items.product', 'user'])
            ->findOrFail($id);
    }

    // Create a new order
    public function store(Request $request)
    {
        $request->validate([
            // ❌ REMOVED: user_id (security risk)
            // 'user_id' => 'required|exists:users,id',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // ✅ Always use authenticated user
        $user = $request->user();
        $userId = $user->id;
        $tenantId = $user->tenant_id;

        $items = $request->items;

        try {
            $order = DB::transaction(function () use ($userId, $tenantId, $items) {

                $orderItems = [];

                foreach ($items as $item) {

                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    // 🔐 CRITICAL: TENANT CHECK
                    if ($product->tenant_id !== $tenantId) {
                        throw new \Exception("Unauthorized product access for this tenant.");
                    }

                    // 🔐 STOCK CHECK
                    if ($product->quantity < $item['quantity']) {
                        throw new \Exception("Product '{$product->name}' only has {$product->quantity} in stock.");
                    }

                    // ✅ Deduct stock
                    $product->decrement('quantity', $item['quantity']);

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                    ];
                }

                // ✅ Create order (tenant_id auto handled in Model boot)
                return $this->orderService->createOrder($userId, $orderItems);
            });

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}