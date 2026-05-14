<?php

namespace App\Http\Requests\Categorie;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class updateRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', 'unique:categories,name'],

            'description' => ['sometimes', 'string', 'max:1000'],

            'icon' => ['sometimes', 'string', 'max:255'],

            'banner' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
