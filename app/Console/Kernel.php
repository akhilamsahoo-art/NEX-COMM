<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            // 1. Log that the task is starting
            Log::info('Checking for orders to auto-deliver...');

            // 2. Perform the update
            $affected = \App\Models\Order::Order::where('shipment_status', 'shipped')
                ->whereNotNull('shipped_at')
                // Orders older than 24 hours (1 day)
                ->where('shipped_at', '<=', now()->subDay()) 
                ->update(['order_status' => 'delivered']);
            // 3. Log the result so you know it worked
            if ($affected > 0) {
                Log::info("Successfully auto-delivered {$affected} orders.");
            }
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}