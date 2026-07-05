<?php

declare(strict_types=1);

namespace App\Domains\Resources\Mail;

use App\Domains\Resources\Models\ResourceUnlock;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

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
                'confirmUrl' => $this->confirmUrl(),
            ],
        );
    }

    /**
     * A signed, expiring confirmation link. Signing means a forwarded/archived
     * link can't be tampered with, and the TTL means it can't unlock forever.
     */
    private function confirmUrl(): string
    {
        $ttlHours = (int) config('neuroresource.unlock_link_ttl_hours', 24);

        return URL::temporarySignedRoute(
            'resources.confirm',
            now()->addHours($ttlHours),
            ['token' => $this->unlock->token],
        );
    }
}
