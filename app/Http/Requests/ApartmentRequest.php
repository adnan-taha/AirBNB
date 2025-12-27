<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApartmentRequest extends FormRequest
{
    public function authorize()
    {
        return true; // route middleware handles auth/role
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'rooms' => 'required|integer',
            'price_per_day' => 'required|numeric',
            'bathrooms'  => 'required|integer|min:1',
            'parking'    => 'required|boolean',
            'area'       => 'nullable|integer|min:10',
            'build_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'images' => 'nullable|array',
            ];
    }

}
