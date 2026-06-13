<?php

namespace App\Http\Requests\media;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'    => 'sometimes|required|string|max:255',
            'type'     => 'sometimes|required|string|in:image,video,audio',
            'file_path' => 'sometimes|file|max:102400',
            'curator'  => 'nullable|string|max:255',
            'description' => 'nullable|string|max:6000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status'   => 'nullable|string|in:active,archived,processing',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'  => 'The media title is required.',
            'type.required'   => 'Media type is required.',
            'type.in'         => 'Media type must be image, video, or audio.',
            'file_path.file'  => 'The uploaded media must be a valid file.',
            'file_path.max'   => 'The media file must not exceed 100MB.',
        ];
    }
}
