<?php

namespace App\Console\Commands;

use App\Mail\PaymentSummaryUpdatedMail;
use App\Models\BusinessInfo;
use App\Models\BusinessPaymentDownload;
use App\Models\PaymentAggregate;
use App\Models\VoucherUsage;
use App\Services\DailyCouponCountNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AggregateBusinessPayments extends Command
{
    protected $signature = 'app:aggregate-business-payments {--skip-notification : 支払明細更新メールを送信しない}';

    protected $description = '前月分のクーポン利用を集計し、事業者・教室別の支払集計テーブルに保存する';

    public function __construct(
        protected DailyCouponCountNotificationService $mailFromService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetMonth = Carbon::today()->subMonth();
        $monthStart = $targetMonth->copy()->startOfMonth()->startOfDay();
        $monthEnd = $targetMonth->copy()->endOfMonth()->endOfDay();
        $targetMonthDate = $monthStart->copy()->toDateString();
        $targetMonthLabel = $targetMonth->format('Y年n月');

        $this->info("支払集計を開始します。対象月: {$targetMonthLabel}");

        $rows = VoucherUsage::query()
            ->where('is_cancelled', false)
            ->whereBetween('used_at', [$monthStart, $monthEnd])
            ->select([
                'subdomain_id',
                'business_info_id',
                'classroom_info_id',
                DB::raw('COUNT(*) as application_count'),
                DB::raw('SUM(amount) as total_amount'),
            ])
            ->groupBy('subdomain_id', 'business_info_id', 'classroom_info_id')
            ->get();

        $businessIds = $rows->pluck('business_info_id')->unique()->filter()->values()->all();
        $transferTargetByBusinessId = BusinessInfo::query()
            ->whereIn('id', $businessIds)
            ->pluck('is_public_funds_transfer_target', 'id')
            ->map(fn ($value) => (bool) $value)
            ->all();

        DB::transaction(function () use ($rows, $targetMonthDate, $monthStart, $transferTargetByBusinessId): void {
            PaymentAggregate::query()
                ->whereDate('target_month', $monthStart)
                ->delete();

            foreach ($rows as $row) {
                $applicationCount = (int) $row->getAttribute('application_count');
                $totalAmount = (int) $row->getAttribute('total_amount');
                $businessInfoId = (int) $row->business_info_id;
                PaymentAggregate::query()->updateOrInsert(
                    [
                        'target_month' => $targetMonthDate,
                        'subdomain_id' => $row->subdomain_id,
                        'business_info_id' => $businessInfoId,
                        'classroom_info_id' => $row->classroom_info_id,
                    ],
                    [
                        'application_count' => $applicationCount,
                        'total_amount' => $totalAmount,
                        'is_public_funds_transfer_target' => $transferTargetByBusinessId[$businessInfoId] ?? false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        $upserted = $rows->count();

        $this->info("支払集計を完了しました。更新件数: {$upserted}");

        $businessPairs = PaymentAggregate::query()
            ->whereDate('target_month', $monthStart)
            ->select('subdomain_id', 'business_info_id')
            ->distinct()
            ->get();

        $downloadRecordsCreated = 0;
        $emailsSent = 0;
        $skipNotification = $this->option('skip-notification');

        foreach ($businessPairs as $pair) {
            BusinessPaymentDownload::query()->updateOrCreate(
                [
                    'subdomain_id' => $pair->subdomain_id,
                    'business_info_id' => $pair->business_info_id,
                    'target_month' => $targetMonthDate,
                ],
                []
            );
            $downloadRecordsCreated++;

            if ($skipNotification) {
                continue;
            }

            $businessInfo = BusinessInfo::query()->with('subdomain')->find($pair->business_info_id);
            if (! $businessInfo || ! $businessInfo->subdomain) {
                Log::warning('支払明細更新メール送信スキップ: 事業者またはサブドメインが見つかりません', [
                    'business_info_id' => $pair->business_info_id,
                ]);

                continue;
            }

            $email = $businessInfo->email;
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('支払明細更新メール送信スキップ: メールアドレスが未設定または不正です', [
                    'business_info_id' => $businessInfo->id,
                ]);

                continue;
            }

            try {
                $fromAddress = $this->mailFromService->buildFromAddress($businessInfo->subdomain);
                $fromName = $businessInfo->subdomain->system_name ?? config('app.name');
                Mail::to($email)->send(new PaymentSummaryUpdatedMail(
                    $businessInfo->subdomain,
                    $businessInfo,
                    $targetMonthLabel,
                    $fromAddress,
                    $fromName
                ));
                $emailsSent++;
            } catch (\Throwable $e) {
                Log::error('支払明細更新メール送信エラー', [
                    'business_info_id' => $businessInfo->id,
                    'email' => $email,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $message = "支払ダウンロード管理レコード: {$downloadRecordsCreated} 件";
        $message .= $skipNotification
            ? '（通知メールは --skip-notification のため送信しませんでした）'
            : "、通知メール送信: {$emailsSent} 件";
        $this->info($message);

        return Command::SUCCESS;
    }
}
