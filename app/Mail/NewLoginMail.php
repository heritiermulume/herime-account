<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLoginMail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $lastName;
    public $ip;
    public $device;
    public $time;

    public function __construct(?string $firstName, ?string $lastName, ?string $ip, ?string $device, string $time)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->ip = $ip;
        $this->device = $device;
        $this->time = $time;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle connexion à votre compte',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-login',
        );
    }
}


