<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\CommitteeMember;
use App\Models\CommitteePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkCommitteeSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_assign_with_kadiv(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['created_by' => $admin->id]);
        $position = CommitteePosition::factory()->create();
        $users = User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->post(route('activities.committee.store', $activity), [
            'committee_position_id' => $position->id,
            'user_ids' => $users->pluck('id')->all(),
            'head_user_id' => $users->first()->id,
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('committee_members', 3);
        $this->assertDatabaseHas('committee_members', [
            'activity_id' => $activity->id,
            'user_id' => $users->first()->id,
            'is_head' => true,
        ]);
        $this->assertSame(1, CommitteeMember::where('activity_id', $activity->id)->where('is_head', true)->count());
    }

    public function test_bulk_rejects_head_not_in_list(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['created_by' => $admin->id]);
        $position = CommitteePosition::factory()->create();
        $users = User::factory()->count(2)->create();
        $outsider = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('activities.committee.store', $activity), [
            'committee_position_id' => $position->id,
            'user_ids' => $users->pluck('id')->all(),
            'head_user_id' => $outsider->id,
        ]);

        $response->assertRedirect()->assertSessionHasErrors('committee');
        $this->assertDatabaseCount('committee_members', 0);
    }
}
