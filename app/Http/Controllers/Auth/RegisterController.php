<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function showRegisterForm(): View
    {
        abort_unless(config('app.allow_public_registration', false), 403, 'Public registration is disabled.');

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(config('app.allow_public_registration', false), 403, 'Public registration is disabled.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        return redirect()->route('login')->with('status', 'Registration successful. Please sign in.');
    }
}
