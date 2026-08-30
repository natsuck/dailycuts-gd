<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\DeliveryWindow;
use App\Services\LalamoveDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Primary dispatcher: books Lalamove deliveries for paid pending orders,
 * but only inside the same-day dispatch window (7am-3pm).
 *
 * Orders that were paid after the cut-off are held here automatically and
 * booked at the start of the next window, so no admin action is required.
 */
class DispatchPendingOrders extends Command
{
    protected $signature = 'orders:dispatch-pending';

    protected $description = 'Book Lalamove deliveries for paid pending orders inside the dispatch window';

    public function handle(LalamoveDeliveryService $delivery, DeliveryWindow $window): int
    {
        if (! $window->isOpen()) {
            $this->info('Dispatch window closed; next open at '.$window->nextOpenAt()->toDateTimeString().'.');

            return self::SUCCESS;
        }

        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereNull('lalamove_order_id')
            ->get();

        $dispatched = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                if ($delivery->dispatch($order)) {
                    $dispatched++;

                    continue;
                }

                $failed++;

                Log::warning('orders:dispatch-pending could not create a delivery.', [
                    'order_id' => $order->id,
                    'reason' => $delivery->getLastError(),
                ]);
            } catch (\Throwable $e) {
                $failed++;

                Log::error('orders:dispatch-pending exception', [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Dispatched {$dispatched} Lalamove delivery(ies). Failed: {$failed}.");

        return self::SUCCESS;
    }
}