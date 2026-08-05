<?php

namespace App\Console\Commands;

use App\Services\LalamoveService;
use Illuminate\Console\Command;

class RegisterLalamoveWebhook extends Command
{
    protected $signature = 'lalamove:register-webhook {url?}';

    protected $description = 'Register or update the Lalamove webhook URL';

    public function handle(LalamoveService $lalamove): int
    {
        $url = $this->argument('url') ?? config('services.lalamove.webhook_url', '');

        if ($url === '') {
            $this->error('No webhook URL provided. Pass one as an argument or set LALAMOVE_WEBHOOK_URL.');

            return self::FAILURE;
        }

        $result = $lalamove->updateWebhook($url);

        if (! $result) {
            $this->error('Failed to register webhook: '.($lalamove->getLastError() ?? 'Unknown error'));

            return self::FAILURE;
        }

        $this->info('Webhook registered: '.($result['url'] ?? $url));

        return self::SUCCESS;
    }
}
