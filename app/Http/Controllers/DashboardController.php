<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Enums\AttendanceStatus;
use App\Enums\PermissionRequestStatus;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->is_active) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        return $this->pengurusDashboard($user);
    }

    private function adminDashboard(): View
    {
        $ongoingActivities = Activity::query()
            ->withCount('attendances')
            ->where(function ($query): void {
                $query->where('status', ActivityStatus::Ongoing->value)
                    ->orWhere(function ($query): void {
                        $query->where('start_at', '<=', now())
                            ->where('end_at', '>=', now());
                    });
            })
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        return view('dashboard.admin', [
            'activeUserCount' => User::query()->where('is_active', true)->count(),
            'activityCount' => Activity::query()->count(),
            'attendanceCount' => Attendance::query()->where('status', AttendanceStatus::Hadir->value)->count(),
            'lateCount' => Attendance::query()->where('is_late', true)->count(),
            'pendingRequestCount' => PermissionRequest::query()
                ->where('status', PermissionRequestStatus::Pending->value)
                ->count(),
            'ongoingActivities' => $ongoingActivities,
            'recentAttendances' => Attendance::query()
                ->with(['user.organizationalField', 'activity'])
                ->latest('check_in_at')
                ->limit(8)
                ->get(),
        ]);
    }

    private function pengurusDashboard(User $user): View
    {
        $activities = Activity::query()
            ->visible()
            ->where('end_at', '>=', now()->subDays(7))
            ->with(['attendances' => fn ($query) => $query->where('user_id', $user->id)])
            ->with(['permissionRequests' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderBy('start_at')
            ->limit(6)
            ->get();

        return view('dashboard.pengurus', [
            'user' => $user->loadMissing('organizationalField', 'permissions'),
            'activities' => $activities,
            'recentAttendances' => $user->attendances()
                ->with('activity')
                ->latest('created_at')
                ->limit(6)
                ->get(),
            'permissionRequests' => $user->permissionRequests()
                ->with('activity')
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
