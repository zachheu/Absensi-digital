<?php

namespace Tests\Feature;

use App\Enums\StructuralPosition;
use App\Enums\UserRole;
use App\Models\OrganizationalField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_structural_position(): void
    {
        $admin = User::factory()->admin()->create();
        $field = OrganizationalField::factory()->create(['code' => 'STAFF_INTI']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'npp' => '1.3.3.04.099',
            'npm' => '2113241099',
            'name' => 'Pengurus Baru',
            'password' => 'rahasia-sekali',
            'organizational_field_id' => $field->id,
            'structural_position' => StructuralPosition::BendaharaUmum->value,
            'role' => UserRole::Pengurus->value,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('npp', '1.3.3.04.099')->firstOrFail();

        $this->assertSame(StructuralPosition::BendaharaUmum, $user->structural_position);
        $this->assertSame('STAFF_INTI', $user->organizationalField?->code);
    }

    public function test_admin_can_update_and_clear_structural_position(): void
    {
        $admin = User::factory()->admin()->create();
        $field = OrganizationalField::factory()->create(['code' => 'STAFF_INTI']);
        $user = User::factory()->create([
            'organizational_field_id' => $field->id,
            'structural_position' => StructuralPosition::Walikota,
        ]);

        $payload = [
            'npp' => $user->npp,
            'npm' => $user->npm,
            'name' => $user->name,
            'email' => $user->email,
            'organizational_field_id' => $field->id,
            'role' => UserRole::Pengurus->value,
        ];

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                ...$payload,
                'structural_position' => StructuralPosition::SekretarisUmum->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(StructuralPosition::SekretarisUmum, $user->fresh()->structural_position);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                ...$payload,
                'structural_position' => '',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertNull($user->fresh()->structural_position);
    }

    public function test_structural_position_must_be_a_known_value(): void
    {
        $admin = User::factory()->admin()->create();
        $field = OrganizationalField::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'npp' => '1.3.3.04.098',
                'npm' => '2113241098',
                'name' => 'Pengurus Salah Jabatan',
                'password' => 'rahasia-sekali',
                'organizational_field_id' => $field->id,
                'structural_position' => 'ketua_umum',
                'role' => UserRole::Pengurus->value,
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors('structural_position');

        $this->assertDatabaseMissing('users', ['npp' => '1.3.3.04.098']);
    }
}
