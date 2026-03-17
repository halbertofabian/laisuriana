<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'El campo usuario es obligatorio.',
            'password.required' => 'El campo contraseña es obligatorio.',
        ];
    }
}
