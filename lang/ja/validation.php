<?php

return [
    'required' => ':attributeを入力してください。',
    'string' => ':attributeは文字列で入力してください。',
    'email' => ':attributeはメールアドレス形式で入力してください。',
    'confirmed' => ':attribute確認と一致しません。',

    'min' => [
        'numeric' => ':attributeは:min以上にしてください。',
        'file' => ':attributeは:minKB以上にしてください。',
        'string' => ':attributeは:min文字以上で入力してください。',
        'array' => ':attributeは:min個以上にしてください。',
    ],

    'max' => [
        'numeric' => ':attributeは:max以下にしてください。',
        'file' => ':attributeは:maxKB以下にしてください。',
        'string' => ':attributeは:max文字以内で入力してください。',
        'array' => ':attributeは:max個以下にしてください。',
    ],

    'attributes' => [
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
    ],
];
