<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Helpers\ApiResponse;

class AuthController extends Controller
{
    // ✅ REGISTER (Customer / Super Admin)
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $superAdminExists = User::where('role', 'super_admin')->exists();

        if (!$superAdminExists) {
            $role = 'super_admin';
            $is_admin = 1;
        } else {
            $role = 'customer';
            $is_admin = 0;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $is_admin,
            'role' => $role,
        ]);

        // 🔥 Create tenant only for super admin
        if ($role === 'super_admin') {
            $tenant = Tenant::create([
                'name' => 'Main Store',
                'slug' => 'main-store-' . uniqid(),
                'is_active' => true,
                'owner_id' => $user->id,
            ]);

            $user->tenant_id = $tenant->id;
            $user->save();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
        ], 'User registered successfully');
    }

    // 🔥 SELLER REGISTER (MULTI-TENANT CORE)
    public function registerSeller(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'store_name' => 'nullable|string|max:255',
        ]);

        // 1️⃣ Create User
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'seller',
            'is_admin' => 0,
        ]);

        // 2️⃣ Create Tenant
        $tenant = Tenant::create([
            'name' => $validated['store_name'] ?? $user->name . "'s Store",
            'slug' => Str::slug(($validated['store_name'] ?? $user->name) . '-' . uniqid()),
            'is_active' => true,
            'owner_id' => $user->id,
        ]);

        // 3️⃣ Link User → Tenant
        $user->tenant_id = $tenant->id;
        $user->save();

        // 4️⃣ Token
        $token = $user->createToken('seller-token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'tenant' => $tenant,
            'token' => $token,
        ], 'Seller registered successfully');
    }

    // ✅ LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        // 🔥 Check tenant active (important SaaS rule)
        if ($user->tenant && !$user->tenant->is_active) {
            return ApiResponse::error('Store is inactive', 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token,
            'role' => $user->role,
            'is_admin' => $user->is_admin,
        ], 'Login successful');
    }

    // ✅ ADMIN LOGIN
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Invalid credentials', 401);
        }

        if ($user->role !== 'super_admin') {
            return ApiResponse::error('Access denied. Super Admin only', 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return ApiResponse::success([
            'user' => $user,
            'token' => $token
        ], 'Admin login successful');
    }

    // ✅ LOGOUT
    public function logout(Request $request)
    {
        // 🔥 safer logout (only current token)
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    // ✅ GET USER
    public function me(Request $request)
    {
        return ApiResponse::success($request->user(), 'User fetched');
    }

    // ✅ UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
            'delete_avatar' => 'sometimes|boolean'
        ]);

        // 🔥 Upload avatar
        if ($request->hasFile('avatar')) {
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = $path;
        }

        // 🔥 Delete avatar
        if ($request->boolean('delete_avatar')) {
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $user->avatar_url = null;
        }

        // 🔥 Update name
        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        $user->save();

        return ApiResponse::success([
            'user' => $user,
            'avatar_url' => $user->getFilamentAvatarUrl()
        ], 'Profile updated successfully');
    }
}