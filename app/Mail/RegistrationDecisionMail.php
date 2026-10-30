<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public bool $approved) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->approved ? 'Akun HotelTask Anda telah disetujui' : 'Status pendaftaran HotelTask',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.registration-decision');
    }
}
