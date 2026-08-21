<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'demo' => [
                'enabled' => (bool) config('demo.enabled'),
                'refreshHours' => max(1, (int) config('demo.refresh_hours', 4)),
                'allowRegistration' => ! (bool) config('demo.enabled')
                    || (bool) config('demo.allow_registration'),
                'guestEmail' => (string) config('demo.guest.email'),
                'guestPassword' => (string) config('demo.guest.password'),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ];
    }
}
