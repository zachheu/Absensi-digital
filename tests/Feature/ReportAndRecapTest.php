<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PermissionRequestStatus;
use App\Enums\PermissionType;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\CommitteeMember;
use App\Models\CommitteePosition;
use App\Models\OrganizationalField;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_counts_one_row_per_attendance_even_with_multiple_positions(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $user = User::factory()->create();

        foreach (CommitteePosition::factory()->count(2)->create() as $position) {
            CommitteeMember::factory()->create([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'committee_position_id' => $position->id,
            ]);
        }

        Attendance::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin)->get(route('reports.index'))->assertOk();

        $attendances = $response->viewData('attendances');
        $summary = $response->viewData('summary');

        $this->assertSame(1, $attendances->total());
        $this->assertSame(1, $summary['total']);
        $this->assertSame(1, $summary['hadir']);
    }

    public function test_report_filters_by_status_activity_user_late_and_position(): void
    {
        $admin = User::factory()->admin()->create();

        $field = OrganizationalField::factory()->create();

        $activityA = Activity::factory()->create(['name' => 'Kegiatan A']);
        $activityB = Activity::factory()->create(['name' => 'Kegiatan B']);

        $position = CommitteePosition::factory()->create(['name' => 'Divisi Acara']);

        $userA = User::factory()->create(['organizational_field_id' => $field->id]);
        $userB = User::factory()->create();

        CommitteeMember::factory()->create([
            'activity_id' => $activityA->id,
            'user_id' => $userA->id,
            'committee_position_id' => $position->id,
        ]);

        Attendance::factory()->late()->create([
            'activity_id' => $activityA->id,
            'user_id' => $userA->id,
        ]);

        Attendance::factory()->withStatus(AttendanceStatus::Izin)->create([
            'activity_id' => $activityB->id,
            'user_id' => $userB->id,
        ]);

        $this->assertSame(1, $this->reportTotal($admin, ['status' => 'izin']));
        $this->assertSame(1, $this->reportTotal($admin, ['activity' => $activityA->id]));
        $this->assertSame(1, $this->reportTotal($admin, ['user' => $userB->id]));
        $this->assertSame(1, $this->reportTotal($admin, ['late' => '1']));
        $this->assertSame(1, $this->reportTotal($admin, ['late' => '0']));
        $this->assertSame(1, $this->reportTotal($admin, ['field' => $field->id]));
        $this->assertSame(1, $this->reportTotal($admin, ['position' => $position->id]));
        $this->assertSame(0, $this->reportTotal($admin, ['status' => 'alpa']));
    }

    public function test_report_exports_excel_and_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();
        $user = User::factory()->create();
        Attendance::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);

        $excel = $this->actingAs($admin)->get(route('reports.excel'))->assertOk();
        $excel->assertDownload();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $excel->headers->get('content-type'),
        );

        $pdf = $this->actingAs($admin)->get(route('reports.pdf'))->assertOk();
        $pdf->assertDownload();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $pdf->headers->get('content-type'),
        );
    }

    public function test_finalize_marks_only_committee_without_attendance_or_approved_permission_as_absent(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $present = User::factory()->create();
        $approved = User::factory()->create();
        $pending = User::factory()->create();
        $rejected = User::factory()->create();

        foreach ([$present, $approved, $pending, $rejected] as $user) {
            CommitteeMember::factory()->create([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'committee_position_id' => CommitteePosition::factory(),
            ]);
        }

        Attendance::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $present->id,
            'status' => AttendanceStatus::Hadir,
        ]);

        PermissionRequest::factory()->approved()->create([
            'activity_id' => $activity->id,
            'user_id' => $approved->id,
            'type' => PermissionType::Izin,
        ]);

        PermissionRequest::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $pending->id,
            'status' => PermissionRequestStatus::Pending,
        ]);

        PermissionRequest::factory()->create([
            'activity_id' => $activity->id,
            'user_id' => $rejected->id,
            'status' => PermissionRequestStatus::Rejected,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('activities.finalize', $activity));

        $response->assertSessionHasNoErrors();

        $activity->refresh();
        $this->assertNotNull($activity->recap_finalized_at);
        $this->assertSame(ActivityStatus::Completed, $activity->status);

        $this->assertSame(
            AttendanceStatus::Hadir,
            $this->attendanceFor($activity, $present)->status,
        );

        $this->assertSame(
            AttendanceStatus::Alpa,
            $this->attendanceFor($activity, $pending)->status,
        );

        $this->assertSame(
            AttendanceStatus::Alpa,
            $this->attendanceFor($activity, $rejected)->status,
        );

        $this->assertNull($this->attendanceFor($activity, $approved));

        $this->assertDatabaseHas('attendance_audits', [
            'activity_id' => $activity->id,
            'action' => 'recap.finalized',
            'user_id' => $pending->id,
        ]);
    }

    public function test_finalize_is_blocked_while_attendance_session_is_open(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        AttendanceSession::factory()->create([
            'activity_id' => $activity->id,
            'status' => AttendanceSession::STATUS_OPEN,
            'opened_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('activities.finalize', $activity))
            ->assertSessionHasErrors('finalize');

        $this->assertNull($activity->fresh()->recap_finalized_at);
    }

    public function test_finalize_is_idempotent_and_requires_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($admin)
            ->post(route('activities.finalize', $activity))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('activities.finalize', $activity))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('attendance_audits', 0);

        $pengurus = User::factory()->create();

        $this->actingAs($pengurus)
            ->post(route('activities.finalize', $activity))
            ->assertForbidden();

        $this->assertDatabaseCount('attendance_audits', 0);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function reportTotal(User $admin, array $query): int
    {
        $response = $this->actingAs($admin)
            ->get(route('reports.index', $query))
            ->assertOk();

        return (int) $response->viewData('attendances')->total();
    }

    private function attendanceFor(Activity $activity, User $user): ?Attendance
    {
        return Attendance::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();
    }
}
