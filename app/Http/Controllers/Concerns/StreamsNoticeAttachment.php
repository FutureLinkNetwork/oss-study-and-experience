<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Notice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait StreamsNoticeAttachment
{
    /**
     * S3からお知らせ添付を取得してストリームダウンロードレスポンスを返す
     */
    protected function streamNoticeAttachment(Notice $notice): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $originalEnvKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
        $originalEnvSecret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;
        if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
            unset($_ENV['AWS_ACCESS_KEY_ID']);
            putenv('AWS_ACCESS_KEY_ID');
        }
        if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
            unset($_ENV['AWS_SECRET_ACCESS_KEY']);
            putenv('AWS_SECRET_ACCESS_KEY');
        }

        try {
            $fileExists = Storage::disk('s3')->exists($notice->attachment_s3_key);
        } catch (\Exception $e) {
            Log::error('S3 existence check failed (notice attachment)', [
                's3_key' => $notice->attachment_s3_key,
                'error' => $e->getMessage(),
            ]);
            $fileExists = false;
        } finally {
            if ($originalEnvKey !== null) {
                $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
            }
            if ($originalEnvSecret !== null) {
                $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
            }
        }

        if (! $fileExists) {
            abort(404, 'ファイルが見つかりません。');
        }

        if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
            unset($_ENV['AWS_ACCESS_KEY_ID']);
            putenv('AWS_ACCESS_KEY_ID');
        }
        if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
            unset($_ENV['AWS_SECRET_ACCESS_KEY']);
            putenv('AWS_SECRET_ACCESS_KEY');
        }

        try {
            $fileContent = Storage::disk('s3')->get($notice->attachment_s3_key);
        } catch (\Exception $e) {
            Log::error('S3 file get failed (notice attachment)', [
                's3_key' => $notice->attachment_s3_key,
                'error' => $e->getMessage(),
            ]);
            abort(404, 'ファイルの取得に失敗しました。');
        } finally {
            if ($originalEnvKey !== null) {
                $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
            }
            if ($originalEnvSecret !== null) {
                $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
            }
        }

        $filename = $notice->attachment_original_filename ?? 'attachment';
        $mimeType = $notice->getAttachmentMimeType();

        return response()->streamDownload(
            function () use ($fileContent) {
                echo $fileContent;
            },
            $filename,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }
}
