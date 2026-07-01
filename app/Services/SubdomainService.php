<?php

namespace App\Services;

use App\Models\Subdomain;
use Illuminate\Http\Request;

class SubdomainService
{
    /**
     * 現在のサブドメインを取得
     * 
     * @param Request $request
     * @return Subdomain
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
     * ホスト名からサブドメイン名を抽出
     * 
     * @param string $host
     * @return string
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
     * 
     * @param string $subdomainName
     * @return bool
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
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActiveSubdomains()
    {
        return Subdomain::where('is_active', true)->get();
    }
}