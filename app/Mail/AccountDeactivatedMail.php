<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $lastName;
    public $reason;

    public function __construct(?string $firstName = null, ?string $lastName = null, ?string $reason = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte a été désactivé',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-deactivated',
        );
    }
}


