<?php

namespace Database\Factories;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::now()->addDay()->setTime(8, 0);

        return [
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'location' => fake()->city(),
            'start_at' => $start,
            'end_at' => $start->copy()->addHours(3),
            'late_tolerance_minutes' => 15,
            'status' => ActivityStatus::Published,
            'created_by' => User::factory()->admin(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ActivityStatus::Draft,
        ]);
    }

    public function ongoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_at' => Carbon::now()->subHour(),
            'end_at' => Carbon::now()->addHour(),
            'status' => ActivityStatus::Ongoing,
        ]);
    }
}
