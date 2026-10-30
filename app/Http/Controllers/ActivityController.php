<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PermissionRequestStatus;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\CommitteePosition;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Activity::query()
            ->with('creator')
            ->withCount(['attendances', 'committeeMembers'])
            ->orderByDesc('start_at');

        if (! $user->isAdmin()) {
            $query->visible();
        }

        $query->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.(string) $request->string('search').'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('location', 'like', $search);
                });
            });

        return view('activities.index', [
            'activities' => $query->paginate(15)->withQueryString(),
            'statuses' => ActivityStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('activities.create', [
            'activity' => new Activity([
                'late_tolerance_minutes' => 15,
                'status' => ActivityStatus::Draft,
            ]),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $activity = Activity::query()->create([
            ...$request->validated(),
            'status' => $request->input('status', ActivityStatus::Draft->value),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('activities.show', $activity)
            ->with('status', 'Kegiatan berhasil dibuat.');
    }

    public function show(Request $request, Activity $activity): View
    {
        $user = $request->user();

        if (! $user->isAdmin() && $activity->status === ActivityStatus::Draft) {
            abort(404);
        }

        $activity->load([
            'creator',
            'committeeMembers.position',
            'committeeMembers.user.organizationalField',
        ]);

        $attendances = $activity->attendances()
            ->with(['user.organizationalField'])
            ->orderBy('check_in_at')
            ->get();

        $positions = CommitteePosition::query()
            ->where('is_active', true)
            ->orderByRaw("FIELD(category, 'structural', 'division')")
            ->orderBy('id')
            ->get();

        $assignedUserIds = $activity->committeeMembers->pluck('user_id')->unique();

        return view('activities.show', [
            'activity' => $activity,
            'sessions' => $activity->sessions()->latest('opened_at')->get(),
            'latestSession' => $activity->sessions()->latest('opened_at')->first(),
            'attendances' => $attendances,
            'attendanceSummary' => [
                'total' => $attendances->count(),
                'hadir' => $attendances->where('status', AttendanceStatus::Hadir)->count(),
                'izin' => $attendances->where('status', AttendanceStatus::Izin)->count(),
                'sakit' => $attendances->where('status', AttendanceStatus::Sakit)->count(),
                'alpa' => $attendances->where('status', AttendanceStatus::Alpa)->count(),
                'late' => $attendances->where('is_late', true)->count(),
            ],
            'positions' => $positions,
            'candidateUsers' => $user->isAdmin()
                ? User::query()
                    ->where('is_active', true)
                    ->with('organizationalField')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'ownAttendance' => $user->isAdmin() ? null : $activity->attendances()->where('user_id', $user->id)->first(),
            'ownPermissionRequest' => $user->isAdmin() ? null : $activity->permissionRequests()->where('user_id', $user->id)->first(),
            'pendingRequestCount' => $activity->permissionRequests()
                ->where('status', PermissionRequestStatus::Pending->value)
                ->count(),
            'assignedUserIds' => $assignedUserIds,
            'canCorrectAttendance' => $user->hasPermission('attendance.correct'),
        ]);
    }

    public function edit(Activity $activity): View
    {
        return view('activities.edit', [
            'activity' => $activity,
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $data = $request->validated();

        $hasAttendance = $activity->attendances()->exists();
        $waktuBerubah = $activity->start_at->toDateTimeString() !== Carbon::parse($data['start_at'])->toDateTimeString()
            || $activity->late_tolerance_minutes !== (int) $data['late_tolerance_minutes'];

        if ($hasAttendance && $waktuBerubah) {
            return back()
                ->withInput()
                ->withErrors([
                    'start_at' => 'Waktu mulai atau toleransi tidak dapat diubah karena sudah ada absensi tercatat. Gunakan koreksi absensi untuk menyesuaikan data yang terdampak.',
                ]);
        }

        if ($activity->recap_finalized_at !== null) {
            unset($data['status']);
        }

        // Status berjalan/selesai hanya boleh ditetapkan lewat aksi sesi dan finalisasi.
        if ($activity->status !== ActivityStatus::Draft) {
            unset($data['status']);
        }

        $activity->update($data);

        return redirect()
            ->route('activities.show', $activity)
            ->with('status', 'Kegiatan berhasil diperbarui.');
    }

    public function finalize(Request $request, Activity $activity, AttendanceService $attendanceService): RedirectResponse
    {
        if ($activity->openSession() !== null) {
            return back()->withErrors(['finalize' => 'Tutup sesi absensi terlebih dahulu sebelum finalisasi rekap.']);
        }

        $created = $attendanceService->finalizeRecap($activity, $request->user());

        return back()->with('status', $created.' pengurus ditetapkan alpa dan rekap kegiatan difinalisasi.');
    }
}
