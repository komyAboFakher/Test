<?php
// app/Mail/TwoFactorVerificationMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Login Verification Code',
        );
    }

    public function content(): Content
    {
        // Pass the token to the email view
        return new Content(
            view: 'emails.2fa_verification',
            with: ['token' => $this->token],
        );
    }
}