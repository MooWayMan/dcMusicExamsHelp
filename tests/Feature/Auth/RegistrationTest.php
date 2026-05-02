<?php

use App\Models\ExamContact;
use App\Models\User;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Public registration flow.
// Re-enabled 2026-05-02 after a 5-day pause (between 27 Apr and 02 May the
// feature was off because there was nothing on /dashboard for a teacher to
// land on). Tests here use skipUnlessFortifyHas so they auto-skip cleanly if
// signups are ever turned off again — the inverse "is disabled" test at the
// bottom covers that state.
// ──────────────────────────────────────────

// Each enabled-state test calls skipUnlessFortifyHas at the top; the single
// inverse test at the bottom checks the feature-off state. This keeps the
// suite valid in both flag states without a global beforeEach.

test('registration screen can be rendered', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->get(route('register'))->assertOk();
});

test('an authenticated admin visiting /register is redirected away, not 403d', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/register')
        ->assertStatus(302)
        ->assertRedirect(config('fortify.home', '/dashboard'));
});

test('GET /login shows the Sign up affordance now registration is enabled', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->get('/login')
        ->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('auth/Login')
                ->where('canRegister', true)
        );
});

// ──────────────────────────────────────────
// Role-aware registration
// ──────────────────────────────────────────

test('a teacher can register and lands with role=teacher', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $response = $this->post(route('register.store'), [
        'name' => 'Tina Teacher',
        'email' => 'tina@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'tina@example.com')->firstOrFail();
    expect($user->role)->toBe('teacher');
    expect($user->isTeacher())->toBeTrue();
});

test('a parent can register and lands with role=parent', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Patricia Parent',
        'email' => 'patricia@example.com',
        'role' => 'parent',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    expect(User::where('email', 'patricia@example.com')->firstOrFail()->role)->toBe('parent');
});

test('an adult candidate can register with role=self', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Sam Self',
        'email' => 'sam@example.com',
        'role' => 'self',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    expect(User::where('email', 'sam@example.com')->firstOrFail()->role)->toBe('self');
});

test('a school admin can register with role=school_admin', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Sandra School',
        'email' => 'sandra@example.com',
        'role' => 'school_admin',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    expect(User::where('email', 'sandra@example.com')->firstOrFail()->role)->toBe('school_admin');
});

// ──────────────────────────────────────────
// Validation — admin self-promo blocked, role required and known
// ──────────────────────────────────────────

test('self-registration cannot promote to admin', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Sneaky Admin',
        'email' => 'sneaky@example.com',
        'role' => 'admin',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('role');

    expect(User::where('email', 'sneaky@example.com')->exists())->toBeFalse();
});

test('an unknown role is rejected', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Wizard Person',
        'email' => 'wizard@example.com',
        'role' => 'wizard',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('role');
});

test('role is required', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Roleless Rita',
        'email' => 'rita@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('role');
});

test('password must be confirmed', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Mismatch Mike',
        'email' => 'mike@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'different456',
    ])->assertSessionHasErrors('password');
});

test('email must be unique', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Late Larry',
        'email' => 'taken@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

// ──────────────────────────────────────────
// exam_contacts linkage — best-effort mirroring of hubspot_contact_id
// ──────────────────────────────────────────

test('registration mirrors hubspot_contact_id from a matching exam_contacts row', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    ExamContact::create([
        'name' => 'Pre-existing Person',
        'email' => 'crm@example.com',
        'hubspot_contact_id' => 'HS-12345',
        'source' => 'trinity_csv',
    ]);

    $this->post(route('register.store'), [
        'name' => 'Pre-existing Person',
        'email' => 'crm@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    expect(User::where('email', 'crm@example.com')->firstOrFail()->hubspot_contact_id)
        ->toBe('HS-12345');
});

test('registration succeeds when no exam_contacts row matches', function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->post(route('register.store'), [
        'name' => 'Nobody Knows Me',
        'email' => 'stranger@example.com',
        'role' => 'teacher',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect();

    expect(User::where('email', 'stranger@example.com')->firstOrFail()->hubspot_contact_id)->toBeNull();
});

// ──────────────────────────────────────────
// Belt-and-braces inverse — kept symmetric with the original test so future
// flicks of the feature flag don't leave the suite blind to either state.
// Skips when registration IS enabled (i.e. right now), runs when it's off.
// ──────────────────────────────────────────

test('registration is disabled and returns 404', function () {
    if (Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration is enabled — see tests above.');
    }

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});