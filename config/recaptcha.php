<?php

return [

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Site Key
    |--------------------------------------------------------------------------
    |
    | The site key for Google reCAPTCHA v3. Used in the frontend to load
    | the reCAPTCHA script and obtain tokens.
    |
    */

    'site_key' => env('RECAPTCHA_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key for server-side verification with Google's API.
    |
    */

    'secret_key' => env('RECAPTCHA_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Enabled
    |--------------------------------------------------------------------------
    |
    | When false (e.g. APP_ENV=local or testing), reCAPTCHA is not shown or validated.
    |
    */

    'enabled' => ! in_array(config('app.env'), ['local', 'testing'], true),

    /*
    |--------------------------------------------------------------------------
    | Minimum Score (v3 only)
    |--------------------------------------------------------------------------
    |
    | reCAPTCHA v3 returns a score from 0.0 to 1.0. Requests with score below
    | this value are considered likely bot traffic. Default 0.5.
    |
    */

    'min_score' => 0.5,

];
