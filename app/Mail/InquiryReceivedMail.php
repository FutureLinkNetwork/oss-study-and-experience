<?php

namespace App\Mail;

use App\Enums\InquiryType;
use App\Models\Inquiry;
use App\Models\Subdomain;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Inquiry $inquiry,
        public Subdomain $subdomain
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->subdomain->system_name.' - 問い合わせを受け付けました';

        return new Envelope(
            from: new Address(config('mail.from.address'), $this->subdomain->system_name),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'emails.inquiry.received',
            with: [
                'inquiry' => $this->inquiry,
                'subdomain' => $this->subdomain,
                'inquiryTypeLabel' => $this->inquiry->inquiry_type === InquiryType::User ? '利用者' : '事業者',
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
        return [];
    }
}
