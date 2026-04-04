<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
{
    $user = auth()->user();

    return $user && in_array($user->role, ['super_admin', 'manager', 'seller']);
}
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image'
        ];
    }

    public function messages()
    {
    return [
        'category_id.exists' => 'Category not found'
    ];
    }
}
