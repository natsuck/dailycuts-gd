<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\LalamoveDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for paid orders whose Lalamove delivery was never created
 * (e.g. the queue worker was down when DispatchLalamoveDelivery exhausted
 * its retries). Runs without a queue worker so it recovers even then.
 */
class RetryLalamoveDispatch extends Command
{
    protected $signature = 'orders:retry-lalamove-dispatch {--days=7 : Only retry orders updated within this many days}';

    protected $description = 'Create missing Lalamove deliveries for paid pending orders';

    public function handle(LalamoveDeliveryService $delivery): int
    {
        $since = now()->subDays(max(1, (int) $this->option('days')));

        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereNull('lalamove_order_id')
            ->where('updated_at', '>=', $since)
            ->get();

        $dispatched = 0;

        foreach ($orders as $order) {
            try {
                if ($delivery->dispatch($order)) {
                    $dispatched++;

                    continue;
                }

                Log::warning('Lalamove retry could not create a delivery.', [
                    'order_id' => $order->id,
                    'reason' => $delivery->getLastError(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Lalamove retry exception', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Dispatched {$dispatched} Lalamove delivery(ies).");

        return self::SUCCESS;
    }
}
