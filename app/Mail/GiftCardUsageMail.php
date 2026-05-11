<?php

namespace App\Mail;

use App\Models\GiftCard;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftCardUsageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GiftCard $giftCard,
        public Order $order,
        public float $usedAmount,
        public float $remainingBalance
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Gift Card Usage Confirmation',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-card-usage',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
