<?php

// tests/Feature/TopTenTest.php

use App\Models\PieceVote;
use App\Models\SyllabusPiece;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────
// Top Ten — public chart + teacher voting
// ──────────────────────────────────────────

/** Give a piece a vote from a fresh teacher. */
function votePiece(SyllabusPiece $piece, ?int $rating, int $used): PieceVote
{
    return PieceVote::create([
        'user_id' => User::factory()->create(['role' => 'teacher'])->id,
        'syllabus_piece_id' => $piece->id,
        'rating' => $rating,
        'used_count' => $used,
    ]);
}

test('GET /top-ten returns 200 for guests', function () {
    $this->get('/top-ten')->assertStatus(200);
});

test('only pieces that have votes appear in the chart', function () {
    $voted = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 1']);
    SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 1']); // unvoted
    votePiece($voted, 3, 4);

    $this->get('/top-ten')
        ->assertInertia(fn ($page) => $page
            ->component('TopTen')
            ->where('groups.0.top_ten.0.id', $voted->id)
            ->count('groups', 1)
            ->count('groups.0.top_ten', 1));
});

test('pieces rank by times used, then by average stars', function () {
    $mostUsed = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 2', 'title' => 'Most Used']);
    $tieHighStars = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 2', 'title' => 'Tie High']);
    $tieLowStars = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 2', 'title' => 'Tie Low']);

    votePiece($mostUsed, 2, 20);          // times_used 20
    votePiece($tieHighStars, 4, 8);       // times_used 8, avg 4
    votePiece($tieLowStars, 1, 8);        // times_used 8, avg 1

    $this->get('/top-ten?instrument=Piano&grade=Grade 2')
        ->assertInertia(fn ($page) => $page
            ->component('TopTen')
            ->where('groups.0.top_ten.0.id', $mostUsed->id)
            ->where('groups.0.top_ten.0.position', 1)
            ->where('groups.0.top_ten.1.id', $tieHighStars->id)
            ->where('groups.0.top_ten.1.position', 2)
            ->where('groups.0.top_ten.2.id', $tieLowStars->id)
            ->where('groups.0.top_ten.2.position', 3));
});

test('equal usage and stars share a joint position', function () {
    $a = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 3']);
    $b = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 3']);
    $c = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 3']);

    votePiece($a, 3, 4);
    votePiece($b, 3, 4);   // identical to A → joint 1st
    votePiece($c, 3, 1);   // lower usage → 3rd

    $this->get('/top-ten?instrument=Piano&grade=Grade 3')
        ->assertInertia(fn ($page) => $page
            ->where('groups.0.top_ten.0.position', 1)
            ->where('groups.0.top_ten.1.position', 1)
            ->where('groups.0.top_ten.2.position', 3));
});

test('the eleventh piece drops out of the Top Ten into others', function () {
    $ids = [];
    foreach (range(11, 1) as $used) {
        $piece = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 4']);
        votePiece($piece, 3, $used);
        $ids[$used] = $piece->id;
    }

    $this->get('/top-ten?instrument=Piano&grade=Grade 4')
        ->assertInertia(fn ($page) => $page
            ->count('groups.0.top_ten', 10)
            ->count('groups.0.others', 1)
            ->where('groups.0.others.0.id', $ids[1])       // least used
            ->where('groups.0.others.0.position', 11));
});

test('average rating is computed across votes', function () {
    $piece = SyllabusPiece::factory()->create(['instrument' => 'Piano', 'grade' => 'Grade 5']);
    votePiece($piece, 2, 1);
    votePiece($piece, 4, 1);

    $this->get('/top-ten?instrument=Piano&grade=Grade 5')
        ->assertInertia(fn ($page) => $page
            ->where('groups.0.top_ten.0.avg_rating', fn ($v) => (float) $v === 3.0)
            ->where('groups.0.top_ten.0.times_used', 2)
            ->where('groups.0.top_ten.0.rating_count', 2));
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
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 4, 'used_count' => 6]);

    $vote = PieceVote::sole();
    expect($vote->user_id)->toBe($teacher->id)
        ->and($vote->rating)->toBe(4)
        ->and($vote->used_count)->toBe(6);
});

test('an admin can cast a vote', function () {
    $piece = SyllabusPiece::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 2, 'used_count' => 1]);

    expect(PieceVote::count())->toBe(1);
});

test('re-voting updates the same row rather than adding a new one', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 2, 'used_count' => 1]);
    $this->actingAs($teacher)->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 4, 'used_count' => 9]);

    expect(PieceVote::count())->toBe(1);
    $vote = PieceVote::sole();
    expect($vote->rating)->toBe(4)->and($vote->used_count)->toBe(9);
});

test('clearing a vote (no stars, zero used) removes it', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);
    PieceVote::create(['user_id' => $teacher->id, 'syllabus_piece_id' => $piece->id, 'rating' => 3, 'used_count' => 4]);

    $this->actingAs($teacher)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => null, 'used_count' => 0]);

    expect(PieceVote::count())->toBe(0);
});

test('rating must be between 1 and 4', function () {
    $piece = SyllabusPiece::factory()->create();
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->post('/top-ten/vote', ['syllabus_piece_id' => $piece->id, 'rating' => 5])
        ->assertSessionHasErrors('rating');
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
