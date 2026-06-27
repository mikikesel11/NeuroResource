<?php

namespace App\Domains\Resources\Mail;

use App\Domains\Resources\Models\ResourceUnlock;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Transactional confirmation — sent synchronously so it arrives immediately.
class ConfirmResourceUnlock extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ResourceUnlock $unlock) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your Email to unlock your Resource');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.resources.confirm-unlock',
            with: [
                'resourceTitle' => $this->unlock->resource->title,
                'confirmUrl' => route('resources.confirm', $this->unlock->token),
            ],
        );
    }
}
