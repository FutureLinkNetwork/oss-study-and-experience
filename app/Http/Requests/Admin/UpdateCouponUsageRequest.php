<?php

namespace App\Http\Requests\Admin;

use App\Models\Beneficiary;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCouponUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $editableFrom = Carbon::today()->startOfMonth()->subMonth()->startOfDay()->toDateString();

        return [
            'used_at' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:'.$editableFrom],
            'amount' => ['required', 'integer', 'min:1'],
            'is_cancelled' => ['nullable', 'boolean'],
            'admin_correction_memo' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var VoucherUsage|null $usage */
            $usage = $this->route('voucherUsage');
            if (! $usage instanceof VoucherUsage) {
                return;
            }

            $newAmount = (int) $this->input('amount');
            $maxAmount = $this->calculateMaxAllowedAmount($usage);
            if ($maxAmount !== null && $newAmount > $maxAmount) {
                $validator->errors()->add(
                    'amount',
                    "利用可能残高を超えています。変更可能な最大金額は {$maxAmount} 円です。"
                );
            }
        });
    }

    /**
     * 当該利用を除いたうえでの「利用可能残高」+ 当該利用の現在の金額 = 変更可能な最大金額
     * 戻り値が null の場合はチェックをスキップ（Beneficiary がいない等）
     */
    private function calculateMaxAllowedAmount(VoucherUsage $usage): ?int
    {
        $user = $usage->user;
        if (! $user) {
            return null;
        }

        $beneficiary = Beneficiary::where('user_id', $user->id)->first();
        if (! $beneficiary) {
            return null;
        }

        $today = Carbon::today();
        $totalVoucherAmount = (int) Voucher::where('beneficiary_id', $beneficiary->id)
            ->where('expiry_date', '>=', $today)
            ->where('status', '!=', 'expired')
            ->sum('amount');

        $usedAmountExcludingThis = (int) VoucherUsage::where('user_id', $user->id)
            ->where('is_cancelled', false)
            ->where('id', '!=', $usage->id)
            ->sum('amount');

        $availableWithThis = $totalVoucherAmount - $usedAmountExcludingThis;

        return max(0, $availableWithThis);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $editableFrom = Carbon::today()->startOfMonth()->subMonth()->startOfDay();

        return [
            'used_at.after_or_equal' => '利用日は'.$editableFrom->format('Y年n月j日').'以降の日付を指定してください。',
            'admin_correction_memo.required' => '修正メモは必須です。',
        ];
    }
}
