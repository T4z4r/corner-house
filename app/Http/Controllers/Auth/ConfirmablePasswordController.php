<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.confirm-password', [
            'continueLabel' => $this->continueLabel($request->session()->get('url.intended')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $credentials['password'],
        ])) {
            throw ValidationException::withMessages([
                'password' => __('The password you entered does not match our records.'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', Date::now()->getTimestamp());

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Map the requested URL to a friendly label for the confirmation modal.
     */
    private function continueLabel(?string $intended): string
    {
        $labels = [
            'payments' => 'Payments',
            'reservations' => 'Bookings',
            'calendar' => 'Calendar',
            'settings' => 'Settings',
        ];

        foreach ($labels as $segment => $label) {
            if ($intended !== null && str_contains($intended, '/'.$segment)) {
                return $label;
            }
        }

        return 'the admin area';
    }
}