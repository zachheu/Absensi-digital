<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\AttendanceSession;
use App\Services\QrTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceSessionController extends Controller
{
    public function open(Request $request, Activity $activity, QrTokenService $qrTokenService): RedirectResponse
    {
        if ($activity->status === ActivityStatus::Draft) {
            return back()->withErrors(['session' => 'Publikasikan kegiatan terlebih dahulu sebelum membuka absensi.']);
        }

        if ($activity->recap_finalized_at !== null) {
            return back()->withErrors(['session' => 'Rekap kegiatan ini sudah difinalisasi dan tidak dapat dibuka kembali.']);
        }

        $opened = DB::transaction(function () use ($request, $activity): bool {
            $activity->refresh();

            if ($activity->openSession() !== null) {
                return false;
            }

            $activity->sessions()->create([
                'status' => AttendanceSession::STATUS_OPEN,
                'opened_by' => $request->user()->id,
                'opened_at' => now(),
            ]);

            $activity->update(['status' => ActivityStatus::Ongoing]);

            return true;
        });

        return $opened
            ? back()->with('status', 'Sesi absensi dibuka. QR Code dinamis siap ditampilkan.')
            : back()->withErrors(['session' => 'Sesi absensi sudah terbuka untuk kegiatan ini.']);
    }

    public function close(Request $request, Activity $activity, QrTokenService $qrTokenService): RedirectResponse
    {
        $session = $activity->openSession();

        if ($session === null) {
            return back()->withErrors(['session' => 'Tidak ada sesi absensi yang sedang terbuka.']);
        }

        DB::transaction(function () use ($request, $activity, $session, $qrTokenService): void {
            $qrTokenService->revokeForSession($session);

            $session->update([
                'status' => AttendanceSession::STATUS_CLOSED,
                'closed_by' => $request->user()->id,
                'closed_at' => now(),
            ]);

            // Sesi ditutup berarti rangkaian kegiatan selesai. Finalisasi rekap
            // tetap dilakukan terpisah untuk menetapkan status alpa.
            $activity->update(['status' => ActivityStatus::Completed]);
        });

        return back()->with('status', 'Sesi absensi ditutup dan kegiatan ditandai selesai. Rekap masih dapat difinalisasi.');
    }

    public function show(Activity $activity): View|RedirectResponse
    {
        $session = $activity->openSession();

        if ($session === null) {
            return redirect()
                ->route('activities.show', $activity)
                ->withErrors(['session' => 'Buka sesi absensi terlebih dahulu untuk menampilkan QR Code.']);
        }

        return view('attendance.qr', [
            'activity' => $activity,
            'session' => $session,
            'ttl' => (int) config('himatif.qr_ttl_seconds'),
        ]);
    }

    public function token(AttendanceSession $session, QrTokenService $qrTokenService): JsonResponse
    {
        abort_unless($session->isOpen(), 409, 'Sesi absensi sudah ditutup.');

        $token = $qrTokenService->rotate($session);

        return response()->json([
            'token' => $token['token'],
            'svg' => $token['svg'],
            'issued_at' => now()->toIso8601String(),
            'expires_at' => $token['expires_at']->toIso8601String(),
            'ttl_seconds' => (int) config('himatif.qr_ttl_seconds'),
        ]);
    }
}
