<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
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
        ];
    }
}
