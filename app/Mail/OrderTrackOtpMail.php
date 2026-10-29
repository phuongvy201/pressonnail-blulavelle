<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderTrackOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $orderNumber,
        public string $code,
        public string $customerName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your BluLavelle order verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-track-otp',
        );
    }
}
