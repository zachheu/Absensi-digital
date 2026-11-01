<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::query()->updateOrCreate(
            ['name' => Permission::ATTENDANCE_CORRECT],
            ['description' => 'Mengoreksi data absensi pengurus pada kegiatan yang diikuti.']
        );

        Permission::query()->updateOrCreate(
            ['name' => Permission::ATTENDANCE_MANAGE],
            ['description' => 'Mengelola sesi absensi, panitia, dan rekap kegiatan yang ditugaskan.']
        );
    }
}
