<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\QrToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrToken>
 */
class QrTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_session_id' => AttendanceSession::factory(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'issued_at' => now(),
            'expires_at' => now()->addSeconds(15),
            'revoked_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'issued_at' => now()->subSeconds(30),
            'expires_at' => now()->subSeconds(5),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'revoked_at' => now(),
        ]);
    }
}
