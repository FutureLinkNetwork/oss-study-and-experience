<?php

namespace App\Services;

use App\Models\Subdomain;
use Illuminate\Http\Request;

class SubdomainService
{
    /**
     * 現在のサブドメインを取得
     */
    public function getCurrentSubdomain(Request $request): Subdomain
    {
        $host = $request->getHost();
        $subdomainName = $this->extractSubdomainFromHost($host);

        return Subdomain::where('subdomain', $subdomainName)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * 現在のサブドメイン ID を取得
     */
    public function currentSubdomainId(Request $request): int
    {
        return (int) $this->getCurrentSubdomain($request)->id;
    }

    /**
     * 対象モデルが現在ホストのサブドメインに属するか確認する
     *
     * @param  object{subdomain_id?: int|string|null}  $model
     */
    public function ensureBelongsToCurrentSubdomain(
        Request $request,
        object $model,
        int $abortStatus = 403,
        string $message = 'アクセス権限がありません。'
    ): void {
        $current = $this->getCurrentSubdomain($request);

        if (! isset($model->subdomain_id) || (int) $model->subdomain_id !== (int) $current->id) {
            abort($abortStatus, $message);
        }
    }

    /**
     * ホスト名からサブドメイン名を抽出
     */
    public function extractSubdomainFromHost(string $host): string
    {
        // 例: demo.localhost -> demo
        $parts = explode('.', $host);

        // localhostの場合は特別処理
        if (in_array('localhost', $parts)) {
            return $parts[0] === 'localhost' ? 'www' : $parts[0];
        }

        // 通常のドメインの場合、最初の部分がサブドメイン
        return $parts[0] ?? 'www';
    }

    /**
     * サブドメインが存在し、有効かチェック
     */
    public function isValidSubdomain(string $subdomainName): bool
    {
        return Subdomain::where('subdomain', $subdomainName)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * 利用可能な全サブドメインを取得
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Subdomain>
     */
    public function getAllActiveSubdomains()
    {
        return Subdomain::where('is_active', true)->get();
    }
}
