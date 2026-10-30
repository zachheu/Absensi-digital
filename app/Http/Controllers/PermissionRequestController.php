<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PermissionRequestStatus;
use App\Http\Requests\StorePermissionRequestRequest;
use App\Models\Activity;
use App\Models\PermissionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PermissionRequestController extends Controller
{
    public function index(Request $request): View
    {
        return view('permission-requests.index', [
            'requests' => $request->user()
                ->permissionRequests()
                ->with(['activity', 'reviewer'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $activities = Activity::query()
            ->visible()
            ->where('recap_finalized_at', null)
            ->whereHas('committeeMembers', fn ($query) => $query->where('user_id', $user->id))
            ->with(['permissionRequests' => fn ($query) => $query->where('user_id', $user->id)])
            ->with(['attendances' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderByDesc('start_at')
            ->get();

        return view('permission-requests.create', [
            'activities' => $activities,
        ]);
    }

    public function store(StorePermissionRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $activity = Activity::query()->findOrFail($validated['activity_id']);

        if ($activity->status === ActivityStatus::Draft) {
            return back()->withInput()->withErrors(['activity_id' => 'Kegiatan ini belum dipublikasikan.']);
        }

        if ($activity->recap_finalized_at !== null) {
            return back()->withInput()->withErrors(['activity_id' => 'Rekap kegiatan ini sudah difinalisasi.']);
        }

        $isCommitteeMember = $activity->committeeMembers()->where('user_id', $user->id)->exists();

        if (! $isCommitteeMember) {
            return back()->withInput()->withErrors(['activity_id' => 'Kamu tidak terdaftar sebagai panitia pada kegiatan ini.']);
        }

        if ($activity->attendances()->where('user_id', $user->id)->where('status', AttendanceStatus::Hadir->value)->exists()) {
            return back()->withInput()->withErrors(['activity_id' => 'Kamu sudah tercatat hadir pada kegiatan ini.']);
        }

        $existing = $activity->permissionRequests()->where('user_id', $user->id)->first();

        if ($existing !== null && $existing->status !== PermissionRequestStatus::Rejected) {
            return back()->withInput()->withErrors(['activity_id' => 'Sudah ada pengajuan aktif untuk kegiatan ini.']);
        }

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')->store('permission-attachments', 'local')
            : null;

        if ($existing !== null) {
            if ($existing->attachment_path !== null) {
                Storage::disk('local')->delete($existing->attachment_path);
            }

            $existing->update([
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'attachment_path' => $attachmentPath,
                'status' => PermissionRequestStatus::Pending,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ]);

            return redirect()
                ->route('permission-requests.index')
                ->with('status', 'Pengajuan yang sebelumnya ditolak berhasil diajukan ulang.');
        }

        PermissionRequest::query()->create([
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'status' => PermissionRequestStatus::Pending,
        ]);

        return redirect()
            ->route('permission-requests.index')
            ->with('status', 'Pengajuan izin/sakit terkirim dan menunggu keputusan admin.');
    }
}
