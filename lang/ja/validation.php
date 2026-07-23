<?php

return [
    'required' => ':attributeは必須項目です。',
    'confirmed' => ':attributeが一致しません。',
    'email' => '正しいメールアドレスの形式で入力してください。',
    'unique' => 'この:attributeは既に登録されています。',
    'min' => [
        'string' => ':attributeは:min文字以上で入力してください。',
    ],

    // フォームの英語のキー名を、画面に表示する日本語名に変換します
    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => 'お名前',
        'name_kana' => 'お名前（かな）',
        'birthday' => '生年月日',
        'gender' => '性別',
        'zip_code' => '郵便番号',
        'address' => '住所',
    ],
];
