<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('attendance.correct', function (User $user): bool {
            return $user->hasPermission(Permission::ATTENDANCE_CORRECT);
        });

        Gate::define('manage-data', function (User $user): bool {
            return $user->role === UserRole::Admin;
        });
    }
}
