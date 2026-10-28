<?php

namespace App\Http\Requests\Users;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validation for creating and editing users. Only admins reach this (route middleware).
 */
class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::enum(Role::class)],
            // Required when creating; optional when editing (empty = keep current password).
            'password' => [$user ? 'nullable' : 'required', 'confirmed', 'max:128', Password::min(8)->letters()->numbers()],
        ];
    }
}
