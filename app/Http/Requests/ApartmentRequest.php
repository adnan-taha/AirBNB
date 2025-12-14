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
        ];
    }

}
