<?php

namespace App\Console\Commands;

use App\Jobs\DispatchLalamoveDelivery;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\MayaReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--older-than=90 : Cancel unpaid pending orders older than this many minutes}';

    protected $description = 'Cancel stale unpaid orders and release their reserved stock';

    /**
     * A paid order whose webhook was lost must never be cancelled just
     * because Maya keeps erroring; but stock cannot be reserved forever.
     * After this long, the order is force-cancelled even on API errors.
     */
    protected const HARD_CAP_HOURS = 24;

    public function handle(InventoryService $inventory, MayaReconciliationService $reconciliation): int
    {
        $cutoff = now()->subMinutes(max(1, (int) $this->option('older-than')));
        $hardCap = now()->subHours(self::HARD_CAP_HOURS);

        $orders = Order::query()
            ->where('payment_status', '!=', 'paid')
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $expired = 0;
        $deferred = 0;

        foreach ($orders as $order) {
            // Final reconciliation before cancelling. Runs outside the locking
            // transaction: confirm() takes its own row lock. A webhook that
            // lands in between is handled by the guards inside the transaction.
            if ($order->checkout_session_id && $order->created_at->gt($hardCap)) {
                $result = $reconciliation->reconcile($order);

                if ($result === MayaReconciliationService::CONFIRMED) {
                    DispatchLalamoveDelivery::dispatch($order->id);

                    continue;
                }

                if ($result === MayaReconciliationService::ERROR) {
                    // Unknown state: defer to a later run rather than risk
                    // cancelling an order whose payment may have succeeded.
                    $deferred++;

                    continue;
                }
            }

            DB::transaction(function () use ($order, $inventory, &$expired) {
                $lockedOrder = Order::with('items')->lockForUpdate()->find($order->id);

                if (! $lockedOrder || $lockedOrder->payment_status === 'paid' || $lockedOrder->status !== 'pending') {
                    return;
                }

                $inventory->release($lockedOrder);
                $lockedOrder->payment_status = 'failed';
                $lockedOrder->status = 'cancelled';
                $lockedOrder->save();

                $expired++;
            });
        }

        $this->info("Expired {$expired} stale unpaid order(s).");

        if ($deferred > 0) {
            $this->warn("Deferred {$deferred} order(s): Maya state unknown.");
        }

        return self::SUCCESS;
    }
}
