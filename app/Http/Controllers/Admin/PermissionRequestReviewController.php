<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\PermissionRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\PermissionRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermissionRequestReviewController extends Controller
{
    public function index(Request $request): View
    {
        $requests = PermissionRequest::query()
            ->with(['user.organizationalField', 'activity', 'reviewer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('activity'), fn ($query) => $query->where('activity_id', $request->integer('activity')))
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.permission-requests.index', [
            'requests' => $requests,
            'statuses' => PermissionRequestStatus::cases(),
            'activities' => Activity::query()->orderByDesc('start_at')->get(['id', 'name', 'start_at']),
        ]);
    }

    public function approve(Request $request, PermissionRequest $permissionRequest, AttendanceService $attendanceService): RedirectResponse
    {
        return $this->decide($request, $permissionRequest, PermissionRequestStatus::Approved, $attendanceService);
    }

    public function reject(Request $request, PermissionRequest $permissionRequest, AttendanceService $attendanceService): RedirectResponse
    {
        return $this->decide($request, $permissionRequest, PermissionRequestStatus::Rejected, $attendanceService);
    }

    private function decide(
        Request $request,
        PermissionRequest $permissionRequest,
        PermissionRequestStatus $decision,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $validated = $request->validate([
            'review_notes' => [$decision === PermissionRequestStatus::Rejected ? 'required' : 'nullable', 'string', 'max:1000'],
        ], [
            'review_notes.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $failure = DB::transaction(function () use ($request, $permissionRequest, $decision, $validated, $attendanceService): ?string {
            $locked = PermissionRequest::query()->lockForUpdate()->find($permissionRequest->id);

            if ($locked === null || $locked->status !== PermissionRequestStatus::Pending) {
                return 'Pengajuan ini sudah diproses sebelumnya.';
            }

            if ($decision === PermissionRequestStatus::Approved) {
                $activity = $locked->activity;

                if ($activity->recap_finalized_at !== null) {
                    return 'Rekap kegiatan sudah difinalisasi. Pengajuan tidak dapat disetujui.';
                }

                $alreadyPresent = Attendance::query()
                    ->where('activity_id', $locked->activity_id)
                    ->where('user_id', $locked->user_id)
                    ->where('status', AttendanceStatus::Hadir->value)
                    ->exists();

                if ($alreadyPresent) {
                    return 'Pengurus sudah tercatat hadir pada kegiatan ini. Koreksi absensi terlebih dahulu bila status perlu diubah.';
                }
            }

            $locked->update([
                'status' => $decision,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'] ?? null,
            ]);

            $attendanceService->applyPermissionDecision($locked->fresh(), $request->user(), now());

            return null;
        });

        if ($failure !== null) {
            return back()->withErrors(['review' => $failure]);
        }

        return back()->with('status', 'Pengajuan berhasil '.($decision === PermissionRequestStatus::Approved ? 'disetujui' : 'ditolak').'.');
    }
}
