<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\CommitteeMember;
use App\Models\CommitteePosition;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_correct_attendance_and_audit_is_written(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();
        $user = User::factory()->create();
        $attendance = Attendance::factory()->withStatus(AttendanceStatus::Alpa)->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($admin)
            ->put(route('attendances.correct.update', $attendance), [
                'status' => AttendanceStatus::Hadir->value,
                'check_in_at' => '2026-10-29 08:05:00',
                'notes' => 'Dibuktikan dengan daftar hadir fisik.',
                'reason' => 'QR gagal dipindai saat kegiatan berlangsung.',
            ])
            ->assertRedirect(route('activities.show', $activity));

        $attendance->refresh();

        $this->assertSame(AttendanceStatus::Hadir, $attendance->status);
        $this->assertSame('manual', $attendance->source);
        $this->assertNotNull($attendance->check_in_at);

        $audit = AttendanceAudit::query()->where('attendance_id', $attendance->id)->firstOrFail();

        $this->assertSame('attendance.corrected', $audit->action);
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertSame(AttendanceStatus::Alpa->value, $audit->old_values['status']);
        $this->assertSame(AttendanceStatus::Hadir->value, $audit->new_values['status']);
        $this->assertSame('QR gagal dipindai saat kegiatan berlangsung.', $audit->reason);
    }

    public function test_pengurus_with_permission_can_correct_own_committee_activity(): void
    {
        $user = User::factory()->create();
        $this->grantCorrectionPermission($user);

        $activity = Activity::factory()->create();
        $this->assignCommittee($activity, $user);

        $attendance = Attendance::factory()->withStatus(AttendanceStatus::Hadir)->create([
            'activity_id' => $activity->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->put(route('attendances.correct.update', $attendance), [
                'status' => AttendanceStatus::Izin->value,
                'reason' => 'Pengurus menyerahkan surat izin resmi kepada panitia.',
            ])
            ->assertRedirect(route('activities.show', $activity));

        $this->assertSame(AttendanceStatus::Izin, $attendance->fresh()->status);
    }

    public function test_pengurus_with_permission_cannot_correct_other_activity(): void
    {
        $user = User::factory()->create();
        $this->grantCorrectionPermission($user);

        $activity = Activity::factory()->create();
        $attendance = Attendance::factory()->create(['activity_id' => $activity->id]);

        $this->actingAs($user)
            ->put(route('attendances.correct.update', $attendance), [
                'status' => AttendanceStatus::Izin->value,
                'reason' => 'Mencoba mengoreksi kegiatan yang bukan kepanitiaannya.',
            ])
            ->assertForbidden();

        $this->assertNotSame(AttendanceStatus::Izin, $attendance->fresh()->status);
    }

    public function test_pengurus_without_permission_cannot_correct_attendance(): void
    {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $this->assignCommittee($activity, $user);
        $attendance = Attendance::factory()->create(['activity_id' => $activity->id]);

        $this->actingAs($user)
            ->put(route('attendances.correct.update', $attendance), [
                'status' => AttendanceStatus::Izin->value,
                'reason' => 'Tanpa izin koreksi seharusnya ditolak.',
            ])
            ->assertForbidden();
    }

    public function test_correction_requires_reason_and_leaves_data_untouched(): void
    {
        $admin = User::factory()->admin()->create();
        $attendance = Attendance::factory()->create();

        $this->actingAs($admin)
            ->from(route('attendances.correct.edit', $attendance))
            ->put(route('attendances.correct.update', $attendance), [
                'status' => AttendanceStatus::Alpa->value,
                'reason' => 'pendek',
            ])
            ->assertRedirect(route('attendances.correct.edit', $attendance))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('attendance_audits', 0);
        $this->assertSame(AttendanceStatus::Hadir, $attendance->fresh()->status);
    }

    private function grantCorrectionPermission(User $user): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => Permission::ATTENDANCE_CORRECT],
            ['description' => 'Koreksi absensi.'],
        );

        $user->permissions()->attach($permission);
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
