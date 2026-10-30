<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\CommitteeMember;
use App\Models\CommitteePosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommitteeMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_move_committee_member_to_another_position(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['created_by' => $admin->id]);
        $member = CommitteeMember::factory()->create(['activity_id' => $activity->id]);
        $newPosition = CommitteePosition::factory()->create();

        $response = $this->actingAs($admin)->put(route('activities.committee.update', $activity), [
            'committee_member_id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('committee_members', [
            'id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);
    }

    public function test_admin_cannot_move_member_to_position_already_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['created_by' => $admin->id]);
        $position = CommitteePosition::factory()->create();
        $user = User::factory()->create();

        CommitteeMember::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'committee_position_id' => $position->id,
        ]);

        $member = CommitteeMember::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin)->put(route('activities.committee.update', $activity), [
            'committee_member_id' => $member->id,
            'committee_position_id' => $position->id,
        ]);

        $response->assertRedirect()->assertSessionHasErrors('committee');

        $this->assertDatabaseMissing('committee_members', [
            'id' => $member->id,
            'committee_position_id' => $position->id,
        ]);
    }

    public function test_position_cannot_change_after_recap_finalized(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create([
            'created_by' => $admin->id,
            'recap_finalized_at' => now(),
        ]);
        $member = CommitteeMember::factory()->create(['activity_id' => $activity->id]);
        $newPosition = CommitteePosition::factory()->create();

        $response = $this->actingAs($admin)->put(route('activities.committee.update', $activity), [
            'committee_member_id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);

        $response->assertRedirect()->assertSessionHasErrors('committee');

        $this->assertDatabaseMissing('committee_members', [
            'id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);
    }

    public function test_position_cannot_change_when_activity_completed(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create([
            'created_by' => $admin->id,
            'status' => ActivityStatus::Completed,
        ]);
        $member = CommitteeMember::factory()->create(['activity_id' => $activity->id]);
        $newPosition = CommitteePosition::factory()->create();

        $response = $this->actingAs($admin)->put(route('activities.committee.update', $activity), [
            'committee_member_id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);

        $response->assertRedirect()->assertSessionHasErrors('committee');

        $this->assertDatabaseMissing('committee_members', [
            'id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);
    }

    public function test_member_from_other_activity_returns_not_found(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['created_by' => $admin->id]);
        $otherActivity = Activity::factory()->create(['created_by' => $admin->id]);
        $member = CommitteeMember::factory()->create(['activity_id' => $otherActivity->id]);
        $newPosition = CommitteePosition::factory()->create();

        $response = $this->actingAs($admin)->put(route('activities.committee.update', $activity), [
            'committee_member_id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);

        $response->assertNotFound();
    }

    public function test_admin_sees_move_form_on_activity_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['created_by' => $admin->id]);
        CommitteeMember::factory()->create(['activity_id' => $activity->id]);
        CommitteePosition::factory()->count(2)->create();

        $response = $this->actingAs($admin)->get(route('activities.show', $activity));

        $response->assertOk()->assertSee('Pindahkan panitia', false);
    }

    public function test_move_form_hidden_after_recap_finalized(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create([
            'created_by' => $admin->id,
            'recap_finalized_at' => now(),
        ]);
        CommitteeMember::factory()->create(['activity_id' => $activity->id]);

        $response = $this->actingAs($admin)->get(route('activities.show', $activity));

        $response->assertOk()->assertDontSee('Pindahkan panitia', false);
    }

    public function test_pengurus_cannot_move_committee_member(): void
    {
        $pengurus = User::factory()->create();
        $activity = Activity::factory()->create();
        $member = CommitteeMember::factory()->create(['activity_id' => $activity->id]);
        $newPosition = CommitteePosition::factory()->create();

        $response = $this->actingAs($pengurus)->put(route('activities.committee.update', $activity), [
            'committee_member_id' => $member->id,
            'committee_position_id' => $newPosition->id,
        ]);

        $response->assertForbidden();
    }
}
