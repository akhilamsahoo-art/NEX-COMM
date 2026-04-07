<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\EmailLog;
use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\SellerNewOrderMail;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    /**
     * Triggered when a new order is created
     */
    public function created(Order $order): void
    {
        // Load all required relationships
        $order->load(['user', 'items.product.tenant.user']);

        /**
         * ✅ 1. CUSTOMER EMAIL (Order Placed)
         */
        if ($order->user && $order->user->email) {
            try {
                Mail::to($order->user->email)
                    ->queue(new OrderPlacedMail($order));

                EmailLog::create([
                    'to_email' => $order->user->email,
                    'type' => 'order_placed',
                    'status' => 'sent',
                ]);
            } catch (\Exception $e) {
                EmailLog::create([
                    'to_email' => $order->user->email,
                    'type' => 'order_placed',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        /**
         * ✅ 2. SELLER EMAILS (Multi-tenant safe)
         */
        $sellers = $order->items
            ->map(fn($item) => $item->product->tenant->user ?? null)
            ->filter()
            ->unique('id');

        foreach ($sellers as $seller) {
            if ($seller && $seller->email) {
                try {
                    Mail::to($seller->email)
                        ->queue(new SellerNewOrderMail($order));

                    EmailLog::create([
                        'to_email' => $seller->email,
                        'type' => 'seller_new_order',
                        'status' => 'sent',
                    ]);
                } catch (\Exception $e) {
                    EmailLog::create([
                        'to_email' => $seller->email,
                        'type' => 'seller_new_order',
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Triggered when order is updated
     */
    public function updated(Order $order): void
    {
        // Only trigger when shipment_status changes to shipped
        if ($order->isDirty('shipment_status') && $order->shipment_status === 'shipped') {

            // Load user if not loaded
            $order->load('user');

            if ($order->user && $order->user->email) {
                try {
                    Mail::to($order->user->email)
                        ->queue(new OrderShippedMail($order));

                    EmailLog::create([
                        'to_email' => $order->user->email,
                        'type' => 'order_shipped',
                        'status' => 'sent',
                        'order_id' => $order->id,
                    ]);
                } catch (\Exception $e) {
                    EmailLog::create([
                        'to_email' => $order->user->email,
                        'type' => 'order_shipped',
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function deleted(Order $order): void
    {
        //
    }

    public function restored(Order $order): void
    {
        //
    }

    public function forceDeleted(Order $order): void
    {
        //
    }
}