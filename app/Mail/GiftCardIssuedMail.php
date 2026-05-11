<?php

namespace App\Mail;

use App\Models\GiftCard;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftCardIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GiftCard $giftCard,
        public Order $order,
        public float $amount,
        public bool $isTopup = false,
        public ?string $giftMessage = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isTopup
                ? 'Your Gift Card Has Been Topped Up'
                : 'You Received a Gift Card',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-card-issued',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
