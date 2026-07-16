<?php

// tests/Feature/Admin/ResultsScanTranscribeTest.php
//
// The PDF → candidates transcription step on /admin/results-scan: an admin
// uploads a Trinity report PDF, the app calls the Anthropic vision API, and the
// returned records flow into the same checker grid the JSON upload uses. The
// external call is faked — we assert our request shape and response handling,
// never a real API hit.

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/** A minimal, well-formed model reply: the JSON array wrapped in a text block. */
function fakeClaudeReply(array $candidates): array
{
    return [
        'content' => [
            ['type' => 'text', 'text' => json_encode($candidates)],
        ],
    ];
}

function sampleScanRecords(): array
{
    return [[
        'subject' => 'Acoustic Guitar',
        'grade' => 'Grade 7',
        'candidate_name' => 'Dylan Freeman',
        'candidate_id' => '1273425',
        'order_number' => '1-16044465878',
        'exam_date' => '2026-07-10',
        'examiner_number' => '3300',
        'examiner_total' => 65,
        'general_comments' => '',
        'sections' => [
            ['label' => 'Graham: Anji', 'mark' => 19, 'max' => 22, 'comment' => ''],
            ['label' => 'Technical Work', 'mark' => 9, 'max' => 14, 'comment' => ''],
            ['label' => 'Sight Reading', 'mark' => 2, 'max' => 10, 'comment' => ''],
            ['label' => 'Aural', 'mark' => 35, 'max' => 10, 'comment' => ''],
        ],
    ]];
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    config(['services.anthropic.key' => 'test-key', 'services.anthropic.model' => 'claude-test']);
});

test('an admin can transcribe a report PDF into candidate records', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeClaudeReply(sampleScanRecords()), 200),
    ]);

    $pdf = UploadedFile::fake()->create('RayLangley.pdf', 200, 'application/pdf');

    $this->actingAs($this->admin)
        ->post('/admin/results-scan/transcribe', ['file' => $pdf])
        ->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonPath('candidates.0.candidate_name', 'Dylan Freeman')
        ->assertJsonPath('candidates.0.order_number', '1-16044465878');

    // We sent the PDF as a base64 document block to the Messages API.
    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/v1/messages')
            && $request['messages'][0]['content'][0]['type'] === 'document'
            && $request['messages'][0]['content'][0]['source']['media_type'] === 'application/pdf'
            && $request->hasHeader('x-api-key', 'test-key');
    });
});

test('the transcribed records feed the existing checker (preview) unchanged', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(fakeClaudeReply(sampleScanRecords()), 200),
    ]);

    $pdf = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');
    $rows = $this->actingAs($this->admin)
        ->post('/admin/results-scan/transcribe', ['file' => $pdf])
        ->json('candidates');

    // The Aural mark (35) exceeds its max of 10, so the checker must flag it —
    // proving the transcription output is the same shape preview() consumes.
    $preview = $this->actingAs($this->admin)
        ->postJson('/admin/results-scan/preview', ['candidates' => $rows])
        ->assertOk();

    expect($preview->json('candidates.0.flags'))->not->toBeEmpty();
});

test('a blank API key disables transcription with a friendly message', function () {
    config(['services.anthropic.key' => '']);
    Http::fake();

    $pdf = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    $this->actingAs($this->admin)
        ->post('/admin/results-scan/transcribe', ['file' => $pdf])
        ->assertStatus(422)
        ->assertJsonPath('error', 'Scan transcription is not configured — add an Anthropic API key first.');

    Http::assertNothingSent();
});

test('a garbled model reply returns a friendly error, not a crash', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'sorry, no idea']]], 200),
    ]);

    $pdf = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    $this->actingAs($this->admin)
        ->post('/admin/results-scan/transcribe', ['file' => $pdf])
        ->assertStatus(422)
        ->assertJsonStructure(['error']);
});

test('a non-PDF upload is rejected by validation', function () {
    $txt = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

    $this->actingAs($this->admin)
        ->post('/admin/results-scan/transcribe', ['file' => $txt])
        ->assertStatus(302); // redirected back with validation errors
});

test('guests cannot reach the transcribe endpoint', function () {
    $pdf = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    $this->post('/admin/results-scan/transcribe', ['file' => $pdf])
        ->assertRedirect('/login');
});

test('the index exposes whether transcription is enabled', function () {
    config(['services.anthropic.key' => 'test-key']);

    $this->actingAs($this->admin)
        ->get('/admin/results-scan')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('transcribeEnabled', true));
});
