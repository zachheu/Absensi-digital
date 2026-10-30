<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\CommitteeMember;
use App\Models\CommitteePosition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'user_id' => User::factory(),
            'committee_position_id' => CommitteePosition::factory(),
        ];
    }
}
