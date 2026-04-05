<?php

namespace App\Http\Requests\Claim;

use Illuminate\Foundation\Http\FormRequest;

class ConvertToWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_center_id' => 'nullable|exists:wms_service_centers,id',
            'engineer_id' => 'nullable|exists:users,id',
            'feedback_preference' => 'nullable|boolean',
            'additional_comment' => 'nullable|string',
        ];
    }
}
