<?php

namespace App\Mail;

use App\Models\BusinessInfo;
use App\Models\Subdomain;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSummaryUpdatedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Subdomain $subdomain,
        public BusinessInfo $businessInfo,
        public string $targetMonthLabel,
        public string $fromAddress,
        public string $fromName
    ) {}

    public function envelope(): Envelope
    {
        $subject = '【'.$this->fromName.'】支払い明細が更新されました（'.$this->targetMonthLabel.'）';

        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.business.payment_summary_updated',
            with: [
                'systemName' => $this->fromName,
                'targetMonthLabel' => $this->targetMonthLabel,
            ]
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
