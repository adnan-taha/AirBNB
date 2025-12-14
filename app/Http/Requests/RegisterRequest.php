<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:5',
            'role' => 'required|in:tenant,owner',
            'birth_date' => 'nullable|date',
            'photo' => 'nullable|string',
            'id_photo_front' => 'nullable|string',
            'id_photo_back' => 'nullable|string',
        ];
    }
}
