<?php

namespace App\Support;

use App\Models\User;

class PersonName
{
    /**
     * @return array{0: string, 1: string}
     */
    public static function split(?string $full): array
    {
        $full = trim((string) $full);
        if ($full === '') {
            return ['', ''];
        }

        $parts = preg_split('/[\s　]+/u', $full, 2) ?: [$full];
        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        return [$parts[0], $parts[1]];
    }

    /**
     * @return array{
     *   last_name: string,
     *   first_name: string,
     *   last_name_kana: string,
     *   first_name_kana: string,
     *   tel: string,
     *   email: string,
     *   zip_code: string,
     *   address: string,
     *   building: string
     * }
     */
    public static function guestFieldsFromUser(User $user): array
    {
        [$last, $first] = self::split($user->name);
        [$lastKana, $firstKana] = self::split($user->name_kana);

        return [
            'last_name' => $last,
            'first_name' => $first,
            'last_name_kana' => $lastKana,
            'first_name_kana' => $firstKana,
            'tel' => '',
            'email' => (string) $user->email,
            'zip_code' => (string) ($user->zip_code ?? ''),
            'address' => (string) ($user->address ?? ''),
            'building' => '',
        ];
    }
}
