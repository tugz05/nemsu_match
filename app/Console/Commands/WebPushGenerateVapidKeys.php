<?php

namespace App\Console\Commands;

use Minishlink\WebPush\VAPID;
use Illuminate\Console\Command;

class WebPushGenerateVapidKeys extends Command
{
    protected $signature = 'webpush:generate-vapid-keys';

    protected $description = 'Generate VAPID key pair for Web Push. Add the output to your .env as VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY.';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->line('Add these to your .env file:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();
        $this->line('Optional: VAPID_SUBJECT=mailto:your@email.com (or a URL)');
        $this->newLine();
        $this->warn('Keep the private key secret. Do not commit it to version control.');

        return self::SUCCESS;
    }
}
