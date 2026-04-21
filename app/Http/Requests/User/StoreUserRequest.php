<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Set to true to allow the request to proceed
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'userName' => ['required', 'string', 'alpha_dash', 'min:3', 'max:20', 'unique:users,userName'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed', 
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
            ],
            'role' => ['required', 'string', 'in:admin,user'], 
        ];
    }

    /**
     * Professional tip: Add custom error messages in your language or style.
     */
    public function messages(): array
    {
        return [
            'userName.unique' => 'Ce nom d\'utilisateur est déjà pris.',
            'userName.alpha_dash' => 'Le nom d\'utilisateur ne peut contenir que des lettres, des chiffres et des tirets.',
            'email.unique' => 'Cette adresse email est déjà enregistrée.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.in' => 'Le rôle sélectionné n\'est pas valide.',
        ];
    }
}
