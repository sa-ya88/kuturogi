<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationMagicLink;
use App\Services\IntegrationWebhookDispatcher;
use App\Support\UserIntegrationPayload;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request (Step 1: Send magic link).
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->closedRegistrationRedirect()) {
            return $redirect;
        }
        $request->validate([
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
        ]);

        Log::info('Registration magic link requested.');

        Notification::route('mail', $request->email)
            ->notify(new RegistrationMagicLink($request->email));

        // 【ここを修正】back() ではなく、専用の完了画面ルートへ確実にリダイレクトさせます
        return redirect()->route('register.sent');
    }


    public function sent(): Response|RedirectResponse
    {
        if ($redirect = $this->closedRegistrationRedirect()) {
            return $redirect;
        }

        return Inertia::render('Auth/RegisterSent');
    }

    /**
     * Display the registration details form (Step 2).
     */
    public function showRegistrationForm(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->closedRegistrationRedirect()) {
            return $redirect;
        }
        if (! $request->hasValidSignature()) {
            abort(403, 'このリンクは無効か、有効期限が切れています。');
        }

        return Inertia::render('Auth/RegisterDetails', [
            'email' => $request->email,
        ]);
    }

    /**
     * Complete registration (Step 3).
     */
    public function completeRegistration(Request $request): RedirectResponse
    {
        if ($redirect = $this->closedRegistrationRedirect()) {
            return $redirect;
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'name_kana' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'birthday' => 'required|date',
            'gender' => 'required|string',
            'zip_code' => 'required|string|max:10',
            'address' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'name_kana' => $request->name_kana,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birthday' => $request->birthday,
            'gender' => $request->gender,
            'zip_code' => $request->zip_code,
            'address' => $request->address,
        ]);

        event(new Registered($user));

        app(IntegrationWebhookDispatcher::class)->dispatch(
            'user.registered',
            UserIntegrationPayload::from($user),
        );

        Auth::login($user);

        return redirect(route('top', absolute: false));
    }

    protected function closedRegistrationRedirect(): ?RedirectResponse
    {
        if (! config('demo.enabled') || config('demo.allow_registration')) {
            return null;
        }

        return redirect()
            ->route('login')
            ->with('status', '公開デモでは新規会員登録を停止しています。'.config('demo.guest.email').' でログインしてください。');
    }
}
