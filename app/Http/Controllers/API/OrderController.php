<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Models\Order;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
{
    $user = $request->user();

    if (in_array($user->role, ['super_admin', 'manager'])) {
        // Admin roles → see all orders
        $orders = $this->orderService->getAllOrders();
    } else {
        // Customer → only their orders
        $orders = $this->orderService->getUserOrders($user->id);
    }

    return ApiResponse::success($orders, 'Orders fetched successfully');
}

// public function store(Request $request)
// {
//     $user = $request->user();

//     if ($user->role !== 'customer') {
//         return ApiResponse::error('Only customers can place orders', 403);
//     }

//     $request->validate([
//         'items' => 'required|array',
//     ]);

//     return $this->orderService->createOrder(
//         $user->id,
//         $request->items
//     );
// }

public function store(Request $request)
{
    $user = $request->user();

    if ($user->role !== 'customer') {
        return ApiResponse::error('Only customers can place orders', 403);
    }

    // 1. Update validation to include address_id
    $request->validate([
        'address_id' => 'required|exists:addresses,id,user_id,' . $user->id,
        'items' => 'required|array',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1'
    ]);

    // 2. Pass the 3rd argument (address_id) to the service
    return $this->orderService->createOrder(
        $user->id,
        $request->items,
        $request->address_id // 👈 Add this line
    );
}
// public function checkout(Request $request)
// {
//     $user = $request->user();

//     if (!$user) {
//         return ApiResponse::error('Unauthenticated', 401);
//     }

//     if ($user->role !== 'customer') {
//         return ApiResponse::error('Only customers can checkout', 403);
//     }

//     $request->validate([
//         'items' => 'required|array',
//         'items.*.product_id' => 'required|exists:products,id',
//         'items.*.quantity' => 'required|integer|min:1'
//     ]);

//     try {
//         // We still run the service to create the order
//         $this->orderService->createOrder($user->id, $request->items);

//         // --- THE CHANGE IS HERE ---
//         // We pass null as the first argument so 'data' is empty/null
//         return ApiResponse::success(null, 'Order placed successfully');

//     } catch (\Exception $e) {
//         return ApiResponse::error($e->getMessage(), 400);
//     }
// }

public function checkout(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return ApiResponse::error('Unauthenticated', 401);
    }

    if ($user->role !== 'customer') {
        return ApiResponse::error('Only customers can checkout', 403);
    }

    $request->validate([
        'address_id' => 'required|exists:addresses,id,user_id,' . $user->id, // 👈 Validate address belongs to user
        'items' => 'required|array',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1'
    ]);

    try {
        // Pass the address_id to the Service
        $this->orderService->createOrder($user->id, $request->items, $request->address_id);

        return ApiResponse::success(null, 'Order placed successfully');
    } catch (\Exception $e) {
        return ApiResponse::error($e->getMessage(), 400);
    }
}

    public function show(Request $request, $id)
    {
        $user = $request->user();
    
        $order = Order::with(['items.product', 'user'])
            ->findOrFail($id);
    
        // 🔒 Restrict access
        if ($user->role === 'customer' && $order->user_id !== $user->id) {
            return ApiResponse::error('Unauthorized', 403);
        }
    
        return ApiResponse::success($order, 'Order details fetched');
    }
    public function update(Request $request, $id)
    {
        $user = $request->user();
    
        // 🔒 Only admin roles can update order
        if (!in_array($user->role, ['super_admin', 'manager'])) {
            return ApiResponse::error('Unauthorized', 403);
        }
    
        $request->validate([
            'status' => 'required|string'
        ]);
    
        $order = Order::findOrFail($id);
    
        $order->update([
            'order_status' => $request->order_status
        ]);
    
        return ApiResponse::success($order, 'Order status updated successfully');
    }
}