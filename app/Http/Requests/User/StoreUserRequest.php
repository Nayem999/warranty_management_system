<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'sometimes|string|min:8',
            'user_type' => 'sometimes|in:admin,staff,client',
            'is_admin' => 'sometimes|boolean',
            'role_id' => 'sometimes|exists:wms_roles,id',
            'status' => 'sometimes|in:active,inactive',
            'image' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'job_title' => 'nullable|string|max:255',
            'disable_login' => 'sometimes|boolean',
            'note' => 'nullable|string',
            'address' => 'nullable|string',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'language' => 'nullable|string|max:10',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:wms_brands,id',
        ];
    }
}
