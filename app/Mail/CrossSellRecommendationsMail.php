<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CrossSellRecommendationsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Collection $products
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recommended Picks For Your Next Order',
            from: config('mail.from.address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cross-sell-recommendations',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
