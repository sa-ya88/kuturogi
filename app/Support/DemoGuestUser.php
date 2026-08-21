<?php

namespace App\Support;

use App\Models\User;
use App\Services\IntegrationWebhookDispatcher;

class DemoGuestUser
{
    public static function firstOrCreate(): User
    {
        $guest = config('demo.guest');

        return User::query()->updateOrCreate(
            ['email' => $guest['email']],
            [
                'name' => $guest['name'],
                'name_kana' => $guest['name_kana'],
                'password' => $guest['password'],
                'email_verified_at' => now(),
                'birthday' => $guest['birthday'],
                'gender' => $guest['gender'],
                'zip_code' => $guest['zip_code'],
                'address' => $guest['address'],
            ],
        );
    }

    public static function ensureAndSyncToAdmin(): User
    {
        $user = static::firstOrCreate();

        app(IntegrationWebhookDispatcher::class)->dispatch(
            'user.registered',
            UserIntegrationPayload::from($user),
        );

        return $user;
    }
}
