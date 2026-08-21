<?php

return [

    /*
    | ポートフォリオ公開デモ用。採用担当者が本物の個人情報を使わずに操作できるようにする。
    */
    'enabled' => (bool) env('DEMO_MODE', true),

    'refresh_hours' => (int) env('DEMO_REFRESH_HOURS', 4),

    'allow_registration' => (bool) env('DEMO_ALLOW_REGISTRATION', false),

    'guest' => [
        'email' => env('DEMO_GUEST_EMAIL', 'guest@example.com'),
        'password' => env('DEMO_GUEST_PASSWORD', 'password'),
        'name' => 'ゲスト 太郎',
        'name_kana' => 'げすと たろう',
        'birthday' => '1990-01-01',
        'gender' => 'other',
        'zip_code' => '1000001',
        'address' => '東京都千代田区千代田1-1',
    ],

];
