<?php

namespace App\Http\Requests\Api\Property;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'maxCapacity' => 'required|integer|min:1',
            'regularPrice' => 'required|numeric|min:1',
            'discount' => 'nullable|numeric|lt:regularPrice',
            'description' => 'nullable|string',
            'image' => 'nullable|file',
        ];
    }
}
