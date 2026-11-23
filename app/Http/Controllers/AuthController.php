<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // this controller is used for authentication related actions, such as retrieving the authenticated user
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function destroy(Request $request): Response
    {
        $user = $request->user();

        if ($user->password) {
            $validated = $request->validate([
                'current_password' => ['required', 'string'],
            ]);

            if (! Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'The provided password is incorrect.',
                ]);
            }
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $request->user()->delete();

        return response()->noContent();
    }
}
