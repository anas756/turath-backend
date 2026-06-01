<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class sendResetPassTokenMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $confirmationToken;


    /**
     * Create a new message instance.
     */
    public function __construct($confirmationToken, User $user)
    {
        $this->confirmationToken = $confirmationToken;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send Reset Pass Token Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $title = 'Email Confirmation';
       
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        
        $confirmationUrl = $frontendUrl . '/reset-token-confirmed?' . http_build_query([
            'status' => 'success',
            'email' => $this->user->email,
            'token' => $this->confirmationToken
        ]);        return new Content(
            view: 'users.confirmAcount',
            with: [
                'title' => $title,
                'userName' => $this->user->userName,
                'confirmationUrl' => $confirmationUrl
            ]
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
