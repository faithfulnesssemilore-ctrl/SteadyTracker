<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        if (! $googleUser->getEmail() || ! (($googleUser->user['email_verified'] ?? false))) {
            abort(403, 'A verified Google email address is required.');
        }

        $user = DB::transaction(function () use ($googleUser): User {
            $user = User::where('google_id', $googleUser->getId())->first()
                ?? User::where('email', $googleUser->getEmail())->first();

            if ($user && $user->google_id && $user->google_id !== $googleUser->getId()) {
                abort(409, 'This Google account is already linked to another user.');
            }

            if (! $user) {
                $user = new User;
            }

            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'user_name' => $user->user_name ?: ($googleUser->getName() ?: $googleUser->getEmail()),
                'email' => $googleUser->getEmail(),
                'email_verified_at' => now(),
            ])->save();

            return $user;
        });

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect('/dashboard');
    }
}
