<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaV3 implements ValidationRule
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('recaptcha.enabled')) {
            return;
        }

        $token = is_string($value) ? trim($value) : '';
        if ($token === '') {
            $fail('確認に失敗しました。再度お試しください。');

            return;
        }

        $response = Http::asForm()->post(self::VERIFY_URL, [
            'secret' => config('recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => request()->ip(),
        ]);

        if (! $response->successful()) {
            $fail('確認に失敗しました。再度お試しください。');

            return;
        }

        $body = $response->json();
        if (empty($body['success'])) {
            $fail('確認に失敗しました。再度お試しください。');

            return;
        }

        $minScore = (float) config('recaptcha.min_score', 0.5);
        $score = isset($body['score']) ? (float) $body['score'] : 0.0;
        if ($score < $minScore) {
            $fail('確認に失敗しました。再度お試しください。');

            return;
        }
    }
}
