<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Http\Requests\CorrectAttendanceRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CorrectionController extends Controller
{
    public function edit(Request $request, Attendance $attendance): View
    {
        $this->authorizeCorrection($request, $attendance);

        return view('attendance.correct', [
            'attendance' => $attendance->load(['user.organizationalField', 'activity']),
            'statuses' => AttendanceStatus::cases(),
            'audits' => $attendance->audits()->with('actor')->latest('created_at')->get(),
        ]);
    }

    public function update(CorrectAttendanceRequest $request, Attendance $attendance, AttendanceService $attendanceService): RedirectResponse
    {
        $this->authorizeCorrection($request, $attendance);

        $validated = $request->validated();
        $status = AttendanceStatus::from($validated['status']);
        $activity = $attendance->activity;

        $checkInAt = $status === AttendanceStatus::Hadir
            ? Carbon::parse($validated['check_in_at'])
            : null;

        DB::transaction(function () use ($attendance, $request, $validated, $status, $activity, $checkInAt, $attendanceService): void {
            $locked = Attendance::query()->lockForUpdate()->findOrFail($attendance->id);
            $oldValues = $attendanceService->snapshot($locked);

            $locked->update([
                'status' => $status,
                'check_in_at' => $checkInAt,
                'is_late' => $checkInAt !== null && $attendanceService->isLate($activity, $checkInAt),
                'source' => Attendance::SOURCE_MANUAL,
                'notes' => $validated['notes'] ?? $locked->notes,
            ]);

            $attendanceService->writeAudit(
                attendance: $locked,
                actor: $request->user(),
                action: 'attendance.corrected',
                oldValues: $oldValues,
                newValues: $attendanceService->snapshot($locked->fresh()),
                reason: $validated['reason'],
            );
        });

        return redirect()
            ->route('activities.show', $activity)
            ->with('status', 'Absensi '.$attendance->user->name.' berhasil dikoreksi dan tercatat di audit log.');
    }

    private function authorizeCorrection(Request $request, Attendance $attendance): void
    {
        $user = $request->user();

        abort_unless($user->hasPermission('attendance.correct'), 403, 'Kamu tidak memiliki izin koreksi absensi.');

        if ($user->isAdmin()) {
            return;
        }

        $isCommitteeMember = $attendance->activity->committeeMembers()
            ->where('user_id', $user->id)
            ->exists();

        abort_unless($isCommitteeMember, 403, 'Kamu hanya dapat mengoreksi absensi pada kegiatan yang kamu ikuti sebagai panitia.');
    }
}
