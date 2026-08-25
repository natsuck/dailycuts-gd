<?php

namespace App\Console\Commands;

use App\Jobs\DispatchLalamoveDelivery;
use App\Models\Order;
use App\Services\MayaReconciliationService;
use Illuminate\Console\Command;

/**
 * Safety net for lost Maya webhooks: re-asks Maya about pending unpaid
 * orders and confirms the ones that were actually paid. Cancellation of
 * orders that were never paid stays with orders:expire-unpaid.
 */
class ReconcileMayaOrders extends Command
{
    protected $signature = 'orders:reconcile-maya {--older-than=5 : Only reconcile orders older than this many minutes}';

    protected $description = 'Reconcile pending unpaid Maya orders against the Get Checkout API and confirm missed payments';

    public function handle(MayaReconciliationService $reconciliation): int
    {
        $orders = Order::query()
            ->where('payment_status', '!=', 'paid')
            ->where('status', 'pending')
            ->whereNotNull('checkout_session_id')
            ->where('created_at', '<=', now()->subMinutes(max(1, (int) $this->option('older-than'))))
            ->get();

        $confirmed = 0;

        foreach ($orders as $order) {
            if ($reconciliation->reconcile($order) === MayaReconciliationService::CONFIRMED) {
                DispatchLalamoveDelivery::dispatch($order->id);

                $confirmed++;
            }
        }

        $this->info("Reconciled {$confirmed} Maya payment(s).");

        return self::SUCCESS;
    }
}
