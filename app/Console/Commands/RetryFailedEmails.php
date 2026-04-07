<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\SellerNewOrderMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class RetryFailedEmails extends Command
{
    protected $signature = 'emails:retry';
    protected $description = 'Retry failed emails from EmailLog';

    public function handle()
    {
        // Fetch all failed emails
        $failedEmails = EmailLog::where('status', 'failed')->get();

        if ($failedEmails->isEmpty()) {
            $this->info('No failed emails to retry.');
            return Command::SUCCESS;
        }

        foreach ($failedEmails as $log) {
            try {
                // Mark as retrying
                $log->update(['status' => 'retrying']);

                // Fetch the related order
                $order = Order::find($log->order_id);

                if (!$order) {
                    $log->update([
                        'status' => 'failed',
                        'error' => 'Order not found',
                    ]);
                    continue;
                }

                // Retry sending based on type
                switch ($log->type) {
                    case 'order_placed':
                        Mail::to($log->to_email)->queue(new OrderPlacedMail($order));
                        break;

                    case 'order_shipped':
                        Mail::to($log->to_email)->queue(new OrderShippedMail($order));
                        break;

                    case 'seller_new_order':
                        Mail::to($log->to_email)->queue(new SellerNewOrderMail($order));
                        break;

                    default:
                        $log->update([
                            'status' => 'failed',
                            'error' => 'Unknown email type',
                        ]);
                        continue 2; // Skip to next log
                }

                // Mark as sent
                $log->update([
                    'status' => 'sent',
                    'error' => null,
                ]);

            } catch (\Exception $e) {
                // Log error and mark failed
                Log::error("Retry email failed for log ID {$log->id}: " . $e->getMessage());

                $log->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Retry process completed.');
        return Command::SUCCESS;
    }
}