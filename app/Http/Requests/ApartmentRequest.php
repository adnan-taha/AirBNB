<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $provinceCities = [
            'Damascus' => ['Mouhajrin', 'Mazzeh', 'Dummar'],
            'Aleppo' => ['Shahba', 'Jamiliyah', 'Soleymanye'],
            'Homs' => ['Al-Waer', 'Hamidiya', 'Al Zahraa'],
            'Rif Dimashq' => ['Douma', 'Yafour', 'Zamalka'],
            'Latakia' => ['Kessab', 'Al Kournish', 'Jableh'],
            'Tartous' => ['Baniyas', 'Al Qadmous', 'Safita'],
        ];

        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => ['required', Rule::in(['Apartment', 'Penthouse', 'Hotel', 'Villa'])],
            'province' => ['required', Rule::in(array_keys($provinceCities))],
            'city' => ['required', function ($attribute, $value, $fail) use ($provinceCities) {
                $province = $this->input('province');
                if (!isset($provinceCities[$province]) || !in_array($value, $provinceCities[$province])) {
                    $fail('The selected city does not belong to the selected province.');
                }
            }],
            'rooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'parking' => 'required|boolean',
            'area' => 'required|integer|min:0',
            'build_year' => 'required|integer|min:1900|max:' . date('Y'),
            'price_per_day' => 'required|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ];
    }
}
