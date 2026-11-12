<?php

use App\Http\Controllers\Auth\RegistrationKeywordController;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['fortify.registration_keyword' => 'segredo']);
});

test('the registration keyword screen can be rendered', function () {
    $this->get(route('register.keyword'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/RegisterKeyword'));
});

test('the registration screen redirects to the keyword screen until the keyword is verified', function () {
    $this->get(route('register'))
        ->assertRedirect(route('register.keyword'));
});

test('guests with the correct keyword are redirected to the registration screen', function () {
    $this->post(route('register.keyword.store'), ['keyword' => 'segredo'])
        ->assertRedirect(route('register'))
        ->assertSessionHas(RegistrationKeywordController::VERIFIED_SESSION_KEY, true);

    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Register'));
});

test('guests with a wrong keyword receive a 403 response', function () {
    $this->post(route('register.keyword.store'), ['keyword' => 'errado'])
        ->assertForbidden()
        ->assertSessionMissing(RegistrationKeywordController::VERIFIED_SESSION_KEY);
});

test('guests receive a 403 response when no registration keyword is configured', function () {
    config(['fortify.registration_keyword' => null]);

    $this->post(route('register.keyword.store'), ['keyword' => 'segredo'])
        ->assertForbidden();
});

test('the keyword is required', function () {
    $this->post(route('register.keyword.store'), ['keyword' => ''])
        ->assertSessionHasErrors(['keyword' => 'Informe a palavra-chave.']);
});

test('verified guests can register', function () {
    $this->withSession([RegistrationKeywordController::VERIFIED_SESSION_KEY => true])
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('dashboard', absolute: false))
        ->assertSessionMissing(RegistrationKeywordController::VERIFIED_SESSION_KEY);

    $this->assertAuthenticated();
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('guests that did not verify the keyword receive a 403 response when registering', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();

    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
});
