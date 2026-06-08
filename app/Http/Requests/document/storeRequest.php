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
            'cover'        => 'nullable|file|image|max:5120',  
            'categorie_id' => 'required|string',
            'file_path'    => 'required|file|mimes:pdf,epub,txt|max:102400',
            'tags'         => 'nullable|array',
            'tags.*'       => 'string|max:50',
            'source'       => 'nullable|string',
        ];
    }
}
