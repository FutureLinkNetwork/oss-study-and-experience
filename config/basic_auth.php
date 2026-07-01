<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basic Authentication Username
    |--------------------------------------------------------------------------
    |
    | The username for HTTP Basic authentication on login screens.
    | When not set, Basic auth is effectively disabled (middleware will pass through).
    |
    */

    'username' => env('BASIC_AUTH_USERNAME'),

    /*
    |--------------------------------------------------------------------------
    | Basic Authentication Password
    |--------------------------------------------------------------------------
    |
    | The password for HTTP Basic authentication. Use a strong password in production.
    | Production should always use HTTPS when Basic auth is enabled.
    |
    */

    'password' => env('BASIC_AUTH_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Basic Authentication Enabled
    |--------------------------------------------------------------------------
    |
    | When false (e.g. APP_ENV=local or testing), Basic auth is not applied.
    |
    */

    'enabled' => ! in_array(config('app.env'), ['local'], true),

    /*
    |--------------------------------------------------------------------------
    | Realm (WWW-Authenticate header)
    |--------------------------------------------------------------------------
    |
    | The realm value shown in the browser's Basic auth dialog.
    |
    */

    'realm' => env('BASIC_AUTH_REALM', config('app.name')),

    /*
    |--------------------------------------------------------------------------
    | Routes to Protect
    |--------------------------------------------------------------------------
    |
    | Which login screens require Basic auth. Keys: user_login, business_login, admin_login.
    | Parsed from BASIC_AUTH_ROUTES (comma-separated, e.g. "user,business,admin").
    |
    */

    'routes' => [
        // 'user_login' => in_array('user', array_map('trim', explode(',', (string) env('BASIC_AUTH_ROUTES', 'user,business,admin'))), true),
        // 'business_login' => in_array('business', array_map('trim', explode(',', (string) env('BASIC_AUTH_ROUTES', 'user,business,admin'))), true),
        'admin_login' => in_array('admin', array_map('trim', explode(',', (string) env('BASIC_AUTH_ROUTES', 'user,business,admin'))), true),
    ],

];
