<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('a visitor can register through Fortify', function () {
    Event::fake([Registered::class]);

    $response = $this->postJson('/register', [
        'name' => 'New User',
        'email' => 'new-user@example.com',
        'password' => 'Password9!',
        'password_confirmation' => 'Password9!',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('users', [
        'user_name' => 'New User',
        'email' => 'new-user@example.com',
    ]);
    $this->assertAuthenticated();
    Event::assertDispatched(Registered::class);
});

test('registration rejects duplicate email addresses', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->postJson('/register', [
        'name' => 'Another User',
        'email' => 'existing@example.com',
        'password' => 'Password9!',
        'password_confirmation' => 'Password9!',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('registration validates the Fortify fields', function () {
    $this->postJson('/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'weak',
        'password_confirmation' => 'different',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('a verified user can sign in through Fortify', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'Password9!',
        'email_verified_at' => now(),
    ]);

    $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'Password9!',
        'remember' => true,
    ])->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('an unverified user can sign in and is restricted by verification middleware', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'unverified@example.com',
        'password' => 'Password9!',
    ]);

    $this->postJson('/login', [
        'email' => $user->email,
        'password' => 'Password9!',
    ])->assertOk();

    $this->assertAuthenticatedAs($user);
    $this->getJson('/api/activities')->assertForbidden();
});

test('sign in rejects invalid credentials', function () {
    User::factory()->create(['email' => 'login@example.com']);

    $this->postJson('/login', [
        'email' => 'login@example.com',
        'password' => 'WrongPassword9!',
    ])->assertUnprocessable();

    $this->assertGuest();
});

test('an authenticated user can retrieve their account through Sanctum', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('name', $user->user_name)
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('email_verified_at', $user->email_verified_at->toJSON());
});

test('a guest cannot retrieve an account', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

test('a user can log out through Fortify', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/logout')->assertNoContent();
    $this->assertGuest();
});

test('a user can request a password reset link through Fortify', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'forgot@example.com']);

    $this->postJson('/forgot-password', ['email' => $user->email])->assertOk();
    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset validates the email address', function () {
    $this->postJson('/forgot-password', ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
