<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function show(): View
    {
        return view('auth.account.index', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);
        $this->auditLogger->log('account.profile_updated', 'users', 'user', (string) $user->id);

        return back()->with('status', 'Account details updated.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'string', function (string $attribute, mixed $value, $fail) use ($user): void {
                if (! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($request->string('password'))]);
        $this->auditLogger->log('account.password_changed', 'users', 'user', (string) $user->id);

        return back()->with('status', 'Password updated.');
    }
}
