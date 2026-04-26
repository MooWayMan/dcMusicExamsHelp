<?php

use App\Models\ExamContact;
use App\Models\Order;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function orderAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function orderTeacher(): User
{
    return User::factory()->create(['role' => 'teacher']);
}

function orderTeacherContact(string $name = null): ExamContact
{
    $contact = ExamContact::create([
        'name'  => $name ?? 'Test Teacher '.uniqid(),
        'email' => uniqid('t', true).'@example.test',
    ]);
    $contact->addType('teacher');

    return $contact;
}

// ──────────────────────────────────────────
// Auth & Access Control
// ──────────────────────────────────────────

test('guests cannot access orders index', function () {
    $this->get(route('admin.orders.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access orders index', function () {
    $this->actingAs(orderTeacher())
        ->get(route('admin.orders.index'))
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Index
// ──────────────────────────────────────────

test('admin can view orders index', function () {
    $admin = orderAdmin();
    Order::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders/Index')
            ->has('orders.data', 3)
        );
});

test('orders can be filtered by delivery method', function () {
    $admin = orderAdmin();
    Order::factory()->digital()->count(2)->create();
    Order::factory()->faceToFace()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['method' => 'digital']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 2)
        );
});

test('orders index payload includes formatted requested_start_date', function () {
    $admin = orderAdmin();

    Order::factory()->create([
        'requested_start_date' => '2026-03-07',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('orders.data.0.requested_start_date', '07 Mar 2026')
        );
});

test('orders can be sorted by requested_start_date ascending and descending', function () {
    $admin = orderAdmin();

    Order::factory()->create([
        'trinity_order_number' => 'TRN-OLDEST',
        'requested_start_date' => '2026-01-13',
    ]);
    Order::factory()->create([
        'trinity_order_number' => 'TRN-NEWEST',
        'requested_start_date' => '2026-03-07',
    ]);

    // Ascending: oldest first
    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['sort' => 'requested_start_date', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('orders.data.0.trinity_order_number', 'TRN-OLDEST')
            ->where('orders.data.1.trinity_order_number', 'TRN-NEWEST')
        );

    // Descending: newest first
    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['sort' => 'requested_start_date', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('orders.data.0.trinity_order_number', 'TRN-NEWEST')
            ->where('orders.data.1.trinity_order_number', 'TRN-OLDEST')
        );
});

// ──────────────────────────────────────────
// Time Period Filters
// ──────────────────────────────────────────

test('orders can be filtered by this quarter', function () {
    $admin = orderAdmin();

    // This quarter — filter uses requested_start_date (the exam date), not created_at
    Order::factory()->create(['requested_start_date' => now()]);
    // Last year (outside this quarter)
    Order::factory()->create(['requested_start_date' => now()->subYear()]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'this_quarter']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
        );
});

test('orders can be filtered by this year', function () {
    $admin = orderAdmin();

    // This year
    Order::factory()->count(2)->create([
        'requested_start_date' => now()->startOfYear()->addDays(10),
    ]);
    // Previous year
    Order::factory()->create([
        'requested_start_date' => now()->subYear()->startOfYear(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'this_year']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 2)
        );
});

test('orders can be filtered by last 12 months', function () {
    $admin = orderAdmin();

    // 6 months ago (within last 12)
    Order::factory()->create(['requested_start_date' => now()->subMonths(6)]);
    // 18 months ago (outside last 12)
    Order::factory()->create(['requested_start_date' => now()->subMonths(18)]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'last_12']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
        );
});

test('summary stats respect time period filter', function () {
    $admin = orderAdmin();

    Order::factory()->create([
        'commission_amount' => 100.00,
        'candidates' => 5,
        'requested_start_date' => now(),
    ]);

    Order::factory()->create([
        'commission_amount' => 200.00,
        'candidates' => 10,
        'requested_start_date' => now()->subYear(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'this_year']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summary.total_orders', 1)
            ->where('summary.total_candidates', 5)
        );
});

test('period filter passes through to frontend filters', function () {
    $admin = orderAdmin();

    $this->actingAs($admin)
        ->get(route('admin.orders.index', ['period' => 'last_quarter']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.period', 'last_quarter')
        );
});

// ──────────────────────────────────────────
// Show
// ──────────────────────────────────────────

test('admin can view an order', function () {
    $admin = orderAdmin();
    $order = Order::factory()->create([
        'trinity_order_number' => 'TRN-123456',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders/Show')
        );
});

// ──────────────────────────────────────────
// Create / Store — manual Trinity order entry
// ──────────────────────────────────────────

test('admin can view create order form', function () {
    $this->actingAs(orderAdmin())
        ->get(route('admin.orders.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders/Create')
            ->has('teachers')
            ->has('options.delivery_methods')
        );
});

test('non-admin cannot view create order form', function () {
    $this->actingAs(orderTeacher())
        ->get(route('admin.orders.create'))
        ->assertForbidden();
});

test('admin can create order with one candidate', function () {
    $admin = orderAdmin();
    $contact = orderTeacherContact();

    $this->actingAs($admin)
        ->post(route('admin.orders.store'), [
            'trinity_order_number' => '1-15899713974',
            'delivery_method' => 'Digital',
            'subject_area' => 'Music',
            'order_status' => 'Delivered',
            'requested_start_date' => '2026-03-30',
            'created_by_contact_id' => $contact->id,
            'commission_rate' => 20,
            'applicant_name' => 'Maria Nielsen',
            'applicant_email' => 'mkn21@me.com',
            'entries' => [
                [
                    'candidate_name' => 'Delfina Yelich Battistessa',
                    'candidate_number' => '1-15899370904',
                    'grade' => '1',
                ],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'trinity_order_number' => '1-15899713974',
        'created_by_contact_id' => $contact->id,
        'candidates' => 1,
    ]);

    $this->assertDatabaseHas('exam_entries', [
        'candidate_name' => 'Delfina Yelich Battistessa',
        'candidate_number' => '1-15899370904',
        'grade' => '1',
        'source' => 'manual',
    ]);
});

test('admin can create order with multiple candidates', function () {
    $admin = orderAdmin();
    $contact = orderTeacherContact();

    $this->actingAs($admin)
        ->post(route('admin.orders.store'), [
            'trinity_order_number' => 'TRN-MULTI-001',
            'delivery_method' => 'Default',
            'subject_area' => 'Music',
            'order_status' => 'Delivered',
            'requested_start_date' => '2026-03-30',
            'created_by_contact_id' => $contact->id,
            'commission_rate' => 28,
            'entries' => [
                ['candidate_name' => 'Student A', 'grade' => '1'],
                ['candidate_name' => 'Student B', 'grade' => '2'],
                ['candidate_name' => 'Student C', 'grade' => '3'],
            ],
        ])
        ->assertRedirect();

    $order = Order::where('trinity_order_number', 'TRN-MULTI-001')->first();
    expect($order)->not->toBeNull();
    expect($order->candidates)->toBe(3);
    expect($order->examEntries()->count())->toBe(3);
});

test('store requires trinity order number', function () {
    $this->actingAs(orderAdmin())
        ->post(route('admin.orders.store'), [
            'delivery_method' => 'Digital',
            'created_by_contact_id' => orderTeacherContact()->id,
            'order_status' => 'Delivered',
            'commission_rate' => 20,
            'entries' => [['candidate_name' => 'X']],
        ])
        ->assertSessionHasErrors('trinity_order_number');
});

test('store accepts order without teacher (applicant-only)', function () {
    $this->actingAs(orderAdmin())
        ->post(route('admin.orders.store'), [
            'trinity_order_number' => 'TRN-NO-TEACHER',
            'delivery_method' => 'Digital',
            'subject_area' => 'Music',
            'order_status' => 'Delivered',
            'requested_start_date' => '2026-03-30',
            'created_by_contact_id' => null,
            'commission_rate' => 20,
            'applicant_name' => 'Daniel Rogers',
            'applicant_email' => 'exams@pulsemusicliverpool.com',
            'entries' => [['candidate_name' => 'Some Student']],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'trinity_order_number' => 'TRN-NO-TEACHER',
        'created_by_contact_id' => null,
        'applicant_name' => 'Daniel Rogers',
    ]);
});

test('store requires at least one candidate', function () {
    $this->actingAs(orderAdmin())
        ->post(route('admin.orders.store'), [
            'trinity_order_number' => 'TRN-NO-ENTRIES',
            'delivery_method' => 'Digital',
            'order_status' => 'Delivered',
            'created_by_contact_id' => orderTeacherContact()->id,
            'commission_rate' => 20,
            'entries' => [],
        ])
        ->assertSessionHasErrors('entries');
});

test('trinity order number must be unique', function () {
    Order::factory()->create(['trinity_order_number' => 'TRN-DUPLICATE']);

    $this->actingAs(orderAdmin())
        ->post(route('admin.orders.store'), [
            'trinity_order_number' => 'TRN-DUPLICATE',
            'delivery_method' => 'Digital',
            'order_status' => 'Delivered',
            'created_by_contact_id' => orderTeacherContact()->id,
            'commission_rate' => 20,
            'entries' => [['candidate_name' => 'X']],
        ])
        ->assertSessionHasErrors('trinity_order_number');
});

test('non-admin cannot store orders', function () {
    $this->actingAs(orderTeacher())
        ->post(route('admin.orders.store'), [
            'trinity_order_number' => 'TRN-FORBIDDEN',
            'delivery_method' => 'Digital',
            'order_status' => 'Delivered',
            'created_by_contact_id' => orderTeacherContact()->id,
            'commission_rate' => 20,
            'entries' => [['candidate_name' => 'X']],
        ])
        ->assertForbidden();
});

// ──────────────────────────────────────────
// Edit / Update
// ──────────────────────────────────────────

test('admin can view edit order form', function () {
    $order = Order::factory()->create([
        'requested_start_date' => '2026-03-30',
    ]);

    $this->actingAs(orderAdmin())
        ->get(route('admin.orders.edit', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Orders/Edit')
            ->has('order')
            ->has('teachers')
        );
});

test('non-admin cannot view edit order form', function () {
    $order = Order::factory()->create();

    $this->actingAs(orderTeacher())
        ->get(route('admin.orders.edit', $order))
        ->assertForbidden();
});

test('admin can update an existing order', function () {
    $contact = orderTeacherContact();
    $order = Order::factory()->create([
        'created_by_contact_id' => $contact->id,
        'trinity_order_number' => 'TRN-UPDATE-001',
        'order_status' => 'Submitted',
        'requested_start_date' => '2026-03-30',
    ]);

    // Seed an existing exam entry to update
    $entry = $order->examEntries()->create([
        'candidate_name' => 'Old Name',
        'grade' => '1',
        'delivery_method' => 'Digital',
        'source' => 'manual',
    ]);

    $this->actingAs(orderAdmin())
        ->put(route('admin.orders.update', $order), [
            'trinity_order_number' => 'TRN-UPDATE-001',
            'delivery_method' => 'Digital',
            'subject_area' => 'Music',
            'order_status' => 'Delivered',
            'requested_start_date' => '2026-03-30',
            'created_by_contact_id' => $contact->id,
            'commission_rate' => 20,
            'entries' => [
                [
                    'id' => $entry->id,
                    'candidate_name' => 'Updated Name',
                    'grade' => '2',
                    'score' => 85,
                    'result' => 'Merit',
                ],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'order_status' => 'Delivered',
    ]);

    $this->assertDatabaseHas('exam_entries', [
        'id' => $entry->id,
        'candidate_name' => 'Updated Name',
        'grade' => '2',
        'score' => 85,
        'result' => 'Merit',
    ]);
});

test('admin can add a new candidate to existing order', function () {
    $contact = orderTeacherContact();
    $order = Order::factory()->create([
        'created_by_contact_id' => $contact->id,
        'requested_start_date' => '2026-03-30',
    ]);

    $existing = $order->examEntries()->create([
        'candidate_name' => 'First Candidate',
        'delivery_method' => 'Digital',
        'source' => 'manual',
    ]);

    $this->actingAs(orderAdmin())
        ->put(route('admin.orders.update', $order), [
            'trinity_order_number' => $order->trinity_order_number,
            'delivery_method' => 'Digital',
            'subject_area' => 'Music',
            'order_status' => 'Delivered',
            'requested_start_date' => '2026-03-30',
            'created_by_contact_id' => $contact->id,
            'commission_rate' => 20,
            'entries' => [
                ['id' => $existing->id, 'candidate_name' => 'First Candidate'],
                ['candidate_name' => 'Second Candidate'],
            ],
        ])
        ->assertRedirect();

    expect($order->fresh()->examEntries()->count())->toBe(2);
    expect($order->fresh()->candidates)->toBe(2);
});

test('non-admin cannot update orders', function () {
    $order = Order::factory()->create();

    $this->actingAs(orderTeacher())
        ->put(route('admin.orders.update', $order), [
            'trinity_order_number' => $order->trinity_order_number,
            'delivery_method' => 'Digital',
            'order_status' => 'Delivered',
            'requested_start_date' => '2026-03-30',
            'created_by_contact_id' => orderTeacherContact()->id,
            'commission_rate' => 20,
            'entries' => [['candidate_name' => 'X']],
        ])
        ->assertForbidden();
});
