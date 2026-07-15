<?php

// tests/Feature/SyllabusTest.php

use App\Models\SyllabusBook;
use App\Models\SyllabusPiece;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeBook(array $overrides = []): SyllabusBook
{
    return SyllabusBook::create(array_merge([
        'exam_board' => 'Trinity',
        'exam_stream' => 'Classical & Jazz',
        'instrument' => 'Piano',
        'title' => 'Piano Exam Pieces Plus Exercises from 2023: Grade 3',
        'edition' => 'Standard',
        'asin' => '1804903140',
        'buy_url' => 'https://www.amazon.co.uk/dp/1804903140?tag=musicexamshelp-21',
    ], $overrides));
}

function makePiece(SyllabusBook $book, array $overrides = []): SyllabusPiece
{
    return SyllabusPiece::create(array_merge([
        'exam_board' => 'Trinity',
        'exam_stream' => 'Classical & Jazz',
        'instrument' => 'Piano',
        'grade' => 'Grade 3',
        'position' => 1,
        'composer' => 'Joseph Haydn',
        'title' => 'Andante',
        'book_title' => $book->title,
        'publisher_code' => 'Trinity TCL031940',
        'syllabus_book_id' => $book->id,
        'technical_focus' => false,
        'buy_kind' => 'exact',
        'buy_url' => $book->buy_url,
        'buy_edition' => 'Standard',
        'audio' => ['youtube_search' => 'https://youtube.com/results?search_query=x'],
        'also_in' => ['Rock & Pop · Guitar · Grade 3'],
    ], $overrides));
}

test('the finder loads with facets and no pieces until a filter is set', function () {
    $book = makeBook();
    makePiece($book);
    makePiece($book, ['exam_stream' => 'Rock & Pop', 'instrument' => 'Guitar', 'title' => 'Yellow', 'composer' => 'Coldplay']);

    $this->get('/syllabus')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Syllabus')
            ->where('hasQuery', false)
            ->has('pieces', 0)
            ->has('streams')
            ->has('streamInstruments')
            ->has('instrumentGrades')
        );
});

test('filtering by instrument returns only matching pieces (server-side)', function () {
    $book = makeBook();
    makePiece($book, ['instrument' => 'Piano']);
    makePiece($book, ['exam_stream' => 'Rock & Pop', 'instrument' => 'Guitar', 'title' => 'Yellow', 'composer' => 'Coldplay']);

    $this->get('/syllabus?instrument=Piano')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('hasQuery', true)
            ->where('count', 1)
            ->has('pieces', 1)
            ->where('pieces.0.instrument', 'Piano')
        );
});

test('search query filters pieces server-side', function () {
    $book = makeBook();
    makePiece($book, ['composer' => 'Joseph Haydn']);
    makePiece($book, ['title' => 'Yellow', 'composer' => 'Coldplay']);

    $this->get('/syllabus?q=coldplay')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->where('count', 1)->has('pieces', 1)->where('pieces.0.composer', 'Coldplay'));
});

test('audio and also_in are cast to arrays on the model', function () {
    $piece = makePiece(makeBook());

    expect($piece->fresh()->audio)->toBeArray()
        ->and($piece->fresh()->also_in)->toBeArray()
        ->and($piece->fresh()->also_in[0])->toContain('Guitar');
});

test('a piece belongs to its book and shares the buy link', function () {
    $book = makeBook();
    $piece = makePiece($book);

    expect($piece->book->is($book))->toBeTrue()
        ->and($piece->buy_url)->toBe($book->buy_url)
        ->and($piece->buy_url)->toContain('musicexamshelp-21');
});

test('search scope matches title, composer and book (case-insensitive)', function () {
    $book = makeBook();
    makePiece($book, ['title' => 'Andante', 'composer' => 'Joseph Haydn']);
    makePiece($book, ['title' => 'Yellow', 'composer' => 'Coldplay']);

    expect(SyllabusPiece::search('haydn')->count())->toBe(1)
        ->and(SyllabusPiece::search('COLDPLAY')->count())->toBe(1)
        ->and(SyllabusPiece::search('zzz-nothing')->count())->toBe(0);
});

// --- C&J Drum Kit seed data (database/seeders/data/drumkit.json + drumkit_books.json) ---
// Guards the hand-parsed 2020 percussion syllabus: correct counts, correct stream,
// and every piece's buy link resolving to one of the seven Drum Kit books.

test('drum kit seed files are well-formed and self-consistent', function () {
    $pieces = json_decode(file_get_contents(database_path('seeders/data/drumkit.json')), true, 512, JSON_THROW_ON_ERROR);
    $books = json_decode(file_get_contents(database_path('seeders/data/drumkit_books.json')), true, 512, JSON_THROW_ON_ERROR);

    // 118 pieces, all Classical & Jazz Drum Kit.
    expect($pieces)->toHaveCount(118);
    expect(collect($pieces)->every(fn ($p) => $p['exam_stream'] === 'Classical & Jazz' && $p['instrument'] === 'Drum Kit'))->toBeTrue();

    // All nine grades are present.
    $grades = collect($pieces)->pluck('grade')->unique()->values();
    expect($grades)->toContain('Initial', 'Grade 1', 'Grade 5', 'Grade 8');

    // Seven books, each with an ASIN and the affiliate tag.
    expect($books)->toHaveCount(7);
    expect(collect($books)->every(fn ($b) => strlen((string) $b['asin']) === 10 && str_contains($b['url'], 'musicexamshelp-21')))->toBeTrue();

    // Every piece's buy ASIN maps to a known book (so the seeder can link it).
    $bookAsins = collect($books)->pluck('asin')->all();
    collect($pieces)->each(function ($p) use ($bookAsins) {
        expect($p['buy_kind'])->toBe('exact');
        preg_match('#/dp/([A-Z0-9]{10})#', $p['buy']['amazon'], $m);
        expect($m[1] ?? null)->toBeIn($bookAsins);
    });
});

test('seeding loads the C&J Drum Kit and links pieces to books', function () {
    $this->seed(\Database\Seeders\SyllabusSeeder::class);

    $drumKit = SyllabusPiece::where('exam_stream', 'Classical & Jazz')->where('instrument', 'Drum Kit');
    expect($drumKit->count())->toBe(118);

    // A sampled piece links to its book and carries an Amazon buy link.
    $piece = SyllabusPiece::where('instrument', 'Drum Kit')->where('title', 'Broadway Bounce')->first();
    expect($piece)->not->toBeNull()
        ->and($piece->grade)->toBe('Grade 1')
        ->and($piece->variant)->toBe('Group A')
        ->and($piece->syllabus_book_id)->not->toBeNull()
        ->and($piece->buy_url)->toContain('musicexamshelp-21');
})->group('seed');

test('the additive Drum Kit seeder adds 118 pieces without touching other pieces', function () {
    // An existing non-Drum-Kit piece stands in for real finder data (and, by
    // extension, the Top Ten votes that cascade-delete off syllabus_pieces).
    $keeper = makePiece(makeBook());

    $this->seed(\Database\Seeders\DrumKitSyllabusSeeder::class);

    expect(SyllabusPiece::find($keeper->id))->not->toBeNull()
        ->and(SyllabusPiece::where('instrument', 'Drum Kit')->count())->toBe(118);

    // Running it again is idempotent — no duplicate Drum Kit rows.
    $this->seed(\Database\Seeders\DrumKitSyllabusSeeder::class);
    expect(SyllabusPiece::where('instrument', 'Drum Kit')->count())->toBe(118)
        ->and(SyllabusPiece::find($keeper->id))->not->toBeNull();
})->group('seed');

// --- C&J Electronic Keyboard + Organ seed data (ek_organ.json + ek_organ_books.json) ---
// Guards the hand-parsed 2019 EK & Organ syllabus: correct counts, correct stream, the
// Trinity core-book links, and the "no Amazon link" rule for alternative/organ pieces.

test('EK + Organ seed files are well-formed and self-consistent', function () {
    $pieces = json_decode(file_get_contents(database_path('seeders/data/ek_organ.json')), true, 512, JSON_THROW_ON_ERROR);
    $books = json_decode(file_get_contents(database_path('seeders/data/ek_organ_books.json')), true, 512, JSON_THROW_ON_ERROR);

    // 338 pieces total: 128 Electronic Keyboard + 210 Organ, all Classical & Jazz.
    expect($pieces)->toHaveCount(338);
    $ek = collect($pieces)->where('instrument', 'Electronic Keyboard');
    $organ = collect($pieces)->where('instrument', 'Organ');
    expect($ek)->toHaveCount(128);
    expect($organ)->toHaveCount(210);
    expect(collect($pieces)->every(fn ($p) => $p['exam_stream'] === 'Classical & Jazz'))->toBeTrue();
    expect(collect($pieces)->every(fn ($p) => in_array($p['instrument'], ['Electronic Keyboard', 'Organ'], true)))->toBeTrue();

    // EK spans Initial-Grade 8; Organ spans Grade 1-8 (no Initial).
    expect($ek->pluck('grade')->unique()->values())->toContain('Initial', 'Grade 1', 'Grade 8');
    expect($organ->pluck('grade')->unique()->values())->toContain('Grade 1', 'Grade 8')
        ->not->toContain('Initial');

    // Nine EK Trinity core books, each with a 10-char ASIN and the affiliate tag.
    expect($books)->toHaveCount(9);
    expect(collect($books)->every(fn ($b) => $b['instrument'] === 'Electronic Keyboard'
        && strlen((string) $b['asin']) === 10
        && str_contains($b['url'], 'musicexamshelp-21')))->toBeTrue();

    // Every "Core repertoire" piece is EK, buys "exact", and its ASIN maps to a book.
    $bookAsins = collect($books)->pluck('asin')->all();
    $core = collect($pieces)->where('variant', 'Core repertoire');
    expect($core)->toHaveCount(84);
    $core->each(function ($p) use ($bookAsins) {
        expect($p['instrument'])->toBe('Electronic Keyboard');
        expect($p['buy_kind'])->toBe('exact');
        preg_match('#/dp/([A-Z0-9]{10})#', $p['buy']['amazon'], $m);
        expect($m[1] ?? null)->toBeIn($bookAsins);
    });

    // Everything else (EK alternative + all Organ) carries NO Amazon link.
    collect($pieces)->where('variant', '!=', 'Core repertoire')->each(function ($p) {
        expect($p['buy_kind'])->toBe('none');
        expect($p['buy'])->toBeNull();
    });
});

test('seeding loads EK + Organ and links only core pieces to books', function () {
    $this->seed(\Database\Seeders\SyllabusSeeder::class);

    expect(SyllabusPiece::where('instrument', 'Electronic Keyboard')->count())->toBe(128)
        ->and(SyllabusPiece::where('instrument', 'Organ')->count())->toBe(210);

    // A core EK piece links to its Trinity book and carries an Amazon buy link.
    $ek = SyllabusPiece::where('instrument', 'Electronic Keyboard')->where('title', 'Lullaby (Wiegenlied)')->first();
    expect($ek)->not->toBeNull()
        ->and($ek->grade)->toBe('Initial')
        ->and($ek->variant)->toBe('Core repertoire')
        ->and($ek->syllabus_book_id)->not->toBeNull()
        ->and($ek->buy_url)->toContain('musicexamshelp-21');

    // An Organ piece has its book title + publisher code but no linked book / buy link.
    $organ = SyllabusPiece::where('instrument', 'Organ')->where('title', 'Trumpet Piece')->first();
    expect($organ)->not->toBeNull()
        ->and($organ->variant)->toBe('Group A')
        ->and($organ->publisher_code)->toContain('OUP')
        ->and($organ->syllabus_book_id)->toBeNull()
        ->and($organ->buy_url)->toBeNull();
})->group('seed');

test('the additive EK + Organ seeder adds 338 pieces without touching other pieces', function () {
    // An existing non-EK/Organ piece stands in for real finder data (and the Top Ten
    // votes that cascade-delete off syllabus_pieces).
    $keeper = makePiece(makeBook());

    $this->seed(\Database\Seeders\EkOrganSyllabusSeeder::class);

    expect(SyllabusPiece::find($keeper->id))->not->toBeNull()
        ->and(SyllabusPiece::whereIn('instrument', ['Electronic Keyboard', 'Organ'])->count())->toBe(338);

    // Running it again is idempotent — no duplicate EK/Organ rows.
    $this->seed(\Database\Seeders\EkOrganSyllabusSeeder::class);
    expect(SyllabusPiece::whereIn('instrument', ['Electronic Keyboard', 'Organ'])->count())->toBe(338)
        ->and(SyllabusPiece::find($keeper->id))->not->toBeNull();
})->group('seed');

// Mandatory scope-composition test (dev-rules.md): the search scope must
// survive a join with a table that shares a column name (`title` exists on
// both syllabus_pieces and syllabus_books). Asserting it runs is enough —
// this catches unqualified-column SQL errors an isolation test can't.
test('search scope survives a join with syllabus_books', function () {
    $book = makeBook();
    makePiece($book);

    $rows = SyllabusPiece::query()
        ->leftJoin('syllabus_books', 'syllabus_books.id', '=', 'syllabus_pieces.syllabus_book_id')
        ->search('andante')
        ->get();

    expect($rows)->not->toBeNull();
});
