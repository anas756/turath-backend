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

class ConfirmAccount extends Mailable  // Fixed typo
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
            subject: 'Confirme ton adresse Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $title = 'Email Confirmation';

        
        $backendUrl = config('app.url', 'http://localhost:8000');

        $confirmationUrl = $backendUrl . "/email-confirmed/" . urlencode($this->user->email) . "?" . http_build_query([
            'token' => $this->confirmationToken
        ]);

        return new Content(
            view: 'users.confirmAccount',  
            with: [
                'title' => $title,
                'userName' => $this->user->name, 
                'confirmationUrl' => $confirmationUrl,
                'email' => $this->user->email,
                'token' => $this->confirmationToken
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
