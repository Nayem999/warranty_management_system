<?php

namespace App\Http\Requests\WorkOrder;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_feedback' => 'required|string',
            'customer_rating' => 'required|integer|min:1|max:5',
        ];
    }
}
