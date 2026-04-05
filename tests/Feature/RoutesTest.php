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
