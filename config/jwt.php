<?php

return [
    'secret' => env('JWT_SECRET', 'your-secret-key'),
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    'expiration' => env('JWT_EXPIRATION', 86400), // 24 hours
];
