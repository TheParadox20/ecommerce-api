<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class FixManualOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-manual-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes old manual orders that have a payment reference but null payment status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Order::whereNull('payment_status')
            ->whereNotNull('payment_reference')
            ->update(['payment_status' => 'pending']);

        $this->info("Successfully updated {$count} old manual orders to 'pending' status.");
    }
}
