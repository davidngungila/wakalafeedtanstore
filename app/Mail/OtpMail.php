<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code, public string $email, public int $minutes = 5) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OTP Code — Wakala Feedtan Store — '.$this->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: ['code' => $this->code, 'email' => $this->email, 'minutes' => $this->minutes],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
