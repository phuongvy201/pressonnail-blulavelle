<?php

namespace App\Mail;

use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateApplicationApprovedToCreator extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AffiliateApplication $affiliateApplication,
        public Affiliate $affiliate,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your creator affiliate application was approved — '.config('app.name'),
            from: config('mail.from.address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.affiliate-application-approved-creator',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
