<?php

namespace App\Observers;

use App\Models\Order;
use App\Mail\OrderPlacedMail;
use App\Mail\OrderShippedMail;
use App\Mail\SellerNewOrderMail;
use Illuminate\Support\Facades\Mail;

class OrderObserver
{
    /**
     * Triggered when a new order is saved to the database.
     */
   public function created(Order $order): void
{
    // Force Laravel to fetch the user data from the database
    $order->load(['user', 'items.product.tenant.user']);

    // 1. Customer Email (Explicitly target the string)
    if ($order->user && $order->user->email) {
        $customerEmail = (string) $order->user->email; // Cast to string for safety
        Mail::to($customerEmail)->send(new OrderPlacedMail($order));
    }

    // 2. Seller Email
    $firstItem = $order->items->first();
    if ($firstItem && $firstItem->product && $firstItem->product->tenant) {
        $seller = $firstItem->product->tenant->user;
        if ($seller && $seller->email) {
            $sellerEmail = (string) $seller->email;
            Mail::to($sellerEmail)->send(new SellerNewOrderMail($order));
        }
    }
}

    /**
     * Triggered when an existing order is updated (e.g., in Filament).
     */
    public function updated(Order $order): void
    {
        // Check if the order_status was changed to 'shipped'
        if ($order->isDirty('order_status') && $order->order_status === 'shipped') {
            Mail::to($order->user->email)->send(new OrderShippedMail($order));
        }
    }


    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
