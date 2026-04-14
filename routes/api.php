<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import Controllers
use App\Http\Controllers\API\ProductController; 

use App\Http\Controllers\API\Admin\AdminProductController;
use App\Http\Controllers\API\Admin\AnalyticsController;
// use App\Http\Controllers\OrderController;
use App\Http\Controllers\API\OrderController; 
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ProductController as AiProductController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\AddressController;

/*
|--------------------------------------------------------------------------
| API Routes (SaaS + RBAC + Tenant Ready)
|--------------------------------------------------------------------------
*/

// ================= AI FOUNDATION LAYER =================
Route::post('/generate-product-description', [AiProductController::class, 'generateDescription']);

// ================= AUTH =================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-seller', [AuthController::class, 'registerSeller']);
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('api.admin.login');

// ================= AUTH USER =================
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::get('/test-user', fn(Request $request) => $request->user());

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);

    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses', [AddressController::class, 'index']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove']);
    Route::post('/cart/clear', [CartController::class, 'clear']);
    Route::get('/orders', [OrderController::class, 'index']);
});

// ================= PUBLIC (STORE FRONT READY) =================
Route::get('/products', [ProductController::class, 'index']); // marketplace
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// 🔥 NEW: STORE BASED ROUTES (MULTI-TENANT SAFE)
Route::get('/store/{slug}/products', [ProductController::class, 'indexByStore']);
Route::get('/store/{slug}/products/{id}', [ProductController::class, 'showByStore']);

// ================= SELLER ROUTES =================
Route::middleware(['auth:sanctum', 'role:seller'])->group(function () {

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});

// ================= CUSTOMER ROUTES =================
Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'add']);
    Route::delete('/cart/{id}', [CartController::class, 'remove']);
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});

// ================= ADMIN (MANAGER + SUPER ADMIN) =================
Route::middleware(['auth:sanctum', 'role:super_admin,manager'])->group(function () {

    Route::get('/admin/orders', [OrderController::class, 'index']);
    Route::put('/admin/orders/{id}', [OrderController::class, 'update']);

    Route::get('/admin/analytics', [AnalyticsController::class, 'index']);
});

Route::prefix('store/{slug}')->group(function () {

    Route::get('/products', [ProductController::class, 'indexByStore']);
    Route::get('/products/{id}', [ProductController::class, 'showByStore']);

});


// ================= SUPER ADMIN ONLY =================
Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {

    Route::post('/admin/products', [AdminProductController::class, 'store']);
    Route::put('/admin/products/{id}', [AdminProductController::class, 'update']);
    Route::delete('/admin/products/{id}', [AdminProductController::class, 'destroy']);

    Route::post('/admin/categories', [CategoryController::class, 'store']);
    Route::put('/admin/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);
});
Route::prefix('store/{slug}')
    ->middleware('tenant')
    ->group(function () {

        Route::get('/products', [ProductController::class, 'indexByStore']);

    });

// ❌ REMOVED PUBLIC DIRECT ACCESS (SECURITY FIX)
// Route::get('/products/{id}', [ProductController::class, 'show']);

// ================= EXTRA =================
Route::get('/health', function () {
    return response()->json([
        'status' => true,
        'message' => 'API is working'
    ]);
});