<?php

namespace Database\Factories;

use App\Enums\PermissionRequestStatus;
use App\Enums\PermissionType;
use App\Models\Activity;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermissionRequest>
 */
class PermissionRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
            'type' => PermissionType::Izin,
            'reason' => fake()->sentence(8),
            'status' => PermissionRequestStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PermissionRequestStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
