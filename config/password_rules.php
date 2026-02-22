<?php

return [
    /* Minimum password length */
    'min_length' => env('PASSWORD_MIN_LENGTH', 6),

    /* Require at least one number */
    'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),

    /* Require at least one special character */
    'require_special' => env('PASSWORD_REQUIRE_SPECIAL', false),

    /* Require both upper and lower case letters */
    'require_mixed_case' => env('PASSWORD_REQUIRE_MIXED_CASE', false),

    /* UI rendering hints for the frontend */
    'ui' => [
        'fields' => [
            'password' => [
                'type' => 'password',
                'label' => 'Password',
                'placeholder' => 'Enter a secure password',
            ],
            'password_confirmation' => [
                'type' => 'password',
                'label' => 'Confirm Password',
                'placeholder' => 'Repeat your password',
            ],
        ],
        'hints' => [
            'min_length' => 'Minimum length configured on server',
            'require_numbers' => 'At least one number',
            'require_special' => 'At least one special character',
            'require_mixed_case' => 'Upper and lower case letters',
        ],
    ],
];
