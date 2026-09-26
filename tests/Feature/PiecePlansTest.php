<?php

// tests/Feature/PiecePlansTest.php

use App\Models\ExamContact;
use App\Models\ExamEntry;
use App\Models\Order;
use App\Models\PiecePlan;
use App\Models\PiecePlanItem;
use App\Models\SyllabusPiece;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Piece tracker (/dashboard/pieces) — a teacher's private plans for their
// pupils' next exams. The round trips below prove what the page is served
// is what the save writes back.
// ──────────────────────────────────────────

function ppTeacher(array $attributes = []): User
{
    return User::factory()->create(['role' => 'teacher', ...$attributes]);
}

function ppPiece(array $attributes = []): SyllabusPiece
{
    return SyllabusPiece::factory()->create([
        'exam_stream' => 'Classical & Jazz',
        'instrument' => 'Piano',
        'grade' => 'Grade 3',
        ...$attributes,
    ]);
}

function ppPlanData(array $overrides = []): array
{
    return [
        'pupil_name' => 'Freddie Smith',
        'exam_stream' => 'Classical & Jazz',
        'instrument' => 'Piano',
        'grade' => 'Grade 3',
        'target_date' => '2026-12-05',
        'items' => [],
        ...$overrides,
    ];
}

/** The plans the page is given, as the page receives them. */
function ppServed(Tests\TestCase $test, User $user): array
{
    $plans = null;
    $test->actingAs($user)->get('/dashboard/pieces')
        ->assertOk()
        ->assertInertia(function ($page) use (&$plans) {
            $page->component('dashboard/PiecePlans');
            $plans = $page->toArray()['props']['plans'];
        });

    return $plans;
}

test('guests are sent to log in', function () {
    $this->get('/dashboard/pieces')->assertRedirect('/login');
});

test('parents and self-candidates do not get the tracker', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get('/dashboard/pieces')->assertForbidden();
    $this->actingAs($user)->post('/dashboard/pieces', ppPlanData())->assertForbidden();
})->with(['parent', 'self']);

test('the sidebar and dashboard are told who gets the tracker', function () {
    $this->actingAs(ppTeacher())->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('auth.pieceTracker', true));

    $this->actingAs(User::factory()->create(['role' => 'parent']))->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('auth.pieceTracker', false));
});

test('a plan round-trips: every served field comes back as saved', function () {
    ppPiece(['title' => 'Other Piece', 'composer' => 'Someone']);
    $piece = ppPiece(['title' => 'Jupiter Storm', 'composer' => 'Kate Lyons', 'book_title' => 'Piano Pieces 2023: Grade 3']);
    $teacher = ppTeacher();

    $this->actingAs($teacher)->post('/dashboard/pieces', ppPlanData([
        'items' => [
            ['section' => 'piece', 'syllabus_piece_id' => $piece->id, 'label' => null, 'percent' => 40],
            ['section' => 'piece', 'syllabus_piece_id' => null, 'label' => 'Own choice piece', 'percent' => 10],
            ['section' => 'technical', 'syllabus_piece_id' => null, 'label' => 'Scales & arpeggios', 'percent' => 70],
            ['section' => 'supporting', 'syllabus_piece_id' => null, 'label' => 'Aural', 'percent' => 0],
        ],
    ]))->assertRedirect()->assertSessionHasNoErrors();

    $served = ppServed($this, $teacher);

    expect($served)->toHaveCount(1);
    $plan = $served[0];
    expect($plan)->toMatchArray([
        'pupil_name' => 'Freddie Smith',
        'exam_stream' => 'Classical & Jazz',
        'instrument' => 'Piano',
        'grade' => 'Grade 3',
        'target_date' => '2026-12-05',
        'ready' => 30,
    ]);
    expect(collect($plan['items'])->map(fn ($i) => [$i['section'], $i['syllabus_piece_id'], $i['label'], $i['percent']])->all())->toBe([
        ['piece', $piece->id, 'Jupiter Storm — Kate Lyons', 40],
        ['piece', null, 'Own choice piece', 10],
        ['technical', null, 'Scales & arpeggios', 70],
        ['supporting', null, 'Aural', 0],
    ]);
    expect($plan['items'][0]['book'])->toBe('Piano Pieces 2023: Grade 3');

    // Send back exactly what was served, with changes: first row readier,
    // second row dropped, a new row added, and the order changed.
    $items = $plan['items'];
    $items[0]['percent'] = 100;
    $sent = [$items[3], $items[0], $items[2], ['id' => null, 'section' => 'supporting', 'syllabus_piece_id' => null, 'label' => 'Sight reading', 'percent' => 20]];

    $this->actingAs($teacher)->put("/dashboard/pieces/{$plan['id']}", [
        ...collect($plan)->only(['pupil_name', 'exam_stream', 'instrument', 'grade', 'target_date'])->all(),
        'grade' => 'Grade 3',
        'items' => $sent,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $again = ppServed($this, $teacher)[0];

    expect(collect($again['items'])->map(fn ($i) => [$i['label'], $i['percent']])->all())->toBe([
        ['Aural', 0],
        ['Jupiter Storm — Kate Lyons', 100],
        ['Scales & arpeggios', 70],
        ['Sight reading', 20],
    ]);
    // Existing rows were updated in place, not recreated.
    expect($again['items'][0]['id'])->toBe($plan['items'][3]['id']);
    expect($again['items'][1]['id'])->toBe($plan['items'][0]['id']);
    expect(PiecePlanItem::query()->where('label', 'Own choice piece')->exists())->toBeFalse();
});

test('a syllabus piece is always labelled from the syllabus, whatever label is sent', function () {
    $piece = ppPiece(['title' => 'Real Title', 'composer' => 'Real Composer']);
    $teacher = ppTeacher();

    $this->actingAs($teacher)->post('/dashboard/pieces', ppPlanData([
        'items' => [['section' => 'piece', 'syllabus_piece_id' => $piece->id, 'label' => 'Something else', 'percent' => 0]],
    ]))->assertSessionHasNoErrors();

    expect(PiecePlanItem::first()->label)->toBe('Real Title — Real Composer');
});

test('a plan survives the syllabus being re-seeded', function () {
    $piece = ppPiece(['title' => 'Gone Soon', 'composer' => 'Composer']);
    $teacher = ppTeacher();

    $this->actingAs($teacher)->post('/dashboard/pieces', ppPlanData([
        'items' => [['section' => 'piece', 'syllabus_piece_id' => $piece->id, 'percent' => 50]],
    ]))->assertSessionHasNoErrors();

    $piece->delete();

    $item = ppServed($this, $teacher)[0]['items'][0];
    expect($item['label'])->toBe('Gone Soon — Composer');
    expect($item['syllabus_piece_id'])->toBeNull();
    expect($item['percent'])->toBe(50);
});

test('the instrument must be one the syllabus has for that exam type', function () {
    ppPiece();

    $this->actingAs(ppTeacher())->post('/dashboard/pieces', ppPlanData(['instrument' => 'Kazoo']))
        ->assertSessionHasErrors('instrument');

    $this->actingAs(ppTeacher())->post('/dashboard/pieces', ppPlanData(['exam_stream' => 'Rock & Pop']))
        ->assertSessionHasErrors('instrument');

    expect(PiecePlan::count())->toBe(0);
});

test('a free-text row needs a label', function () {
    ppPiece();

    $this->actingAs(ppTeacher())->post('/dashboard/pieces', ppPlanData([
        'items' => [['section' => 'technical', 'syllabus_piece_id' => null, 'label' => '', 'percent' => 0]],
    ]))->assertSessionHasErrors('items.0.label');
});

test('a teacher never sees, changes or removes another teacher\'s plan', function () {
    ppPiece();
    $owner = ppTeacher();
    $other = ppTeacher();

    $this->actingAs($owner)->post('/dashboard/pieces', ppPlanData([
        'items' => [['section' => 'technical', 'label' => 'Scales', 'percent' => 10]],
    ]));
    $plan = PiecePlan::first();

    expect(ppServed($this, $other))->toBe([]);

    $this->actingAs($other)->put("/dashboard/pieces/{$plan->id}", ppPlanData(['pupil_name' => 'Hijacked']))->assertNotFound();
    $this->actingAs($other)->delete("/dashboard/pieces/{$plan->id}")->assertNotFound();

    expect($plan->fresh()->pupil_name)->toBe('Freddie Smith');
    expect($plan->items()->count())->toBe(1);
});

test('an item id from someone else\'s plan is never taken over', function () {
    ppPiece();
    $owner = ppTeacher();
    $other = ppTeacher();

    $this->actingAs($owner)->post('/dashboard/pieces', ppPlanData([
        'items' => [['section' => 'technical', 'label' => 'Owner scales', 'percent' => 10]],
    ]));
    $ownersItem = PiecePlanItem::first();

    $this->actingAs($other)->post('/dashboard/pieces', ppPlanData(['pupil_name' => 'Other pupil']));
    $othersPlan = PiecePlan::where('user_id', $other->id)->first();

    $this->actingAs($other)->put("/dashboard/pieces/{$othersPlan->id}", ppPlanData([
        'pupil_name' => 'Other pupil',
        'items' => [['id' => $ownersItem->id, 'section' => 'technical', 'label' => 'Stolen', 'percent' => 90]],
    ]))->assertSessionHasNoErrors();

    expect($ownersItem->fresh()->label)->toBe('Owner scales');
    expect($ownersItem->fresh()->piece_plan_id)->not->toBe($othersPlan->id);
    expect($othersPlan->items()->pluck('label')->all())->toBe(['Stolen']);
});

test('removing a plan removes its rows', function () {
    ppPiece();
    $teacher = ppTeacher();
    $this->actingAs($teacher)->post('/dashboard/pieces', ppPlanData([
        'items' => [['section' => 'technical', 'label' => 'Scales', 'percent' => 10]],
    ]));
    $plan = PiecePlan::first();

    $this->actingAs($teacher)->delete("/dashboard/pieces/{$plan->id}")->assertRedirect();

    expect(PiecePlan::count())->toBe(0);
    expect(PiecePlanItem::count())->toBe(0);
});

test('the piece picker lists only that exam\'s syllabus pieces', function () {
    $wanted = ppPiece(['title' => 'Wanted', 'book_title' => 'The Book']);
    ppPiece(['grade' => 'Grade 4', 'title' => 'Wrong grade']);
    ppPiece(['instrument' => 'Flute', 'title' => 'Wrong instrument']);

    $this->actingAs(ppTeacher())
        ->getJson('/dashboard/pieces/syllabus?'.http_build_query(['stream' => 'Classical & Jazz', 'instrument' => 'Piano', 'grade' => 'Grade 3']))
        ->assertOk()
        ->assertExactJson([['value' => $wanted->id, 'label' => "Wanted — {$wanted->composer}", 'book' => 'The Book']]);
});

test('a teacher can start a plan from their own candidates, and a planned pupil drops off the list', function () {
    ppPiece();
    $teacher = ppTeacher(['email' => 'tina@example.com']);
    $contact = ExamContact::create(['name' => 'Tina Teacher', 'email' => 'tina@example.com', 'source' => 'trinity_csv']);
    $contact->addType('teacher');
    $order = Order::create([
        'trinity_order_number' => 'ORD-7654321',
        'order_status' => 'Submitted',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'requested_start_date' => '2026-03-01',
    ]);
    foreach (['Freddie Smith', 'Amy Jones', 'Freddie Smith'] as $i => $name) {
        ExamEntry::create([
            'order_id' => $order->id,
            'candidate_number' => "1-1000{$i}",
            'candidate_name' => $name,
            'grade' => '2',
            'subject_area' => 'Music',
            'delivery_method' => 'Digital',
            'exam_date' => '2026-03-10',
            'result' => 'Merit',
            'teacher_contact_id' => $contact->id,
        ]);
    }
    // Someone else's candidate never appears.
    ExamEntry::create([
        'order_id' => $order->id,
        'candidate_number' => '1-99999',
        'candidate_name' => 'Not Mine',
        'grade' => '1',
        'subject_area' => 'Music',
        'delivery_method' => 'Digital',
        'exam_date' => '2026-03-10',
    ]);

    $names = function () use ($teacher) {
        $list = null;
        $this->actingAs($teacher)->get('/dashboard/pieces')
            ->assertInertia(function ($page) use (&$list) {
                $list = collect($page->toArray()['props']['candidates'])->pluck('name')->all();
            });

        return $list;
    };

    expect($names())->toBe(['Amy Jones', 'Freddie Smith']);

    $this->actingAs($teacher)->post('/dashboard/pieces', ppPlanData(['pupil_name' => 'freddie smith']));

    expect($names())->toBe(['Amy Jones']);
});
