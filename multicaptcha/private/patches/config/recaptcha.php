<?php

return [
    /*
     * Enable or disable captchas.
     */
    'enabled' => env('RECAPTCHA_ENABLED', true),

    /*
     * Select active provider:
     * - google
     * - turnstile
     * - hcaptcha
     */
    'provider' => env('RECAPTCHA_PROVIDER', 'google'),

    /*
     * Provider verification endpoints.
     */
    'domains' => [
        'google' => env('RECAPTCHA_DOMAIN_GOOGLE', 'https://www.google.com/recaptcha/api/siteverify'),
        'turnstile' => env('RECAPTCHA_DOMAIN_TURNSTILE', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
        'hcaptcha' => env('RECAPTCHA_DOMAIN_HCAPTCHA', 'https://hcaptcha.com/siteverify'),
    ],

    /*
     * Legacy key kept for compatibility with existing panel code/third-party mods.
     */
    'domain' => env('RECAPTCHA_DOMAIN', 'https://www.google.com/recaptcha/api/siteverify'),

    /*
     * Use a custom secret key, we use our public one by default.
     */
    'secret_key' => env('RECAPTCHA_SECRET_KEY', '6LcJcjwUAAAAALOcDJqAEYKTDhwELCkzUkNDQ0J5'),
    '_shipped_secret_key' => '6LcJcjwUAAAAALOcDJqAEYKTDhwELCkzUkNDQ0J5',

    /*
     * Use a custom website key, we use our public one by default.
     */
    'website_key' => env('RECAPTCHA_WEBSITE_KEY', '6LcJcjwUAAAAAO_Xqjrtj9wWufUpYRnK6BW8lnfn'),
    '_shipped_website_key' => '6LcJcjwUAAAAAO_Xqjrtj9wWufUpYRnK6BW8lnfn',

    /*
     * Domain verification is enabled by default and compares the host used while
     * solving the captcha response with the request host.
     */
    'verify_domain' => true,
];
