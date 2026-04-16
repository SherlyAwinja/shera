<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use LogicException;

class OrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public ?OrderLog $log = null;
    public ?OrderLog $orderLog = null;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, OrderLog $log)
    {
        $this->order = $order;
        $this->log = $log;
        $this->orderLog = $log;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $Subject = 'Order Status Updated- ' . config('app.name');
        return new Envelope(
            subject: $Subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order_status_updated',
            with: [
                'order' => $this->order,
                'log' => $this->resolveLog(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function resolveLog(): OrderLog
    {
        if ($this->log instanceof OrderLog) {
            return $this->log;
        }

        if ($this->orderLog instanceof OrderLog) {
            return $this->orderLog;
        }

        throw new LogicException('Order status update email is missing its order log payload.');
    }
}
