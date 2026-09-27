<?php

// tests/Feature/AuthAutofillTest.php
//
// Password managers (Apple Passwords, Chrome, 1Password) find a login's
// account box by autocomplete="username". On 27 Sep 2026 the login page had
// autocomplete="email" plus autofocus, and Apple Passwords offered no list
// until Paul clicked into the password box or refreshed: "email" reads as a
// contact field, and autofocus fires before the manager has scanned the form.
// Contact forms elsewhere keep autocomplete="email"; this only covers the
// pages where an email IS the account.

$accountPages = ['Login', 'Register', 'ForgotPassword', 'ResetPassword'];

test('account email boxes are marked as the username', function (string $page) {
    $source = file_get_contents(resource_path("js/pages/auth/{$page}.vue"));

    preg_match('/<MyInputConstructor\b(?=[^>]*name="email")[^>]*>/s', $source, $email);

    expect($email[0] ?? '')->toContain('autocomplete="username"');
})->with($accountPages);

test('the login and forgot-password email boxes are not autofocused', function (string $page) {
    $source = file_get_contents(resource_path("js/pages/auth/{$page}.vue"));

    preg_match('/<MyInputConstructor\b(?=[^>]*name="email")[^>]*>/s', $source, $email);

    expect($email[0] ?? '')->not->toMatch('/\sautofocus\b/');
})->with(['Login', 'ForgotPassword']);
