<?php

namespace App\Http\Requests\document;

use Illuminate\Foundation\Http\FormRequest;

class storeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'authors'      => 'required|array|min:1',
            'authors.*'    => 'required|string|max:255',
            'categorie_id' => 'required|string|exists:categories,id',
            'cover'        => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
            'file_path'    => 'required|file|mimes:pdf,epub,txt|max:102400',
            'tags'         => 'nullable|array',
            'tags.*'       => 'string|max:50',
            'source'       => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'cover.max' => 'The cover image must not exceed 5MB.',
            'cover.mimes' => 'The cover must be a JPEG, JPG, or PNG file.',
            'file_path.required' => 'The document file is required.',
            'file_path.mimes' => 'The document must be a PDF, EPUB, or TXT file.',
            'file_path.max' => 'The document file must not exceed 100MB.',
            'authors.required' => 'At least one author is required.',
            'authors.array' => 'Authors must be submitted as an array.',
        ];
    }
}
