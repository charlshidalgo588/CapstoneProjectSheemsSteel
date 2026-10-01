<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Vue sends API requests to `/api/...`
    | Login/logout use web, so we include them too.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Your frontend runs at http://localhost:5173
    | Also allow the VS Code dev tunnel URL used for testing on other devices.
    |
    */

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods & Headers
    |--------------------------------------------------------------------------
    */

    'allowed_methods' => ['*'],

    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Cookies & Sanctum
    |--------------------------------------------------------------------------
    */

    'supports_credentials' => true,

    /*
    |--------------------------------------------------------------------------
    | Other Settings (Default)
    |--------------------------------------------------------------------------
    */

    'allowed_origins_patterns' => [
        '#^https://.*\.asse\.devtunnels\.ms$#',
    ],

    'exposed_headers' => [],

    'max_age' => 0,
];