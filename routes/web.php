<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CommitteeMemberController;
use App\Http\Controllers\Admin\CommitteePositionController;
use App\Http\Controllers\Admin\OrganizationalFieldController;
use App\Http\Controllers\Admin\PermissionGrantController;
use App\Http\Controllers\Admin\PermissionRequestReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MyAttendanceController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:'.config('himatif.login_attempts_per_minute').',1')->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/create', [ActivityController::class, 'create'])->middleware('admin')->name('activities.create');
    Route::get('/activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');

    Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('/scan', [ScanController::class, 'store'])
        ->middleware('throttle:'.config('himatif.scan_attempts_per_minute').',1')
        ->name('scan.store');

    Route::get('/my-attendances', [MyAttendanceController::class, 'index'])->name('attendances.mine');

    Route::get('/permission-requests', [PermissionRequestController::class, 'index'])->name('permission-requests.index');
    Route::get('/permission-requests/create', [PermissionRequestController::class, 'create'])->name('permission-requests.create');
    Route::post('/permission-requests', [PermissionRequestController::class, 'store'])->name('permission-requests.store');

    Route::get('/attendances/{attendance}/koreksi', [CorrectionController::class, 'edit'])->name('attendances.correct.edit');
    Route::put('/attendances/{attendance}', [CorrectionController::class, 'update'])->name('attendances.correct.update');

    Route::middleware('admin')->group(function (): void {
        Route::resource('admin/users', UserController::class)
            ->except(['show', 'destroy'])
            ->names('admin.users');
        Route::patch('/admin/users/{user}/status', [UserController::class, 'toggleStatus'])->name('admin.users.status');
        Route::put('/admin/users/{user}/permissions', [PermissionGrantController::class, 'update'])->name('admin.users.permissions');

        Route::get('/admin/fields', [OrganizationalFieldController::class, 'index'])->name('admin.fields.index');
        Route::post('/admin/fields', [OrganizationalFieldController::class, 'store'])->name('admin.fields.store');
        Route::put('/admin/fields/{organizationalField}', [OrganizationalFieldController::class, 'update'])->name('admin.fields.update');

        Route::get('/admin/positions', [CommitteePositionController::class, 'index'])->name('admin.positions.index');
        Route::post('/admin/positions', [CommitteePositionController::class, 'store'])->name('admin.positions.store');
        Route::put('/admin/positions/{committeePosition}', [CommitteePositionController::class, 'update'])->name('admin.positions.update');

        Route::post('/activities', [ActivityController::class, 'store'])->name('activities.store');
        Route::get('/activities/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::post('/activities/{activity}/finalize', [ActivityController::class, 'finalize'])->name('activities.finalize');

        Route::post('/activities/{activity}/committee-members', [CommitteeMemberController::class, 'store'])->name('activities.committee.store');
        Route::put('/activities/{activity}/committee-members', [CommitteeMemberController::class, 'update'])->name('activities.committee.update');
        Route::delete('/activities/{activity}/committee-members/{committeeMember}', [CommitteeMemberController::class, 'destroy'])->name('activities.committee.destroy');

        Route::post('/activities/{activity}/session', [AttendanceSessionController::class, 'open'])->name('activities.session.open');
        Route::post('/activities/{activity}/session/close', [AttendanceSessionController::class, 'close'])->name('activities.session.close');
        Route::get('/activities/{activity}/qr', [AttendanceSessionController::class, 'show'])->name('activities.qr');
        Route::get('/attendance-sessions/{session}/token', [AttendanceSessionController::class, 'token'])->name('attendance-sessions.token');

        Route::get('/admin/permission-requests', [PermissionRequestReviewController::class, 'index'])->name('admin.permission-requests.index');
        Route::post('/admin/permission-requests/{permissionRequest}/approve', [PermissionRequestReviewController::class, 'approve'])->name('admin.permission-requests.approve');
        Route::post('/admin/permission-requests/{permissionRequest}/reject', [PermissionRequestReviewController::class, 'reject'])->name('admin.permission-requests.reject');

        Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audits.index');
        Route::get('/attendances/{attendance}/audit', [AuditLogController::class, 'forAttendance'])->name('admin.audits.attendance');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/excel', [ReportController::class, 'excel'])->name('reports.excel');
        Route::get('/reports/export/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    });
});
