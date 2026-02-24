<?php

/**
 * Web Push (notifications when browser/app is closed).
 *
 * Generate VAPID keys: php artisan webpush:generate-vapid-keys
 * Or use an online generator (e.g. https://vapid.keys.generate) and add to .env:
 *   VAPID_PUBLIC_KEY=...
 *   VAPID_PRIVATE_KEY=...
 *   VAPID_SUBJECT=mailto:your@email.com
 */
return [
    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@'.parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
    ],
];
