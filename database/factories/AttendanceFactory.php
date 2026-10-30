<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
            'status' => AttendanceStatus::Hadir,
            'check_in_at' => now(),
            'is_late' => false,
            'source' => Attendance::SOURCE_QR,
            'notes' => null,
        ];
    }

    public function late(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_late' => true,
        ]);
    }

    public function withStatus(AttendanceStatus $status): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => $status,
            'check_in_at' => $status === AttendanceStatus::Hadir ? now() : null,
        ]);
    }
}
