<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // For updates, dates are optional; for creates, they're required
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        
        $rules = [
            'start_date' => ($isUpdate ? 'nullable' : 'required') . '|date|after_or_equal:today',
        ];

        // Validate end_date
        if ($isUpdate) {
            // For updates, end_date is nullable but if provided must be valid date
            $rules['end_date'] = 'nullable|date';
            // If both dates provided, end_date must be after start_date
            if ($this->has('start_date') && $this->has('end_date')) {
                $rules['end_date'] .= '|after:start_date';
            }
        } else {
            // For creates, end_date is required and must be after start_date
            $rules['end_date'] = 'required|date|after:start_date';
        }

        return $rules;
    }
}
