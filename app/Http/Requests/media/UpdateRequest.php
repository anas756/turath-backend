<?php

namespace App\Http\Requests\media;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow authenticated users to update media
        // Additional authorization will be handled in the service
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:image,video,audio',
            'format' => 'sometimes|required|string|max:50',
            'resolution' => 'nullable|string|max:50',
            'size' => 'sometimes|required|integer|min:0',
            'curator' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,archived,processing',
        ];
    }

    /**
     * Get custom messages for validation errors
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The media title is required.',
            'type.required' => 'Media type is required.',
            'type.in' => 'Media type must be image, video, or audio.',
            'format.required' => 'File format is required.',
            'size.integer' => 'File size must be a number.',
        ];
    }
}
