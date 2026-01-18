<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Console\Command;

class SyncPaymentStatus extends Command
{
    protected $signature = 'payment:sync {--days=7}';

    protected $description = 'Sync payment status for pending orders';

    public function handle()
    {
        $days = $this->option('days');
        $midtransService = app(MidtransService::class);

        $pendingOrders = Order::where('payment_status', 'unpaid')
            ->whereNotNull('payment_reference')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $this->info("Found {$pendingOrders->count()} pending orders to sync");

        $updated = 0;
        foreach ($pendingOrders as $order) {
            $result = $midtransService->getTransactionStatus($order->order_number);

            if ($result['success']) {
                $this->line("Checking order: {$order->order_number}");
                $updated++;
            }
        }

        $this->info("Sync completed. {$updated} orders processed.");
    }
}
