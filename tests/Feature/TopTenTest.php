<?php

// tests/Feature/TopTenTest.php

use App\Models\PieceVote;
use App\Models\SyllabusPiece;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Top Ten — public chart + teacher voting
// ──────────────────────────────────────────

/** Give a piece one vote from a fresh teacher (band = usage 1-3, nullable). */
function votePiece(SyllabusPiece $piece, ?int $rating, ?int $band): PieceVote
{
    return PieceVote::create([
        'user_id' => User::factory()->create(['role' => 'teacher'])->id,
        'syllabus_piece_id' => $piece->id,
        'rating' => $rating,
        'used_band' => $band,
    ]);
}

test('GET /top-ten returns 200 for guests', function () {
    $this->get('/top-ten')->assertStatus(200);
});

test('only pieces that have votes appear in the chart', function () {
    $voted = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 1']);
    SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 1']); // unvoted
    votePiece($voted, 3, 2);

    $this->get('/top-ten')
        ->assertInertia(fn ($page) => $page
            ->component('TopTen')
            ->where('groups.0.top_ten.0.id', $voted->id)
            ->count('groups', 1)
            ->count('groups.0.top_ten', 1));
});

test('pieces rank by number of teachers, then usage band, then stars', function () {
    $mostTeachers = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 2', 'title' => 'Most Teachers']);
    $oneHighBand = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 2', 'title' => 'One High Band']);
    $oneLowBand = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 2', 'title' => 'One Low Band']);

    votePiece($mostTeachers, 3, 2);      // 2 distinct teachers → teachers_using 2
    votePiece($mostTeachers, 4, 2);
    votePiece($oneHighBand, 3, 3);       // 1 teacher, "loads"
    votePiece($oneLowBand, 3, 1);        // 1 teacher, "a few times"

    $this->get('/top-ten?instrument=Piano&grade=Grade 2')
        ->assertInertia(fn ($page) => $page
            ->component('TopTen')
            ->where('groups.0.top_ten.0.id', $mostTeachers->id)
            ->where('groups.0.top_ten.0.position', 1)
            ->where('groups.0.top_ten.0.teachers_using', 2)
            ->where('groups.0.top_ten.1.id', $oneHighBand->id)
            ->where('groups.0.top_ten.1.position', 2)
            ->where('groups.0.top_ten.2.id', $oneLowBand->id)
            ->where('groups.0.top_ten.2.position', 3));
});

test('equal teachers, usage and stars share a joint position', function () {
    $a = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 3']);
    $b = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 3']);
    $c = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 3']);

    votePiece($a, 3, 2);
    votePiece($b, 3, 2);   // identical to A → joint 1st
    votePiece($c, 3, 1);   // lower usage band → 3rd

    $this->get('/top-ten?instrument=Piano&grade=Grade 3')
        ->assertInertia(fn ($page) => $page
            ->where('groups.0.top_ten.0.position', 1)
            ->where('groups.0.top_ten.1.position', 1)
            ->where('groups.0.top_ten.2.position', 3));
});

test('the eleventh piece drops out of the Top Ten into others', function () {
    $ids = [];
    // Give each piece a distinct teacher count (11 down to 1) so positions are unique.
    foreach (range(11, 1) as $teachers) {
        $piece = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 4']);
        for ($i = 0; $i < $teachers; $i++) {
            votePiece($piece, 3, 1);
        }
        $ids[$teachers] = $piece->id;
    }

    $this->get('/top-ten?instrument=Piano&grade=Grade 4')
        ->assertInertia(fn ($page) => $page
            ->count('groups.0.top_ten', 10)
            ->count('groups.0.others', 1)
            ->where('groups.0.others.0.id', $ids[1])       // fewest teachers
            ->where('groups.0.others.0.position', 11));
});

test('average rating is computed across votes', function () {
    $piece = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 5']);
    votePiece($piece, 2, 1);
    votePiece($piece, 4, 1);

    $this->get('/top-ten?instrument=Piano&grade=Grade 5')
        ->assertInertia(fn ($page) => $page
            ->where('groups.0.top_ten.0.avg_rating', fn ($v) => (float) $v === 3.0)
            ->where('groups.0.top_ten.0.teachers_using', 2)
            ->where('groups.0.top_ten.0.rating_count', 2));
});

test('a stars-only vote does not count as a teacher using the piece', function () {
    $piece = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 6']);
    votePiece($piece, 4, null); // rated but usage left blank

    $this->get('/top-ten?instrument=Piano&grade=Grade 6')
        ->assertInertia(fn ($page) => $page
            ->where('groups.0.top_ten.0.teachers_using', 0)
            ->where('groups.0.top_ten.0.rating_count', 1));
});

test('instrument filter narrows the chart', function () {
    $piano = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 1']);
    $guitar = SyllabusPiece::factory()->rockAndPop()->create(['grade' => 'Grade 1']);
    votePiece($piano, 3, 2);
    votePiece($guitar, 3, 2);

    $this->get('/top-ten?instrument=Piano')
        ->assertInertia(fn ($page) => $page
            ->count('groups', 1)
            ->where('groups.0.instrument', 'Piano'));
});

// ── Voting permissions ──

test('guests are redirected to login when voting', function () {
    $piece = SyllabusPiece::factory()->create();

    $this->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 3])
        ->assertRedirect('/login');
});

test('parents cannot vote', function () {
    $piece = SyllabusPiece::factory()->create();
    $parent = User::factory()->create(['role' => 'parent']);

    $this->actingAs($parent)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 3])
        ->assertStatus(403);

    expect(PieceVote::count())->toBe(0);
});

test('a teacher can cast a vote', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 4, 'used_band' => 3]);

    $vote = PieceVote::sole();
    expect($vote->user_id)->toBe($teacher->id)
        ->and($vote->rating)->toBe(4)
        ->and($vote->used_band)->toBe(3);
});

test('an admin can cast a vote', function () {
    $piece = SyllabusPiece::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 2, 'used_band' => 1]);

    expect(PieceVote::count())->toBe(1);
});

test('re-voting updates the same row rather than adding a new one', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 2, 'used_band' => 1]);
    $this->actingAs($teacher)->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 4, 'used_band' => 3]);

    expect(PieceVote::count())->toBe(1);
    $vote = PieceVote::sole();
    expect($vote->rating)->toBe(4)->and($vote->used_band)->toBe(3);
});

test('clearing a vote (no stars, no usage) removes it', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);
    PieceVote::create(['user_id' => $teacher->id, 'syllabus_piece_id' => $piece->id, 'rating' => 3, 'used_band' => 2]);

    $this->actingAs($teacher)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => null, 'used_band' => null]);

    expect(PieceVote::count())->toBe(0);
});

test('rating must be between 1 and 4', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 5])
        ->assertSessionHasErrors('rating');
});

test('usage band must be between 1 and 3', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'used_band' => 4])
        ->assertSessionHasErrors('used_band');
});

// ── Login redirect back to the chart ──

test('a safe redirect param sets the intended url on login', function () {
    $this->get('/login?redirect=' . urlencode('/top-ten?instrument=Piano'))
        ->assertSessionHas('url.intended', '/top-ten?instrument=Piano');
});

test('an off-site redirect param is ignored', function () {
    $this->get('/login?redirect=' . urlencode('https://evil.example.com'))
        ->assertSessionMissing('url.intended');
});
