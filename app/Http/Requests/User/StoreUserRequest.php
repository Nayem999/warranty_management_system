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
            'image' => 'sometimes|string',
            'phone' => 'sometimes|string|max:20',
            'job_title' => 'sometimes|string|max:255',
            'disable_login' => 'sometimes|boolean',
            'note' => 'sometimes|string',
            'address' => 'sometimes|string',
            'dob' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'language' => 'sometimes|string|max:10',
            'brand_ids' => 'sometimes|array',
            'brand_ids.*' => 'exists:wms_brands,id',
        ];
    }
}
