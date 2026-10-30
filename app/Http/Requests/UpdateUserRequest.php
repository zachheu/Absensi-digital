<?php

namespace App\Http\Requests;

use App\Enums\StructuralPosition;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'npp' => ['required', 'string', 'max:30', Rule::unique('users', 'npp')->ignore($user)],
            'npm' => ['required', 'string', 'max:30', Rule::unique('users', 'npm')->ignore($user)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', Password::defaults()],
            'organizational_field_id' => ['required', 'exists:organizational_fields,id'],
            'structural_position' => ['nullable', Rule::enum(StructuralPosition::class)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'npp.unique' => 'NPP sudah dipakai pengurus lain.',
            'npm.unique' => 'NPM sudah tercatat untuk pengurus lain.',
        ];
    }
}
