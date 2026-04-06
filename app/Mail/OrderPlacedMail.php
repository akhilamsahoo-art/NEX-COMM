<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    // 1. Standard variable declaration
    public $order;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Removed the "subject:" name to support older PHP versions
        return new Envelope(
            'Order Confirmation - #' . $this->order->id
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Removed the "view:" name to support older PHP versions
        return new Content(
            'emails.orders.placed'
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}