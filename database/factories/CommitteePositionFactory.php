<?php

namespace Database\Factories;

use App\Enums\CommitteePositionCategory;
use App\Models\CommitteePosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteePosition>
 */
class CommitteePositionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'category' => fake()->randomElement(CommitteePositionCategory::cases()),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
