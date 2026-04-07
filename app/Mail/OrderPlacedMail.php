<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable implements ShouldQueue
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
    public function build()
{
    return $this->subject('Order Confirmation - #' . $this->order->id)
                ->view('emails.orders.placed');
}

    /**
     * Get the message envelope.
     */
   public function envelope(): Envelope
{
    return new Envelope(
        subject: 'Order Confirmation - #' . $this->order->id
    );
}

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Removed the "view:" name to support older PHP versions
        return new Content(
           view: 'emails.orders.placed'
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