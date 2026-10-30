<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PermissionRequestStatus;
use App\Enums\PermissionType;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\CommitteeMember;
use App\Models\CommitteePosition;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_committee_member_can_submit_permission_request(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $this->assignCommittee($activity, $user);

        $this->actingAs($user)
            ->post(route('permission-requests.store'), [
                'activity_id' => $activity->id,
                'type' => PermissionType::Izin->value,
                'reason' => 'Mengikuti lomba tingkat nasional pada tanggal yang sama.',
            ])
            ->assertRedirect(route('permission-requests.index'));

        $this->assertDatabaseHas('permission_requests', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'type' => PermissionType::Izin->value,
            'status' => PermissionRequestStatus::Pending->value,
        ]);
    }

    public function test_non_committee_member_cannot_submit_permission_request(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($user)
            ->post(route('permission-requests.store'), [
                'activity_id' => $activity->id,
                'type' => PermissionType::Sakit->value,
                'reason' => 'Sedang sakit dan tidak dapat menghadiri kegiatan.',
            ])
            ->assertSessionHasErrors('activity_id');

        $this->assertDatabaseCount('permission_requests', 0);
    }

    public function test_admin_approval_creates_attendance_with_permission_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $this->assignCommittee($activity, $user);

        $request = PermissionRequest::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'type' => PermissionType::Sakit,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permission-requests.approve', $request), [
                'review_notes' => 'Surat dokter terlampir valid.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(PermissionRequestStatus::Approved, $request->fresh()->status);
        $this->assertSame($admin->id, $request->fresh()->reviewed_by);

        $this->assertDatabaseHas('attendances', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => AttendanceStatus::Sakit->value,
            'source' => Attendance::SOURCE_PERMISSION,
        ]);
    }

    public function test_rejection_requires_notes_and_does_not_create_attendance(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $request = PermissionRequest::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permission-requests.reject', $request))
            ->assertSessionHasErrors('review_notes');

        $this->assertSame(PermissionRequestStatus::Pending, $request->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.permission-requests.reject', $request), [
                'review_notes' => 'Bukti tidak mendukung alasan pengajuan.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(PermissionRequestStatus::Rejected, $request->fresh()->status);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_rejected_request_can_be_submitted_again_without_duplicate_row(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $this->assignCommittee($activity, $user);

        $request = PermissionRequest::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => PermissionRequestStatus::Rejected,
            'review_notes' => 'Alasan kurang jelas.',
        ]);

        $this->actingAs($user)
            ->post(route('permission-requests.store'), [
                'activity_id' => $activity->id,
                'type' => PermissionType::Izin->value,
                'reason' => 'Alasan diperbaiki: menghadiri sidang tugas akhir.',
            ])
            ->assertRedirect(route('permission-requests.index'));

        $this->assertDatabaseCount('permission_requests', 1);

        $request->refresh();
        $this->assertSame(PermissionRequestStatus::Pending, $request->status);
        $this->assertNull($request->review_notes);
        $this->assertStringContainsString('diperbaiki', $request->reason);
    }

    public function test_pending_request_blocks_second_submission(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $this->assignCommittee($activity, $user);

        PermissionRequest::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('permission-requests.store'), [
                'activity_id' => $activity->id,
                'type' => PermissionType::Izin->value,
                'reason' => 'Pengajuan kedua seharusnya ditolak sistem.',
            ])
            ->assertSessionHasErrors('activity_id');

        $this->assertDatabaseCount('permission_requests', 1);
    }

    public function test_approval_is_blocked_when_user_already_present(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $request = PermissionRequest::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);
        Attendance::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => AttendanceStatus::Hadir,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.permission-requests.approve', $request), [
                'review_notes' => 'Tetap hadir.',
            ])
            ->assertSessionHasErrors('review');

        $this->assertSame(PermissionRequestStatus::Pending, $request->fresh()->status);
        $this->assertSame(AttendanceStatus::Hadir, Attendance::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->status);
    }

    public function test_approval_is_blocked_after_recap_finalized(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create([
            'status' => ActivityStatus::Completed,
            'recap_finalized_at' => now(),
        ]);
        $request = PermissionRequest::factory()->create(['activity_id' => $activity->id]);

        $this->actingAs($admin)
            ->post(route('admin.permission-requests.approve', $request), [
                'review_notes' => 'Terlambat disetujui.',
            ])
            ->assertSessionHasErrors('review');

        $this->assertSame(PermissionRequestStatus::Pending, $request->fresh()->status);
    }

    private function assignCommittee(Activity $activity, User $user): CommitteeMember
    {
        return CommitteeMember::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'committee_position_id' => CommitteePosition::factory(),
        ]);
    }
}
