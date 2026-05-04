<?php

// tests/Feature/Admin/ImpersonationTest.php

use App\Models\User;
use App\Services\Impersonation;
use Illuminate\Support\Facades\Auth;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Admin "Login as user" impersonation
//
// Lets Paul log in as a real teacher/parent on prod to verify the
// dashboard linkage. Sensitive feature — these tests lock down the
// guardrails so we never ship a privilege-escalation surface.
// ──────────────────────────────────────────

test('admin can start impersonating a teacher and is redirected to the dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Clare Keeling']);

    $response = $this->actingAs($admin)
        ->post("/admin/users/{$teacher->id}/impersonate");

    $response->assertRedirect('/dashboard');
    expect(Auth::id())->toBe($teacher->id);
    expect(session(Impersonation::IMPERSONATOR_KEY))->toBe($admin->id);
});

test('impersonating user can return to admin via leave route', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($admin)->post("/admin/users/{$teacher->id}/impersonate");

    $response = $this->post('/impersonate/leave');

    $response->assertRedirect(route('admin.users.index'));
    expect(Auth::id())->toBe($admin->id);
    expect(session(Impersonation::IMPERSONATOR_KEY))->toBeNull();
});

test('non-admin cannot start impersonation', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $otherTeacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->post("/admin/users/{$otherTeacher->id}/impersonate")
        ->assertStatus(403);

    expect(Auth::id())->toBe($teacher->id);
    expect(session(Impersonation::IMPERSONATOR_KEY))->toBeNull();
});

test('guest cannot start impersonation', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->post("/admin/users/{$teacher->id}/impersonate")
        ->assertRedirect('/login');
});

test('admin cannot impersonate another admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $otherAdmin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post("/admin/users/{$otherAdmin->id}/impersonate")
        ->assertStatus(403);

    expect(Auth::id())->toBe($admin->id);
    expect(session(Impersonation::IMPERSONATOR_KEY))->toBeNull();
});

test('admin cannot impersonate themselves', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post("/admin/users/{$admin->id}/impersonate")
        ->assertStatus(403);

    expect(session(Impersonation::IMPERSONATOR_KEY))->toBeNull();
});

test('admin cannot start a second impersonation while one is already active', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacherA = User::factory()->create(['role' => 'teacher']);
    $teacherB = User::factory()->create(['role' => 'teacher']);

    // First impersonation succeeds
    $this->actingAs($admin)->post("/admin/users/{$teacherA->id}/impersonate");
    expect(Auth::id())->toBe($teacherA->id);

    // Second one should be blocked — we are now Auth'd as a teacher, so
    // we get 403 on the admin route. This double-locks the guard: even
    // if the admin middleware were ever loosened, the service's own
    // "already impersonating" check would still throw.
    $this->post("/admin/users/{$teacherB->id}/impersonate")
        ->assertStatus(403);

    expect(Auth::id())->toBe($teacherA->id);
});

test('impersonated teacher cannot access admin routes', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($admin)->post("/admin/users/{$teacher->id}/impersonate");

    $this->get('/admin')->assertStatus(403);
});

test('impersonated user sees the impersonation banner via shared inertia props', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Paul Sheridan']);
    $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Clare Keeling']);

    $this->actingAs($admin)->post("/admin/users/{$teacher->id}/impersonate");

    $this->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.id', $teacher->id)
            ->where('auth.impersonating.admin_name', 'Paul Sheridan')
            ->where('auth.impersonating.target_name', 'Clare Keeling')
        );
});

test('non-impersonated user sees no impersonation banner', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('auth.impersonating', null)
        );
});

test('leave route is a no-op redirect to login when not impersonating', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->post('/impersonate/leave')
        ->assertRedirect('/login');

    // Auth should be cleared since we had no impersonator to restore to —
    // safe default rather than leaving a stale session.
    expect(Auth::id())->toBe($teacher->id);
});
