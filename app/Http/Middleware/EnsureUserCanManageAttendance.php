<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanManageAttendance
{
    /**
     * Admin atau akun yang diberi permission attendance.manage boleh melintas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (! $user->isAdmin() && ! $user->hasPermission(Permission::ATTENDANCE_MANAGE))) {
            abort(403, 'Anda tidak memiliki izin untuk mengelola absensi kegiatan ini.');
        }

        return $next($request);
    }
}
