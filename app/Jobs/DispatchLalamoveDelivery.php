<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\DeliveryWindow;
use App\Services\LalamoveDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Creates the Lalamove delivery for a paid order, with retries.
 *
 * Dispatched after the payment transaction commits. When the window is closed
 * (outside the same-day dispatch window) the order is deferred to the
 * scheduled orders:dispatch-pending command instead of booking a rider.
 * Inside the window, a soft failure inside LalamoveDeliveryService::dispatch()
 * (returns false) or an exception is surfaced as a job failure so the queue
 * retries with backoff.
 */
class DispatchLalamoveDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900, 3600, 7200];

    public function __construct(
        public int $orderId,
    ) {}

    public function handle(LalamoveDeliveryService $delivery, DeliveryWindow $window): void
    {
        $order = Order::find($this->orderId);

        if (! $order || $order->payment_status !== 'paid') {
            return;
        }

        if ($order->lalamove_order_id) {
            return;
        }

        // Outside the dispatch window the order is held for the scheduled
        // orders:dispatch-pending command, which books it at the next opening.
        if (! $window->isOpen()) {
            return;
        }

        if (! $delivery->dispatch($order)) {
            throw new RuntimeException($delivery->getLastError() ?? 'Lalamove dispatch failed.');
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Lalamove delivery could not be created after all retries.', [
            'order_id' => $this->orderId,
            'message' => $e->getMessage(),
        ]);
    }
}
