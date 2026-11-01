<?php

namespace Tests\Feature;

use App\Enums\StructuralPosition;
use App\Enums\UserRole;
use App\Models\CommitteePosition;
use App\Models\OrganizationalField;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\CommitteePositionSeeder;
use Database\Seeders\OrganizationalFieldSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private array $managedEnvKeys = [
        'HIMATIF_ADMIN_NPP',
        'HIMATIF_ADMIN_FIELD',
        'HIMATIF_ADMIN_STRUCTURAL_POSITION',
        'HIMATIF_ADMIN_NPM',
        'HIMATIF_ADMIN_NAME',
        'HIMATIF_ADMIN_PASSWORD',
        'HIMATIF_ADMIN_RESET_PASSWORD',
    ];

    protected function tearDown(): void
    {
        foreach ($this->managedEnvKeys as $key) {
            $this->forgetEnv($key);
        }

        parent::tearDown();
    }

    public function test_organizational_field_seeder_creates_five_units(): void
    {
        $this->seed(OrganizationalFieldSeeder::class);

        $this->assertSame(5, OrganizationalField::query()->count());
        $this->assertSame(
            ['CNB', 'HRDO', 'PR', 'RE', 'STAFF_INTI'],
            OrganizationalField::query()->orderBy('code')->pluck('code')->all(),
        );

        $staffInti = OrganizationalField::query()->where('code', 'STAFF_INTI')->firstOrFail();
        $this->assertSame('Staff Inti', $staffInti->name);
        $this->assertSame('Unit pimpinan himpunan', $staffInti->description);
    }

    public function test_committee_position_seeder_creates_twelve_positions(): void
    {
        $this->seed(CommitteePositionSeeder::class);

        $this->assertSame(12, CommitteePosition::query()->count());
        $this->assertSame(12, CommitteePosition::query()->where('is_active', true)->count());
    }

    public function test_permission_seeder_creates_attendance_correct_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => Permission::ATTENDANCE_CORRECT,
        ]);
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed(OrganizationalFieldSeeder::class);
        $this->seed(OrganizationalFieldSeeder::class);
        $this->seed(CommitteePositionSeeder::class);
        $this->seed(CommitteePositionSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->assertSame(5, OrganizationalField::query()->count());
        $this->assertSame(12, CommitteePosition::query()->count());
        $this->assertSame(2, Permission::query()->count());
    }

    public function test_admin_seeder_skips_creation_when_environment_is_incomplete(): void
    {
        $this->seed(OrganizationalFieldSeeder::class);
        $this->setEnv('HIMATIF_ADMIN_NPP', '1987654321');
        $this->forgetEnv('HIMATIF_ADMIN_NPM');

        $this->seed(AdminUserSeeder::class);

        $this->assertSame(0, User::query()->count());
    }

    public function test_admin_seeder_creates_admin_and_does_not_reset_password_without_flag(): void
    {
        $this->seed(OrganizationalFieldSeeder::class);
        $this->setAdminEnv('password-awal');

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('npp', '1987654321')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame('2113241015', $admin->npm);
        $this->assertSame('Admin HIMATIF', $admin->name);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('password-awal', $admin->password));

        $this->setEnv('HIMATIF_ADMIN_PASSWORD', 'password-baru');
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->count());
        $admin->refresh();
        $this->assertTrue(Hash::check('password-awal', $admin->password));
    }

    public function test_admin_seeder_assigns_configured_organizational_field(): void
    {
        $this->seed(OrganizationalFieldSeeder::class);
        $this->setAdminEnv('password-awal');
        $this->setEnv('HIMATIF_ADMIN_FIELD', 'STAFF_INTI');

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('npp', '1987654321')->firstOrFail();

        $this->assertSame('STAFF_INTI', $admin->organizationalField?->code);
    }

    public function test_admin_seeder_assigns_configured_structural_position(): void
    {
        $this->seed(OrganizationalFieldSeeder::class);
        $this->setAdminEnv('password-awal');
        $this->setEnv('HIMATIF_ADMIN_STRUCTURAL_POSITION', 'sekretaris_umum');

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('npp', '1987654321')->firstOrFail();

        $this->assertSame(StructuralPosition::SekretarisUmum, $admin->structural_position);
    }

    public function test_admin_seeder_resets_password_when_flag_enabled(): void
    {
        $this->seed(OrganizationalFieldSeeder::class);
        $this->setAdminEnv('password-awal');

        $this->seed(AdminUserSeeder::class);

        $this->setEnv('HIMATIF_ADMIN_PASSWORD', 'password-baru');
        $this->setEnv('HIMATIF_ADMIN_RESET_PASSWORD', 'true');

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('npp', '1987654321')->firstOrFail();

        $this->assertSame(1, User::query()->count());
        $this->assertTrue(Hash::check('password-baru', $admin->password));
    }

    private function setAdminEnv(string $password): void
    {
        $this->setEnv('HIMATIF_ADMIN_NPP', '1987654321');
        $this->setEnv('HIMATIF_ADMIN_NPM', '2113241015');
        $this->setEnv('HIMATIF_ADMIN_NAME', 'Admin HIMATIF');
        $this->setEnv('HIMATIF_ADMIN_PASSWORD', $password);
        $this->forgetEnv('HIMATIF_ADMIN_RESET_PASSWORD');
        $this->forgetEnv('HIMATIF_ADMIN_STRUCTURAL_POSITION');
    }

    private function setEnv(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key.'='.$value);
    }

    private function forgetEnv(string $key): void
    {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }
}
