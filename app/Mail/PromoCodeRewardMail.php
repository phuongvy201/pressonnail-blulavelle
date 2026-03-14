<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PromoCodeRewardMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientEmail;
    public string $promoCode;
    public string $triggerLabel;
    public ?string $promoDescription;

    public function __construct(string $recipientEmail, string $promoCode, string $triggerLabel = '', ?string $promoDescription = null)
    {
        $this->recipientEmail = $recipientEmail;
        $this->promoCode = $promoCode;
        $this->triggerLabel = $triggerLabel;
        $this->promoDescription = $promoDescription;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Promo Code – ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.promo_code_reward',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
