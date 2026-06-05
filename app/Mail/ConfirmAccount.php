<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmAccount extends Mailable  // Changé: ConfirmAcount -> ConfirmAccount
{
    use Queueable, SerializesModels;

    public $user;
    public $confirmationToken;

    public function __construct($confirmationToken, User $user)
    {
        $this->confirmationToken = $confirmationToken;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirme ton adresse Email',
        );
    }

    public function content(): Content
    {
        $title = 'Email Confirmation';
        $backendUrl = config('app.url', 'http://localhost:8000');

        // Correction: Créez l'URL correctement
        $confirmationUrl = $backendUrl . "/api/auth/email-confirm/" . urlencode($this->user->email) . "?token=" . $this->confirmationToken;

        return new Content(
            view: 'users.confirm-account', 
            with: [
                'title' => $title,
                'userName' => $this->user->name,
                'confirmationUrl' => $confirmationUrl,
                'email' => $this->user->email,
                'token' => $this->confirmationToken
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
