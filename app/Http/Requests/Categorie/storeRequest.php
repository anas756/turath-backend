<?php

namespace App\Http\Requests\Categorie;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreRequest extends FormRequest
{
    /**
     * authorize request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],

            'description' => ['nullable', 'string', 'max:1000'],

            'icon' => ['nullable', 'string', 'max:255'],

            'banner' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * custom messages (optional but professional)
     */
    public function messages(): array
    {
        return [
            'name.required' => 'category name is required',
            'name.unique' => 'this category already exists',
            'name.max' => 'category name is too long',
        ];
    }
}
  