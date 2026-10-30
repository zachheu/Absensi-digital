<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OrganizationalField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_for_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_npp_and_password(): void
    {
        $user = User::factory()->create([
            'npp' => '1987654321',
            'password' => 'rahasia-sekali',
        ]);

        $response = $this->post(route('login.store'), [
            'npp' => '1987654321',
            'password' => 'rahasia-sekali',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['npp' => '1987654321']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'npp' => '1987654321',
                'password' => 'password-salah',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('npp');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create(['npp' => '1987654321']);

        $this->post(route('login.store'), [
            'npp' => '1987654321',
            'password' => 'password',
        ])->assertSessionHasErrors('npp');

        $this->assertGuest();
    }

    public function test_inactive_user_is_logged_out_by_middleware(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_pengurus_cannot_access_admin_pages(): void
    {
        $user = User::factory()->create(['role' => UserRole::Pengurus]);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_pages(): void
    {
        $admin = User::factory()->admin()->create([
            'organizational_field_id' => OrganizationalField::factory(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk();
    }
}
