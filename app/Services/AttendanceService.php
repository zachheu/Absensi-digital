<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PermissionRequestStatus;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(private readonly QrTokenService $qrTokenService) {}

    /**
     * Catat kehadiran melalui pemindaian QR yang sudah divalidasi.
     *
     * Seluruh aturan validasi token, sesi, kegiatan, dan duplikasi diperiksa
     * di dalam transaksi dengan unique constraint sebagai pengaman terakhir.
     */
    public function recordQrAttendance(User $user, string $plainToken): AttendanceScanResult
    {
        $token = $this->qrTokenService->findUsable($plainToken);

        if ($token === null) {
            return AttendanceScanResult::failure('QR Code tidak valid, sudah kedaluwarsa, atau sudah dicabut. Silakan pindai QR terbaru.');
        }

        $session = $token->attendanceSession;
        $activity = $session?->activity;

        if ($session === null || $activity === null) {
            return AttendanceScanResult::failure('QR Code tidak terhubung dengan sesi absensi yang sah.');
        }

        if (! $session->isOpen()) {
            return AttendanceScanResult::failure('Sesi absensi untuk kegiatan ini sudah ditutup.');
        }

        if ($activity->status === ActivityStatus::Draft || $activity->status === ActivityStatus::Completed) {
            return AttendanceScanResult::failure('Kegiatan ini tidak sedang membuka absensi.');
        }

        if ($activity->recap_finalized_at !== null) {
            return AttendanceScanResult::failure('Rekap absensi kegiatan ini sudah difinalisasi. Hubungi admin bila perlu koreksi.');
        }

        $existing = Attendance::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            return AttendanceScanResult::success($existing, 'Absensi kamu untuk kegiatan ini sudah tercatat sebelumnya.');
        }

        $checkInAt = now();

        try {
            $attendance = DB::transaction(function () use ($activity, $user, $checkInAt): Attendance {
                return Attendance::query()->create([
                    'activity_id' => $activity->id,
                    'user_id' => $user->id,
                    'status' => AttendanceStatus::Hadir,
                    'check_in_at' => $checkInAt,
                    'is_late' => $this->isLate($activity, $checkInAt),
                    'source' => Attendance::SOURCE_QR,
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicateEntry($exception)) {
                $attendance = Attendance::query()
                    ->where('activity_id', $activity->id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                return AttendanceScanResult::success($attendance, 'Absensi kamu untuk kegiatan ini sudah tercatat sebelumnya.');
            }

            throw $exception;
        }

        $message = $attendance->is_late
            ? 'Absensi tercatat. Kamu tercatat terlambat.'
            : 'Absensi berhasil. Kamu tercatat hadir tepat waktu.';

        return AttendanceScanResult::success($attendance, $message);
    }

    /**
     * Hitung keterlambatan berdasarkan waktu check-in dan aturan kegiatan.
     */
    public function isLate(Activity $activity, Carbon $checkInAt): bool
    {
        return $checkInAt->greaterThan($activity->lateThreshold());
    }

    /**
     * Terapkan hasil persetujuan izin/sakit ke data absensi.
     */
    public function applyPermissionDecision(PermissionRequest $request, User $actor, Carbon $decidedAt): ?Attendance
    {
        if ($request->status !== PermissionRequestStatus::Approved) {
            return null;
        }

        $attendance = Attendance::query()
            ->where('activity_id', $request->activity_id)
            ->where('user_id', $request->user_id)
            ->first();

        $newValues = [
            'status' => $request->attendanceStatus()->value,
            'check_in_at' => null,
            'is_late' => false,
            'source' => Attendance::SOURCE_PERMISSION,
        ];

        if ($attendance === null) {
            return Attendance::query()->create([
                'activity_id' => $request->activity_id,
                'user_id' => $request->user_id,
                ...$newValues,
            ]);
        }

        if ($attendance->status === $request->attendanceStatus()) {
            return $attendance;
        }

        $oldValues = $this->snapshot($attendance);

        $attendance->update($newValues);

        $this->writeAudit(
            attendance: $attendance,
            actor: $actor,
            action: 'permission.approved',
            oldValues: $oldValues,
            newValues: $this->snapshot($attendance->fresh()),
            reason: 'Persetujuan pengajuan '.$request->type->value.' oleh admin.',
            createdAt: $decidedAt,
        );

        return $attendance;
    }

    /**
     * Finalisasi rekap: tetapkan alpa untuk pengurus yang tidak hadir dan tidak
     * memiliki izin/sakit yang disetujui. Pengajuan pending tidak diubah.
     */
    public function finalizeRecap(Activity $activity, User $actor): int
    {
        return DB::transaction(function () use ($activity, $actor): int {
            $activity->refresh();

            if ($activity->recap_finalized_at !== null) {
                return 0;
            }

            $committeeUserIds = $activity->committeeMembers()->pluck('user_id')->unique();

            $recordedUserIds = Attendance::query()
                ->where('activity_id', $activity->id)
                ->pluck('user_id');

            $approvedUserIds = PermissionRequest::query()
                ->where('activity_id', $activity->id)
                ->where('status', PermissionRequestStatus::Approved)
                ->pluck('user_id');

            $absentUserIds = $committeeUserIds
                ->diff($recordedUserIds)
                ->diff($approvedUserIds);

            $created = 0;

            foreach ($absentUserIds as $userId) {
                $attendance = Attendance::query()->create([
                    'activity_id' => $activity->id,
                    'user_id' => $userId,
                    'status' => AttendanceStatus::Alpa,
                    'check_in_at' => null,
                    'is_late' => false,
                    'source' => Attendance::SOURCE_SYSTEM,
                    'notes' => 'Ditetapkan alpa saat finalisasi rekap.',
                ]);

                $this->writeAudit(
                    attendance: $attendance,
                    actor: $actor,
                    action: 'recap.finalized',
                    oldValues: null,
                    newValues: $this->snapshot($attendance),
                    reason: 'Finalisasi rekap kegiatan: pengurus tidak hadir tanpa izin/sakit yang disetujui.',
                );

                $created++;
            }

            $activity->update([
                'recap_finalized_at' => now(),
                'status' => ActivityStatus::Completed,
            ]);

            return $created;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Attendance $attendance): array
    {
        return [
            'status' => $attendance->status->value,
            'check_in_at' => $attendance->check_in_at?->toIso8601String(),
            'is_late' => $attendance->is_late,
            'source' => $attendance->source,
            'notes' => $attendance->notes,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function writeAudit(
        Attendance $attendance,
        User $actor,
        string $action,
        ?array $oldValues,
        array $newValues,
        string $reason,
        ?Carbon $createdAt = null,
    ): AttendanceAudit {
        return AttendanceAudit::query()->create([
            'attendance_id' => $attendance->id,
            'activity_id' => $attendance->activity_id,
            'user_id' => $attendance->user_id,
            'actor_user_id' => $actor->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function isDuplicateEntry(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true)
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
