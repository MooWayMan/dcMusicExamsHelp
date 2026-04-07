<?php

// ──────────────────────────────────────────
// Public Routes (No Auth Required)
// ──────────────────────────────────────────

test('GET / returns 200', function () {
    $this->get('/')
        ->assertStatus(200);
});

test('GET /faq returns 200', function () {
    $this->get('/faq')
        ->assertStatus(200);
});

test('GET /for-teachers returns 200', function () {
    $this->get('/for-teachers')
        ->assertStatus(200);
});

test('GET /for-parents returns 200', function () {
    $this->get('/for-parents')
        ->assertStatus(200);
});

test('GET /for-students returns 200', function () {
    $this->get('/for-students')
        ->assertStatus(200);
});

test('GET /privacy returns 200', function () {
    $this->get('/privacy')
        ->assertStatus(200);
});

test('GET /cookies returns 200', function () {
    $this->get('/cookies')
        ->assertStatus(200);
});

test('GET /incentives returns 200', function () {
    $this->get('/incentives')
        ->assertStatus(200);
});

test('GET /recognition returns 200', function () {
    $this->get('/recognition')
        ->assertStatus(200);
});

test('GET /exam-guide returns 200', function () {
    $this->get('/exam-guide')
        ->assertStatus(200);
});

test('GET /exam-guide/ucas-points returns 200', function () {
    $this->get('/exam-guide/ucas-points')
        ->assertStatus(200);
});

test('GET /exam-guide/what-to-expect returns 200', function () {
    $this->get('/exam-guide/what-to-expect')
        ->assertStatus(200);
});

test('GET /exam-guide/digital-exams returns 200', function () {
    $this->get('/exam-guide/digital-exams')
        ->assertStatus(200);
});

test('GET /exam-guide/grades-explained returns 200', function () {
    $this->get('/exam-guide/grades-explained')
        ->assertStatus(200);
});

test('GET /exam-fees returns 200', function () {
    $this->get('/exam-fees')
        ->assertStatus(200);
});

test('GET /for-teachers/faber-discounts returns 200', function () {
    $this->get('/for-teachers/faber-discounts')
        ->assertStatus(200);
});

test('GET /for-teachers/awards returns 200', function () {
    $this->get('/for-teachers/awards')
        ->assertStatus(200);
});

test('GET /contact returns 200', function () {
    $this->get('/contact')
        ->assertStatus(200);
});

test('GET /about returns 200', function () {
    $this->get('/about')
        ->assertStatus(200);
});

// ──────────────────────────────────────────
// Breadcrumb ?from= parameter tests
// ──────────────────────────────────────────

test('GET /incentives?from=for-teachers returns 200', function () {
    $this->get('/incentives?from=for-teachers')
        ->assertStatus(200);
});

test('GET /incentives?from=for-students returns 200', function () {
    $this->get('/incentives?from=for-students')
        ->assertStatus(200);
});

test('GET /recognition?from=for-teachers returns 200', function () {
    $this->get('/recognition?from=for-teachers')
        ->assertStatus(200);
});

test('GET /recognition?from=for-students returns 200', function () {
    $this->get('/recognition?from=for-students')
        ->assertStatus(200);
});

test('GET /recognition?from=for-parents returns 200', function () {
    $this->get('/recognition?from=for-parents')
        ->assertStatus(200);
});

test('GET /recognition?from=incentives returns 200', function () {
    $this->get('/recognition?from=incentives')
        ->assertStatus(200);
});

test('GET /for-teachers/awards?from=incentives returns 200', function () {
    $this->get('/for-teachers/awards?from=incentives')
        ->assertStatus(200);
});
