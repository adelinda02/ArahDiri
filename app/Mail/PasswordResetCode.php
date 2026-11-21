<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email berisi kode 6 digit untuk membuat kata sandi baru. */
class PasswordResetCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $name, public string $code, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode reset kata sandi ArahDiri');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset-code', text: 'emails.password-reset-code-text');
    }
}
