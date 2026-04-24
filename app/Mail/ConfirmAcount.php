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

class ConfirmAcount extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $confirmationToken;

    /**
     * Create a new message instance.
     */
    public function __construct($confirmationToken, User $user,)
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
            subject: 'Confirme ton Email Address',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $title = 'Email Confirmation';
        $confirmationUrl = url('/api/users/email-confirm/' . $this->confirmationToken);
        return new Content(
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
