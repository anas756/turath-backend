<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:50'],

            'userName' => [
                'sometimes',
                'string',
                'alpha_dash',
                'min:3',
                'max:20',
                Rule::unique('users', 'userName')->ignore($userId)
            ],

            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],

            'password' => [
                'nullable', 
                'confirmed',
                Password::min(8)->letters()->numbers()
            ],

            'role' => ['sometimes', 'string', 'in:admin,user,editor'],
        ];
    }

    public function messages(): array
    {
        return [
            'userName.unique' => 'Ce nom d\'utilisateur est déjà utilisé.',
            'email.unique' => 'Cet email est déjà associé à un autre compte.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
