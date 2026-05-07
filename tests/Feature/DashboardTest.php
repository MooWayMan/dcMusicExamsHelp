<?php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Instrument;
use App\Models\Order;
use App\Models\PrizeDraw;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

// ──────────────────────────────────────────────────────────────────────────
// Teacher prize draw widget
// ──────────────────────────────────────────────────────────────────────────
// The dashboard's prize-draw card has three display rules for the winner
// name (in priority order):
//   1. School admin contact → linked school name (Pulse Music etc.)
//   2. show_full_name = true → full name
//   3. otherwise → "First L"
//
// And it surfaces a live ticket count for the signed-in teacher in the
// current undrawn quarter, climbing as they enter more candidates.

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::create(2026, 5, 7, 12, 0, 0));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * Compact helper — registers a teacher draw winner under a given contact
 * name with sensible defaults so each test only has to declare what differs.
 */
function pdTeacherDraw(string $winnerName, int $winnerEntries = 5, int $totalTickets = 30, int $quarter = 1, int $year = 2026): PrizeDraw
{
    $admin = User::factory()->create(['role' => 'admin']);
    return PrizeDraw::create([
        'type' => 'teacher',
        'quarter' => $quarter,
        'year' => $year,
        'winner_name' => $winnerName,
        'winner_entries' => $winnerEntries,
        'total_tickets' => $totalTickets,
        'all_eligible' => [],
        'drawn_by' => $admin->id,
    ]);
}

// Index notes: today is fixed at 7 May 2026 (Q2). The controller always
// prepends the current undrawn quarter at quarters.0 — so a real Q1 2026
// draw row lives at quarters.1.

test('school admin winner is rendered as the school name, not the personal name', function () {
    $contact = ExamContact::create(['name' => 'Daniel Rogers']);
    $contact->addType('school_admin');
    $school = School::create(['name' => 'Pulse Music School']);
    $contact->schools()->attach($school);

    pdTeacherDraw('Daniel Rogers');

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($p) => $p
        ->where('teacherPrizeDraw.quarters.1.has_winner', true)
        ->where('teacherPrizeDraw.quarters.1.winner_display_name', 'Pulse Music School'));
});

test('teacher with show_full_name=true is rendered with full name', function () {
    ExamContact::create([
        'name' => 'Helen Hodgkiss',
        'show_full_name' => true,
    ])->addType('teacher');

    pdTeacherDraw('Helen Hodgkiss');

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($p) => $p
        ->where('teacherPrizeDraw.quarters.1.winner_display_name', 'Helen Hodgkiss'));
});

test('teacher without show_full_name consent is rendered as First L', function () {
    ExamContact::create([
        'name' => 'Helen Hodgkiss',
        'show_full_name' => false,
    ])->addType('teacher');

    pdTeacherDraw('Helen Hodgkiss');

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($p) => $p
        ->where('teacherPrizeDraw.quarters.1.winner_display_name', 'Helen H'));
});

test('current quarter is always present even when no draw exists yet', function () {
    // No PrizeDraw rows at all.
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($p) => $p
        ->where('teacherPrizeDraw.quarters.0.year', 2026)
        ->where('teacherPrizeDraw.quarters.0.quarter', 2)  // May 2026 = Q2
        ->where('teacherPrizeDraw.quarters.0.has_winner', false)
        ->where('teacherPrizeDraw.quarters.0.drawn_at', null));
});

test('NO_SHOW entries do not count toward the teacher dashboard ticket count', function () {
    // NO_SHOW is excluded from the dashboard ticket count alongside CANCELLED
    // — both signal "no exam was actually taken", so neither earns a draw
    // ticket. (The teacher's volume tally — Bronze/Silver/Gold badge —
    // still counts NO_SHOW; that's tested separately via the QuarterEnd
    // controller flow.)
    $teacherEmail = 'helen@example.com';
    $contact = ExamContact::create([
        'name' => 'Helen Hodgkiss',
        'email' => $teacherEmail,
    ]);
    $contact->addType('teacher');

    $user = User::factory()->create(['email' => $teacherEmail]);

    $piano = Instrument::firstOrCreate(['name' => 'Piano']);
    $thisQuarter = Carbon::create(2026, 4, 15);

    $entry = function (Carbon $date, ?string $notes = null) use ($piano) {
        $order = Order::create([
            'trinity_order_number' => '1-T-'.uniqid('', true),
            'delivery_method' => 'F2F',
            'subject_area' => 'Music',
            'candidates' => 1,
            'order_status' => 'Delivered',
            'requested_start_date' => $date,
        ]);
        return ExamEntry::create([
            'order_id' => $order->id,
            'candidate_name' => 'Test Student',
            'instrument_id' => $piano->id,
            'grade' => '1',
            'subject_area' => 'Piano',
            'delivery_method' => 'F2F',
            'exam_date' => $date,
            'result' => 'Pass',
            'score' => 70,
            'teacher_name' => 'Helen Hodgkiss',
            'notes' => $notes,
        ]);
    };

    // Three entries — one each of: in-good-standing, CANCELLED, NO_SHOW.
    // Only the first should count.
    $entry($thisQuarter);
    $entry($thisQuarter, \App\Models\ExamEntry::NOTE_CANCELLED);
    $entry($thisQuarter, \App\Models\ExamEntry::NOTE_NO_SHOW);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($p) => $p
        ->where('teacherPrizeDraw.my_current_quarter_tickets', 1));
});

test('signed-in teachers ticket count reflects their non-cancelled entries this quarter', function () {
    // The signed-in user's email matches an exam_contacts row whose name is
    // used in exam_entries.teacher_name (the existing string-link pattern).
    $teacherEmail = 'helen@example.com';
    $contact = ExamContact::create([
        'name' => 'Helen Hodgkiss',
        'email' => $teacherEmail,
    ]);
    $contact->addType('teacher');

    $user = User::factory()->create(['email' => $teacherEmail]);

    $piano = Instrument::firstOrCreate(['name' => 'Piano']);
    $thisQuarter = Carbon::create(2026, 4, 15);  // Q2 2026 — May 2026 lives in Q2
    $lastQuarter = Carbon::create(2026, 2, 15);  // Q1 2026 — should NOT count

    // Helper that creates an order + entry attached to Helen.
    $entry = function (Carbon $date, ?string $notes = null) use ($piano) {
        $order = Order::create([
            'trinity_order_number' => '1-T-'.uniqid('', true),
            'delivery_method' => 'F2F',
            'subject_area' => 'Music',
            'candidates' => 1,
            'order_status' => 'Delivered',
            'requested_start_date' => $date,
        ]);
        return ExamEntry::create([
            'order_id' => $order->id,
            'candidate_name' => 'Test Student',
            'instrument_id' => $piano->id,
            'grade' => '1',
            'subject_area' => 'Piano',
            'delivery_method' => 'F2F',
            'exam_date' => $date,
            'result' => 'Pass',
            'score' => 70,
            'teacher_name' => 'Helen Hodgkiss',
            'notes' => $notes,
        ]);
    };

    // Three this quarter (one CANCELLED so excluded), one last quarter (excluded).
    $entry($thisQuarter);
    $entry($thisQuarter);
    $entry($thisQuarter, 'CANCELLED');
    $entry($lastQuarter);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($p) => $p
        ->where('teacherPrizeDraw.my_current_quarter_tickets', 2)
        ->where('teacherPrizeDraw.current_quarter_label', 'Q2 2026'));
});

test('quarter dropdown is sorted newest first', function () {
    pdTeacherDraw('Anna Older', quarter: 4, year: 2025);
    pdTeacherDraw('Beth Recent', quarter: 1, year: 2026);

    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($p) => $p
        // Index 0 should be the current undrawn quarter (Q2 2026)
        ->where('teacherPrizeDraw.quarters.0.label', 'Q2 2026')
        ->where('teacherPrizeDraw.quarters.0.has_winner', false)
        // Then Q1 2026 (most recent draw)
        ->where('teacherPrizeDraw.quarters.1.label', 'Q1 2026')
        ->where('teacherPrizeDraw.quarters.1.winner_display_name', 'Beth R')
        // Then Q4 2025
        ->where('teacherPrizeDraw.quarters.2.label', 'Q4 2025')
        ->where('teacherPrizeDraw.quarters.2.winner_display_name', 'Anna O'));
});