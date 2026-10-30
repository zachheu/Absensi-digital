<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\OrganizationalField;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'npp' => 'NPP'.fake()->unique()->numerify('######'),
            'npm' => '2113'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => UserRole::Pengurus,
            'organizational_field_id' => OrganizationalField::factory(),
            'is_active' => true,
            'last_login_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Admin,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withNpp(string $npp): static
    {
        return $this->state(fn (array $attributes): array => [
            'npp' => $npp,
        ]);
    }
}
