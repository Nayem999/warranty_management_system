<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');
        
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
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
        ];
    }
}
