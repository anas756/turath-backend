<?php

namespace App\Http\Requests\document;

use Illuminate\Foundation\Http\FormRequest;

class updateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Set to true so Laravel allows the request to process
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title'           => 'sometimes|required|string|max:255',
            'open_library_id' => 'nullable|string|max:100',
            'description'     => 'nullable|string',

            'authors'         => 'sometimes|required|array|min:1',
            'authors.*'       => 'required|string|max:255',

            'cover'           => 'nullable|string',
            'categorie_id'    => 'sometimes|required|string',

            'tags'            => 'nullable|array',
            'tags.*'          => 'string|max:50',
        ];
    }
}
