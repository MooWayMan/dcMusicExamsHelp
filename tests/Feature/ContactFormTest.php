<?php

use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
use App\Mail\ContactAutoReply;
use App\Mail\ContactFormSubmission;

// ──────────────────────────────────────────
// Contact Form (POST /contact)
// ──────────────────────────────────────────

test('contact form sends email with valid data', function () {
    Mail::fake();

    $response = $this->post('/contact', [
        'name' => 'Sarah Connor',
        'email' => 'sarah@gmail.com',
        'subject' => 'Exam enquiry',
        'message' => 'I would like to book a Grade 3 piano exam please.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Mail::assertSent(ContactFormSubmission::class, function ($mail) {
        return $mail->hasTo('musicexams@musicexams.help');
    });
});

test('contact auto-reply body links the free teacher account signup', function () {
    // The auto-reply lands in the inbox of anyone who uses the public
    // contact form — many of them music teachers reaching out to ask
    // about exam booking. Surface the dashboard signup right alongside
    // the existing quick-links so they can act on it without bouncing
    // back to the marketing site.
    $rendered = (new ContactAutoReply('Sarah Connor', 'Exam enquiry'))->render();

    expect($rendered)->toContain('/register');
    expect($rendered)->toContain('Free teacher account');
});

test('contact form requires name', function () {
    $response = $this->post('/contact', [
        'email' => 'test@gmail.com',
        'message' => 'Hello',
    ]);

    $response->assertSessionHasErrors('name');
});

test('contact form requires email', function () {
    $response = $this->post('/contact', [
        'name' => 'Test',
        'message' => 'Hello',
    ]);

    $response->assertSessionHasErrors('email');
});

test('contact form requires message', function () {
    $response = $this->post('/contact', [
        'name' => 'Test',
        'email' => 'test@gmail.com',
    ]);

    $response->assertSessionHasErrors('message');
});

test('contact form rejects invalid email', function () {
    $response = $this->post('/contact', [
        'name' => 'Test',
        'email' => 'not-valid',
        'message' => 'Hello',
    ]);

    $response->assertSessionHasErrors('email');
});

test('contact form subject is optional', function () {
    Mail::fake();

    $response = $this->post('/contact', [
        'name' => 'Test User',
        'email' => 'test@gmail.com',
        'message' => 'A message without a subject.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('contact form message cannot exceed 5000 characters', function () {
    $response = $this->post('/contact', [
        'name' => 'Test',
        'email' => 'test@gmail.com',
        'message' => str_repeat('a', 5001),
    ]);

    $response->assertSessionHasErrors('message');
});

test('contact page loads', function () {
    $this->get('/contact')->assertStatus(200);
});
