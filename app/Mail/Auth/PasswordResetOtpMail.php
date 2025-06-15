<?php

namespace App\Mail\Auth; // Ensure the namespace is correct

use Illuminate\Bus\Queueable; // Allows the mailable to be queued for background sending
use Illuminate\Contracts\Queue\ShouldQueue; // Interface for queueable mailables
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content; // For defining content using Markdown or HTML views
use Illuminate\Mail\Mailables\Envelope; // For defining the email's subject, sender, recipients
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable implements ShouldQueue // Implement ShouldQueue if you want to queue emails
{
    use Queueable, SerializesModels;

    public string $otp; // Public property to pass OTP to the view
    public string $userName; // Public property to pass user's name to the view

    /**
     * Create a new message instance.
     *
     * @param string $otp The One-Time Password.
     * @param string $userName The name of the user receiving the email.
     */
    public function __construct(string $otp, string $userName)
    {
        $this->otp = $otp;
        $this->userName = $userName;
    }

    /**
     * Get the message envelope.
     * Defines the email's subject, from address, etc.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(env('MAIL_FROM_ADDRESS', 'hello@example.com'), env('MAIL_FROM_NAME', 'Example App')),
            subject: config('app.name') . ' - Password Reset OTP', // Dynamically set subject using app name
        );
    }

    /**
     * Get the message content definition.
     * Specifies the view to be used for the email body.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.password-reset-otp', // Path to the Markdown Blade view
            with: [ // Data to pass to the Markdown view
                'otp' => $this->otp,
                'name' => $this->userName, // Pass userName as 'name' to the view
                'appName' => config('app.name'), // Pass app name for consistency in the email
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // No attachments for this email
        return [];
    }
}