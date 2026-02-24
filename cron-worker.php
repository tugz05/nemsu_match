<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

// Simple entry point for Hostinger cron to process queued jobs (including Web Push notifications).
// Example Hostinger cron command (adjust path and PHP binary):
//   * * * * * /usr/bin/php /home/USER/path/to/dating-app/cron-worker.php

require __DIR__ . '/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__ . '/bootstrap/app.php';

/** @var ConsoleKernel $kernel */
$kernel = $app->make(ConsoleKernel::class);
$kernel->bootstrap();

// Run queued jobs for a short burst, then exit so cron can invoke again.
Artisan::call('queue:work', [
    '--stop-when-empty' => true,
    '--max-jobs' => 50,
    '--max-time' => 50,
    '--quiet' => true,
]);

