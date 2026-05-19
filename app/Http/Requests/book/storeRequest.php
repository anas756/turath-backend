<?php

namespace App\Http\Requests\book;

use Illuminate\Foundation\Http\FormRequest;

class storeRequest extends FormRequest
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
            'title'           => 'required|string|max:255',
            'open_library_id' => 'nullable|string|max:100',
            'description'     => 'nullable|string',

            // Array validation for MongoDB document flexibility
            'authors'         => 'required|array|min:1',
            'authors.*'       => 'required|string|max:255',

            'cover'           => 'nullable|string', // URL string or path
            'categorie_id'    => 'required|string', // String matching your categories MongoDB ID

            'tags'            => 'nullable|array',
            'tags.*'          => 'string|max:50',
        ];
    }
}
