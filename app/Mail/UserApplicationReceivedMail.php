<?php

namespace App\Mail;

use App\Models\Subdomain;
use App\Models\UserApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserApplication $userApplication,
        public Subdomain $subdomain,
        public string $contactUrl
    ) {}

    public function envelope(): Envelope
    {
        $systemName = $this->systemDisplayName();

        return new Envelope(
            from: new Address(config('mail.from.address'), $systemName),
            subject: '【'.$systemName.'】利用者申請を受け付けました',
        );
    }

    public function content(): Content
    {
        $createdAt = $this->userApplication->created_at;

        return new Content(
            text: 'emails.user.application_received',
            with: [
                'systemName' => $this->systemDisplayName(),
                'guardianName' => $this->userApplication->guardian_name,
                'childName' => $this->userApplication->child_name,
                'submittedAtLabel' => $createdAt !== null
                    ? $createdAt->timezone('Asia/Tokyo')->format('Y年n月j日 H:i')
                    : now()->timezone('Asia/Tokyo')->format('Y年n月j日 H:i'),
                'contactUrl' => $this->contactUrl,
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

    private function systemDisplayName(): string
    {
        $name = trim((string) $this->subdomain->system_name);

        return $name !== '' ? $name : (string) config('app.name');
    }
}
