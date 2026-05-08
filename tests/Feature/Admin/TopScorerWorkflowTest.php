<?php

// tests/Feature/Admin/TopScorerWorkflowTest.php
//
// /admin/quarter-end exposes Bought / Sent / Cert checkboxes per top-scorer
// winner. State is persisted in `top_scorer_workflow` so Paul can look back
// at any past quarter and see which winners were dealt with. These tests
// cover the toggle endpoint that drives those checkboxes.

use App\Models\TopScorerWorkflow;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

it('requires authentication to toggle a workflow step', function (): void {
    // JSON requests get 401 from auth middleware, not a redirect to /login
    // (redirects only fire for browser-style requests).
    $this->postJson('/admin/quarter-end/toggle-workflow', [
        'quarter' => 1,
        'year' => 2026,
        'award_key' => 'initial_5_distinction',
        'winner_full_name' => 'Anna Martin',
        'step' => 'bought',
        'value' => true,
    ])->assertUnauthorized();
});

it('creates a new workflow row when none exists for that winner', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 1,
            'year' => 2026,
            'award_key' => 'initial_5_distinction',
            'winner_full_name' => 'Anna Martin',
            'step' => 'bought',
            'value' => true,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => ['bought' => true, 'sent' => false, 'cert' => false],
        ]);

    $row = TopScorerWorkflow::where('winner_full_name', 'Anna Martin')->first();
    expect($row)->not->toBeNull();
    expect($row->bought)->toBeTrue();
    expect($row->sent)->toBeFalse();
    expect($row->cert)->toBeFalse();
    expect($row->updated_by)->toBe($this->admin->id);
});

it('updates an existing row in place rather than duplicating', function (): void {
    TopScorerWorkflow::create([
        'quarter' => 1,
        'year' => 2026,
        'award_key' => 'initial_5_distinction',
        'winner_full_name' => 'Anna Martin',
        'bought' => true,
        'sent' => false,
        'cert' => false,
        'updated_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 1,
            'year' => 2026,
            'award_key' => 'initial_5_distinction',
            'winner_full_name' => 'Anna Martin',
            'step' => 'sent',
            'value' => true,
        ])
        ->assertOk();

    expect(TopScorerWorkflow::count())->toBe(1);
    $row = TopScorerWorkflow::first();
    expect($row->bought)->toBeTrue();
    expect($row->sent)->toBeTrue();
    expect($row->cert)->toBeFalse();
});

it('keeps tied winners separate (anna and maya tracked independently)', function (): void {
    $this->actingAs($this->admin);

    // Anna ticks Bought
    $this->postJson('/admin/quarter-end/toggle-workflow', [
        'quarter' => 1, 'year' => 2026,
        'award_key' => 'initial_5_distinction',
        'winner_full_name' => 'Anna Martin',
        'step' => 'bought', 'value' => true,
    ])->assertOk();

    // Maya in same award category — should be a separate row
    $this->postJson('/admin/quarter-end/toggle-workflow', [
        'quarter' => 1, 'year' => 2026,
        'award_key' => 'initial_5_distinction',
        'winner_full_name' => 'Maya Parkinson',
        'step' => 'sent', 'value' => true,
    ])->assertOk();

    expect(TopScorerWorkflow::count())->toBe(2);
    expect(TopScorerWorkflow::where('winner_full_name', 'Anna Martin')->first()->bought)->toBeTrue();
    expect(TopScorerWorkflow::where('winner_full_name', 'Anna Martin')->first()->sent)->toBeFalse();
    expect(TopScorerWorkflow::where('winner_full_name', 'Maya Parkinson')->first()->bought)->toBeFalse();
    expect(TopScorerWorkflow::where('winner_full_name', 'Maya Parkinson')->first()->sent)->toBeTrue();
});

it('can untick a step (set back to false)', function (): void {
    TopScorerWorkflow::create([
        'quarter' => 1, 'year' => 2026,
        'award_key' => 'initial_5_distinction',
        'winner_full_name' => 'Anna Martin',
        'bought' => true, 'sent' => true, 'cert' => true,
        'updated_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 1, 'year' => 2026,
            'award_key' => 'initial_5_distinction',
            'winner_full_name' => 'Anna Martin',
            'step' => 'sent', 'value' => false,
        ])
        ->assertOk()
        ->assertJson(['status' => ['bought' => true, 'sent' => false, 'cert' => true]]);
});

it('rejects an unknown award_key', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 1, 'year' => 2026,
            'award_key' => 'student_draw', // not a valid top-scorer award_key
            'winner_full_name' => 'Anna Martin',
            'step' => 'bought', 'value' => true,
        ])
        ->assertStatus(422);
});

it('rejects an unknown step', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 1, 'year' => 2026,
            'award_key' => 'initial_5_distinction',
            'winner_full_name' => 'Anna Martin',
            'step' => 'forwarded', 'value' => true,
        ])
        ->assertStatus(422);
});

it('exposes existing workflow status on the index page so checkboxes pre-fill on reload', function (): void {
    TopScorerWorkflow::create([
        'quarter' => 1, 'year' => 2026,
        'award_key' => 'initial_5_distinction',
        'winner_full_name' => 'Anna Martin',
        'bought' => true, 'sent' => false, 'cert' => true,
        'updated_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/quarter-end?quarter=1&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('winnerWorkflow.initial_5_distinction|Anna Martin', [
                'bought' => true,
                'sent' => false,
                'cert' => true,
            ])
        );
});
