<?php

namespace App\Mail;

use App\Models\TripInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TripInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TripInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Undangan Trip: {$this->invitation->trip->title} ✈️",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trip-invitation',
        );
    }
}
