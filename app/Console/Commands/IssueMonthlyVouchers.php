<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\Subdomain;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueMonthlyVouchers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:issue-monthly-vouchers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '月次クーポンを発行する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = Carbon::today();
        $dayOfMonth = (int) $today->format('d');

        $this->info("月次クーポン発行バッチ処理を開始します。基準日: {$today->format('Y-m-d')} (日付: {$dayOfMonth})");

        // 実行日の日付がvoucher_publish_dateと一致し、かつvoucher_amount、voucher_expiryが設定されているサブドメインを取得
        $targetSubdomains = Subdomain::query()
            ->where('voucher_publish_date', $dayOfMonth)
            ->whereNotNull('voucher_amount')
            ->whereNotNull('voucher_expiry')
            ->get();

        if ($targetSubdomains->isEmpty()) {
            $this->info('対象のサブドメインはありません。');

            return Command::SUCCESS;
        }

        $this->info("対象サブドメイン数: {$targetSubdomains->count()}件");

        $totalVouchersIssued = 0;
        $totalErrors = 0;

        foreach ($targetSubdomains as $subdomain) {
            $this->info("サブドメイン: {$subdomain->name} (ID: {$subdomain->id}) を処理中...");

            // ステータスが「資格喪失」「決定通知書未送信」「決定通知書送信待ち」「決定通知書送信失敗」でない利用者を取得
            $beneficiaries = Beneficiary::query()
                ->where('subdomain_id', $subdomain->id)
                ->where('status', '!=', '資格喪失')
                ->where('status', '!=', '決定通知書未送信')
                ->where('status', '!=', '決定通知書送信待ち')
                ->where('status', '!=', '決定通知書送信失敗')
                ->get();

            if ($beneficiaries->isEmpty()) {
                $this->info('  対象の利用者がいません。');

                continue;
            }

            $this->info("  対象利用者数: {$beneficiaries->count()}件");

            $vouchersIssued = 0;
            $errors = 0;

            foreach ($beneficiaries as $beneficiary) {
                try {
                    DB::transaction(function () use ($beneficiary, $subdomain, $today, &$vouchersIssued) {
                        // 有効期限を計算
                        if ($subdomain->voucher_expiry === 0) {
                            // voucher_expiryが0の場合は年度末（3月31日）を設定
                            // 年度は4月1日から3月31日まで
                            // 発行日が4月以降ならその年度の年度末（翌年の3月31日）、1-3月ならその年度の年度末（その年の3月31日）
                            if ($today->month >= 4) {
                                // 4月以降：その年度の年度末は翌年の3月31日
                                $expiryDate = Carbon::create($today->year + 1, 3, 31);
                            } else {
                                // 1-3月：その年度の年度末はその年の3月31日
                                $expiryDate = Carbon::create($today->year, 3, 31);
                            }
                        } else {
                            // 従来通り、発行日からvoucher_expiryヶ月後
                            $expiryDate = $today->copy()->addMonths($subdomain->voucher_expiry);
                        }

                        // クーポンを作成
                        Voucher::create([
                            'beneficiary_id' => $beneficiary->id,
                            'subdomain_id' => $subdomain->id,
                            'voucher_number' => Str::uuid()->toString(),
                            'issue_date' => $today,
                            'expiry_date' => $expiryDate,
                            'amount' => $subdomain->voucher_amount,
                            'status' => 'unused',
                        ]);

                        $vouchersIssued++;
                    });
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("  利用者ID {$beneficiary->id} のクーポン発行中にエラーが発生しました: {$e->getMessage()}");
                }
            }

            $this->info("  処理完了: 発行 {$vouchersIssued}件, エラー {$errors}件");
            $totalVouchersIssued += $vouchersIssued;
            $totalErrors += $errors;
        }

        $this->info("全体処理完了: 合計発行 {$totalVouchersIssued}件, 合計エラー {$totalErrors}件");

        return $totalErrors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
