<?php

namespace App\Providers;

use App\Models\Subdomain;
use App\Support\UserCouponBalanceCalculator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // UIkit用のページネーションビューをデフォルトに設定
        Paginator::defaultView('vendor.pagination.uikit');
        Paginator::defaultSimpleView('vendor.pagination.simple-uikit');

        // 全ビューに$subdomain変数を共有
        View::composer('*', function ($view) {
            try {
                $request = request();
                if ($request) {
                    $host = $request->getHost();
                    $subdomainName = $this->extractSubdomainFromHost($host);

                    $subdomain = Subdomain::where('subdomain', $subdomainName)
                        ->where('is_active', true)
                        ->first();

                    $view->with('subdomain', $subdomain);

                    // 利用者（subdomain_user）の場合のみクーポン残高と有効期限を計算
                    if (Auth::check() && Auth::user()->role && Auth::user()->role->name === 'subdomain_user') {
                        $voucherInfo = $this->calculateAvailableBalanceAndExpiry(Auth::user(), $subdomain);
                        $view->with('voucherBalance', $voucherInfo['balance']);
                        $view->with('voucherExpiryDate', $voucherInfo['expiry_date']);
                    } else {
                        $view->with('voucherBalance', null);
                        $view->with('voucherExpiryDate', null);
                    }
                }
            } catch (\Exception $e) {
                // エラーが発生した場合はnullを設定
                $view->with('subdomain', null);
                $view->with('voucherBalance', null);
                $view->with('voucherExpiryDate', null);
            }
        });
    }

    /**
     * ホスト名からサブドメイン名を抽出
     */
    protected function extractSubdomainFromHost(string $host): string
    {
        // 例: demo.localhost -> demo
        $parts = explode('.', $host);

        // localhostの場合は特別処理
        if (in_array('localhost', $parts)) {
            return $parts[0] === 'localhost' ? 'www' : $parts[0];
        }

        // 通常のドメインの場合、最初の部分がサブドメイン
        return $parts[0];
    }

    /**
     * クーポン利用可能金額と有効期限を計算（会計年度内の発行分・当該年度の利用実績に基づく）
     *
     * @return array{balance: int, expiry_date: \Carbon\Carbon|null}
     */
    protected function calculateAvailableBalanceAndExpiry($user, $subdomain): array
    {
        if (! $subdomain) {
            return ['balance' => 0, 'expiry_date' => null];
        }

        $result = UserCouponBalanceCalculator::calculate($user);

        return [
            'balance' => $result['balance'],
            'expiry_date' => $result['expiry_date'],
        ];
    }
}
