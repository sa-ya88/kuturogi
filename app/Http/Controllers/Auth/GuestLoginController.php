<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\DemoGuestUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        $user = DemoGuestUser::ensureAndSyncToAdmin();
        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $redirect = $request->input('redirect');

        if (is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect);
        }

        return redirect()->intended(route('top', absolute: false));
    }
}
