<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\AttendanceStatus;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\User;
use App\Services\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_records_attendance(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);

        $response = $this->actingAs($user)->postJson(route('scan.store'), [
            'token' => $this->issueToken($session),
        ]);

        $response->assertOk()
            ->assertJson(['successful' => true, 'is_late' => false])
            ->assertJsonPath('status', 'Hadir');

        $this->assertDatabaseHas('attendances', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => AttendanceStatus::Hadir->value,
            'is_late' => false,
        ]);
    }

    public function test_scanning_twice_only_records_one_attendance(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);
        $token = $this->issueToken($session);

        $this->actingAs($user)->postJson(route('scan.store'), ['token' => $token])->assertOk();
        $this->actingAs($user)->postJson(route('scan.store'), ['token' => $token])->assertOk();

        $this->assertSame(1, Attendance::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->count());
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);
        $token = $this->issueToken($session);

        $this->travel(21)->seconds();

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $token])
            ->assertStatus(422)
            ->assertJson(['successful' => false]);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_revoked_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);
        $token = $this->issueToken($session);

        app(QrTokenService::class)->revokeForSession($session);

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $token])
            ->assertStatus(422)
            ->assertJson(['successful' => false]);
    }

    public function test_closed_session_rejects_token(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);
        $token = $this->issueToken($session);

        $session->update(['status' => AttendanceSession::STATUS_CLOSED, 'closed_at' => now()]);

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $token])
            ->assertStatus(422)
            ->assertJson(['successful' => false]);
    }

    public function test_late_scan_is_marked_late(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity([
            'start_at' => now()->subMinutes(40),
            'late_tolerance_minutes' => 10,
        ]);
        $session = $this->openSession($activity);

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $this->issueToken($session)])
            ->assertOk()
            ->assertJson(['successful' => true, 'is_late' => true]);

        $this->assertDatabaseHas('attendances', [
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'status' => AttendanceStatus::Hadir->value,
            'is_late' => true,
        ]);
    }

    public function test_scan_is_rejected_after_recap_finalized(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity(['recap_finalized_at' => now()]);
        $session = $this->openSession($activity);

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $this->issueToken($session)])
            ->assertStatus(422)
            ->assertJson(['successful' => false]);
    }

    public function test_draft_activity_rejects_scan(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity(['status' => ActivityStatus::Draft]);
        $session = $this->openSession($activity);

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $this->issueToken($session)])
            ->assertStatus(422);
    }

    public function test_token_endpoint_rotates_and_revokes_previous_token(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);
        $oldToken = $this->issueToken($session);

        $response = $this->actingAs($admin)
            ->getJson(route('attendance-sessions.token', $session))
            ->assertOk()
            ->assertJsonStructure(['token', 'svg', 'expires_at', 'ttl_seconds']);

        $newToken = $response->json('token');
        $this->assertNotSame($oldToken, $newToken);

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $oldToken])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('scan.store'), ['token' => $newToken])
            ->assertOk();
    }

    public function test_pengurus_cannot_request_qr_token(): void
    {
        $user = User::factory()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);

        $this->actingAs($user)
            ->getJson(route('attendance-sessions.token', $session))
            ->assertForbidden();
    }

    public function test_token_is_stored_hashed_only(): void
    {
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);
        $token = $this->issueToken($session);

        $this->assertDatabaseMissing('qr_tokens', ['token_hash' => $token]);
        $this->assertDatabaseHas('qr_tokens', ['token_hash' => hash('sha256', $token)]);
    }

    public function test_closing_session_marks_activity_completed_and_revokes_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeActivity();
        $session = $this->openSession($activity);
        $token = $this->issueToken($session);

        $this->actingAs($admin)
            ->post(route('activities.session.close', $activity))
            ->assertSessionHasNoErrors();

        $activity->refresh();
        $session->refresh();

        $this->assertSame(ActivityStatus::Completed, $activity->status);
        $this->assertNull($activity->recap_finalized_at);
        $this->assertSame(AttendanceSession::STATUS_CLOSED, $session->status);
        $this->assertNull(app(QrTokenService::class)->findUsable($token));
    }

    private function makeActivity(array $attributes = []): Activity
    {
        return Activity::factory()->create([
            'status' => ActivityStatus::Published,
            'start_at' => now()->subMinutes(5),
            'end_at' => now()->addHour(),
            'late_tolerance_minutes' => 15,
            ...$attributes,
        ]);
    }

    private function openSession(Activity $activity): AttendanceSession
    {
        return AttendanceSession::factory()->create([
            'activity_id' => $activity->id,
            'status' => AttendanceSession::STATUS_OPEN,
            'opened_at' => now(),
        ]);
    }

    private function issueToken(AttendanceSession $session): string
    {
        return app(QrTokenService::class)->issue($session)['token'];
    }
}
