<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $attendances = $request->user()
            ->attendances()
            ->with('activity')
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('attendance.mine', [
            'attendances' => $attendances,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }
}
