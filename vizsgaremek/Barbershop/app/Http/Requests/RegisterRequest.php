<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ez az e-mail cím már regisztrálva van.',
            'password.min' => 'A jelszó legalább 8 karakter hosszú kell, hogy legyen.',
            'password.confirmed' => 'A jelszavak nem egyeznek.',
        ];
    }
}
