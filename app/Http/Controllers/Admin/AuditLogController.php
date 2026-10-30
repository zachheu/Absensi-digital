<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $audits = AttendanceAudit::query()
            ->with(['actor', 'user', 'activity', 'attendance'])
            ->when($request->filled('activity'), fn ($query) => $query->where('activity_id', $request->integer('activity')))
            ->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->integer('user')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.audits.index', [
            'audits' => $audits,
            'activities' => Activity::query()->orderByDesc('start_at')->get(['id', 'name', 'start_at']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'npp']),
        ]);
    }

    public function forAttendance(Request $request, Attendance $attendance): View
    {
        $attendance->load(['user', 'activity']);

        return view('admin.audits.attendance', [
            'attendance' => $attendance,
            'audits' => $attendance->audits()->with('actor')->orderByDesc('created_at')->get(),
        ]);
    }
}
