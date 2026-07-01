<?php

namespace App\Mail;

use App\Models\Subdomain;
use App\Models\VoucherUsage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ImmediateCouponCancelledMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public VoucherUsage $voucherUsage,
        public Subdomain $subdomain,
        public string $fromAddress,
        public string $fromName
    ) {}

    public function envelope(): Envelope
    {
        $systemName = $this->subdomain->system_name ?? config('app.name');
        $subject = '【'.$systemName.'】クーポン申し込みがキャンセルされました';

        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $voucherUsage = $this->voucherUsage->loadMissing(['classroomInfo', 'courseInfo']);
        $classroomName = $voucherUsage->classroomInfo?->classroom_name ?? '';
        $courseName = $voucherUsage->course_info_id
            ? ($voucherUsage->courseInfo?->course_name ?? '')
            : '金額指定利用';
        $amount = $voucherUsage->amount;
        $usedAt = $voucherUsage->used_at?->format('Y年n月j日 H:i') ?? '';
        $cancelledAt = $voucherUsage->cancelled_at?->format('Y年n月j日 H:i') ?? '';

        return new Content(
            text: 'emails.business.immediate_coupon_cancelled',
            with: [
                'subdomain' => $this->subdomain,
                'classroomName' => $classroomName,
                'courseName' => $courseName,
                'amount' => $amount,
                'usedAt' => $usedAt,
                'cancelledAt' => $cancelledAt,
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
