<?php

namespace App\Support;

use App\Models\User;

class UserIntegrationPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function from(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'name_kana' => $user->name_kana,
            'email' => $user->email,
            'birthday' => optional($user->birthday)?->toDateString(),
            'gender' => $user->gender,
            'zip_code' => $user->zip_code,
            'address' => $user->address,
        ];
    }
}
