<?php

namespace App\Mail;

use App\Models\AffiliateApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateApplicationSubmittedToAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AffiliateApplication $affiliateApplication)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New affiliate / KOC application: '.$this->affiliateApplication->full_name,
            from: config('mail.from.address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.affiliate-application-submitted-admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
