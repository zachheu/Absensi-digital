<?php

namespace Database\Seeders;

use App\Enums\StructuralPosition;
use App\Enums\UserRole;
use App\Models\OrganizationalField;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Akun admin awal diambil dari environment agar tidak ada kredensial
     * default yang tertanam di source code.
     */
    public function run(): void
    {
        $npp = env('HIMATIF_ADMIN_NPP');
        $npm = env('HIMATIF_ADMIN_NPM');
        $name = env('HIMATIF_ADMIN_NAME');
        $password = env('HIMATIF_ADMIN_PASSWORD');

        if (blank($npp) || blank($npm) || blank($name) || blank($password)) {
            $this->command?->warn('Akun admin tidak dibuat: isi HIMATIF_ADMIN_NPP, HIMATIF_ADMIN_NPM, HIMATIF_ADMIN_NAME, dan HIMATIF_ADMIN_PASSWORD di .env.');

            return;
        }

        $fieldCode = env('HIMATIF_ADMIN_FIELD', 'HRDO');

        $field = OrganizationalField::query()->where('code', $fieldCode)->first()
            ?? OrganizationalField::query()->where('code', 'HRDO')->first()
            ?? OrganizationalField::query()->first();

        $structuralPosition = StructuralPosition::tryFrom((string) env('HIMATIF_ADMIN_STRUCTURAL_POSITION', ''));

        $user = User::query()->firstOrNew(['npp' => $npp]);

        $user->fill([
            'npm' => $npm,
            'name' => $name,
            'email' => $user->email ?? $npp.'@himatif.local',
            'role' => UserRole::Admin,
            'organizational_field_id' => $field?->id,
            'is_active' => true,
        ]);

        if ($structuralPosition !== null) {
            $user->structural_position = $structuralPosition;
        }

        if (! $user->exists || filled(env('HIMATIF_ADMIN_RESET_PASSWORD'))) {
            $user->password = $password;
        }

        $user->save();

        $this->command?->info('Akun admin '.$user->npp.' siap digunakan.');
    }
}
