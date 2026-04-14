<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Helpers\ApiResponse;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'address_line_1' => 'required|string',
            'city'           => 'required|string',
            'state'          => 'required|string',
            'postal_code'    => 'required|string',
            'country'        => 'required|string',
        ]);

        try {
            $address = Address::create([
                'user_id'        => auth()->id(),
                'address_line_1' => $request->address_line_1,
                'city'           => $request->city,
                'state'          => $request->state,
                'postal_code'    => $request->postal_code,
                'country'        => $request->country,
                'is_default'     => $request->is_default ?? false,
            ]);

            return ApiResponse::success($address, 'Address added successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to add address: ' . $e->getMessage(), 500);
        }
    }

    public function index(Request $request)
    {
        $addresses = Address::where('user_id', auth()->id())->get();
        return ApiResponse::success($addresses, 'Addresses retrieved successfully');
    }
}