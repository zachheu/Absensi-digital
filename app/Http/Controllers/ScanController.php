<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Request $request): View
    {
        return view('attendance.scan', [
            'activeActivities' => Activity::query()
                ->visible()
                ->whereHas('sessions', fn ($query) => $query->where('status', 'open'))
                ->orderBy('start_at')
                ->get(),
        ]);
    }

    public function store(Request $request, AttendanceService $attendanceService): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
        ], [
            'token.required' => 'Token QR tidak ditemukan. Pindai ulang QR Code.',
            'token.size' => 'Format token QR tidak dikenali.',
            'token.regex' => 'Format token QR tidak dikenali.',
        ]);

        $result = $attendanceService->recordQrAttendance($request->user(), $validated['token']);

        if ($request->expectsJson()) {
            return response()->json([
                'successful' => $result->successful,
                'message' => $result->message,
                'status' => $result->statusLabel(),
                'is_late' => $result->isLate(),
                'activity' => $result->activity()?->name,
                'check_in_at' => $result->attendance?->check_in_at?->toIso8601String(),
            ], $result->successful ? 200 : 422);
        }

        return redirect()
            ->route('scan.index')
            ->with('scan_result', [
                'successful' => $result->successful,
                'message' => $result->message,
                'status' => $result->statusLabel(),
                'is_late' => $result->isLate(),
                'activity' => $result->activity()?->name,
                'check_in_at' => $result->attendance?->check_in_at?->format('d M Y H:i'),
            ]);
    }
}
