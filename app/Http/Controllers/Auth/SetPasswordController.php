<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class SetPasswordController
{
    public function show(Request $request, string $token)
    {
        return view('auth.set-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker()->reset(
            [
                'token' => $validated['token'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function (User $user, string $password): void {
                $user->password = $password; // 'hashed' cast hashes it
                $user->email_verified_at = $user->email_verified_at ?? now();
                $user->save();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('filament.admin.auth.login')
                ->with('status', __($status));
        }

        throw ValidationException::withMessages(['email' => [__($status)]]);
    }
}
