<?php

// tests/Feature/Admin/PrizeWorkflowTest.php
//
// /admin/quarter-end shows tick boxes for every prize winner: the four
// top-scorer awards, the student draw and the teacher draw. State is kept in
// `prize_workflow` so Paul can look back at any past quarter. Which boxes a
// prize shows comes from PrizeWorkflow::STEPS_BY_AWARD.

use App\Models\PrizeWorkflow;
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

    $row = PrizeWorkflow::where('winner_full_name', 'Anna Martin')->first();
    expect($row)->not->toBeNull();
    expect($row->bought)->toBeTrue();
    expect($row->sent)->toBeFalse();
    expect($row->cert)->toBeFalse();
    expect($row->updated_by)->toBe($this->admin->id);
});

it('updates an existing row in place rather than duplicating', function (): void {
    PrizeWorkflow::create([
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

    expect(PrizeWorkflow::count())->toBe(1);
    $row = PrizeWorkflow::first();
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

    expect(PrizeWorkflow::count())->toBe(2);
    expect(PrizeWorkflow::where('winner_full_name', 'Anna Martin')->first()->bought)->toBeTrue();
    expect(PrizeWorkflow::where('winner_full_name', 'Anna Martin')->first()->sent)->toBeFalse();
    expect(PrizeWorkflow::where('winner_full_name', 'Maya Parkinson')->first()->bought)->toBeFalse();
    expect(PrizeWorkflow::where('winner_full_name', 'Maya Parkinson')->first()->sent)->toBeTrue();
});

it('can untick a step (set back to false)', function (): void {
    PrizeWorkflow::create([
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
            'award_key' => 'not_a_prize',
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
    PrizeWorkflow::create([
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
                'sent' => false,
                'cert' => true,
                'claimed' => false,
                'bought' => true,
                'card_sent' => false,
                'used' => false,
            ])
        );
});

it('tracks the student draw winner through the claim-first steps', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 3, 'year' => 2026,
            'award_key' => 'student_draw',
            'winner_full_name' => 'Jim Hazell',
            'step' => 'claimed', 'value' => true,
        ])
        ->assertOk()
        ->assertJson(['status' => ['claimed' => true, 'bought' => false, 'used' => false]]);

    expect(PrizeWorkflow::where('award_key', 'student_draw')->first()->claimed)->toBeTrue();
});

it('refuses a step the prize does not show', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 3, 'year' => 2026,
            'award_key' => 'teacher_draw',
            'winner_full_name' => 'Jenny Capstick',
            'step' => 'claimed', 'value' => true,
        ])
        ->assertStatus(422);

    expect(PrizeWorkflow::count())->toBe(0);
});

it('sends the page the boxes each prize shows, in order', function (): void {
    $this->actingAs($this->admin)
        ->get('/admin/quarter-end?quarter=3&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('workflowSteps.teacher_draw', [
                ['key' => 'bought', 'label' => 'Bought'],
                ['key' => 'sent', 'label' => 'Email sent'],
                ['key' => 'used', 'label' => 'Used'],
            ])
            ->where('workflowSteps.student_draw.1', ['key' => 'claimed', 'label' => 'Claimed'])
            ->where('workflowSteps.initial_5_merit.1', ['key' => 'cert', 'label' => 'Cert'])
        );
});

it('round-trips a teacher draw tick back onto the page', function (): void {
    $this->actingAs($this->admin)
        ->postJson('/admin/quarter-end/toggle-workflow', [
            'quarter' => 3, 'year' => 2026,
            'award_key' => 'teacher_draw',
            'winner_full_name' => 'Jenny Capstick',
            'step' => 'used', 'value' => true,
        ])
        ->assertOk();

    $this->get('/admin/quarter-end?quarter=3&year=2026')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('winnerWorkflow.teacher_draw|Jenny Capstick.used', true)
            ->where('winnerWorkflow.teacher_draw|Jenny Capstick.bought', false)
        );
});

it('posts prize tick boxes from one component only', function (): void {
    expect(guardOffenders('#/admin/quarter-end/toggle-workflow#', [
        'resources/js/components/PrizeWorkflowChecks.vue',
    ]))->toBe([]);
});
