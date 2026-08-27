<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisqualificationNoticeMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public string $systemName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), $this->systemName),
            subject: $this->systemName.'にかかる資格要件の審査結果について',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.user.disqualification_notice',
            with: [
                'systemName' => $this->systemName,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
