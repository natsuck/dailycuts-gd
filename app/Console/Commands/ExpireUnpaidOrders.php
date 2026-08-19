<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--older-than=90 : Cancel unpaid pending orders older than this many minutes}';

    protected $description = 'Cancel stale unpaid orders and release their reserved stock';

    public function handle(InventoryService $inventory): int
    {
        $cutoff = now()->subMinutes(max(1, (int) $this->option('older-than')));

        $orders = Order::query()
            ->where('payment_status', '!=', 'paid')
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $expired = 0;

        foreach ($orders as $order) {
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

        return self::SUCCESS;
    }
}
