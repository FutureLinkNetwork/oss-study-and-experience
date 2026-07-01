<?php

namespace App\Services;

/**
 * S3オブジェクトキーのプレフィックス生成。
 * APP_ENV=local の場合は "dev_subdomain_{id}"、それ以外は "subdomain_{id}"。
 */
class S3KeyPrefix
{
    public static function forSubdomain(int $subdomainId): string
    {
        $prefix = config('app.env') === 'local' ? 'dev_subdomain' : 'subdomain';

        return "{$prefix}_{$subdomainId}";
    }
}
