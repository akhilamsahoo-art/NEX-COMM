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
        // -------------------------------
        // 1️⃣ Auto-deliver shipped orders
        // -------------------------------
        $schedule->call(function () {
            Log::info('Checking for orders to auto-deliver...');

            $affected = \App\Models\Order::where('shipment_status', 'shipped')
                ->whereNotNull('shipped_at')
                ->where('shipped_at', '<=', now()->subDay())
                ->where('order_status', '!=', 'delivered') // safety
                ->update([
                    'order_status' => 'delivered',
                    'delivered_at' => now(), // optional but recommended
                ]);

            if ($affected > 0) {
                Log::info("Successfully auto-delivered {$affected} orders.");
            }
        })->everyFiveMinutes(); // changed from everyMinute() for performance

        // -------------------------------
        // 2️⃣ Retry failed emails
        // -------------------------------
        $schedule->command('emails:retry')->everyFiveMinutes();
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