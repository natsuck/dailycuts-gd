<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\LalamoveDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Creates the Lalamove delivery for a paid order, with retries.
 *
 * Dispatched after the payment transaction commits. A soft failure inside
 * LalamoveDeliveryService::dispatch() (returns false) or an exception is
 * surfaced as a job failure so the queue retries with backoff; the scheduled
 * orders:retry-lalamove-dispatch command is the safety net if the worker is
 * down.
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

    public function handle(LalamoveDeliveryService $delivery): void
    {
        $order = Order::find($this->orderId);

        if (! $order || $order->payment_status !== 'paid') {
            return;
        }

        if ($order->lalamove_order_id) {
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
