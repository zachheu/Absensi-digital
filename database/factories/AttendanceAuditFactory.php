<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceAudit>
 */
class AttendanceAuditFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
            'actor_user_id' => User::factory()->admin(),
            'action' => 'attendance.corrected',
            'old_values' => ['status' => 'alpa'],
            'new_values' => ['status' => 'hadir'],
            'reason' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
