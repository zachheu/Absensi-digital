<?php

namespace App\Http\Requests;

use App\Enums\PengurusMembership;
use App\Enums\StructuralPosition;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
        return [
            'npp' => ['required', 'string', 'max:30', 'unique:users,npp'],
            'npm' => ['required', 'string', 'max:30', 'unique:users,npm'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', Password::defaults()],
            'organizational_field_id' => ['required', 'exists:organizational_fields,id'],
            'structural_position' => ['nullable', Rule::enum(StructuralPosition::class)],
            'membership_type' => ['nullable', Rule::enum(PengurusMembership::class)],
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
            'organizational_field_id.required' => 'Bidang wajib dipilih.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
