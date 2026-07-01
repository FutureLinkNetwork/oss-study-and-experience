<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * 画像処理サービス（Laravel 12 + Intervention Image v3対応）
 */
readonly class ImageProcessingService
{
    public function __construct(
        private int $maxFileSize = 10 * 1024 * 1024, // 10MB
        private array $allowedMimes = ['image/jpeg', 'image/png'],
        private int $thumbnailSize = 150,
        private int $mediumSize = 500,
        private int $jpegQuality = 85
    ) {}

    /**
     * 教室画像をアップロードして複数サイズで保存
     */
    public function processClassroomImage(
        UploadedFile $file,
        int $classroomId,
        string $imageType = 'classrooms',
        int $subdomainId = 1
    ): array {
        // バリデーション
        $this->validateImage($file);

        // 画像マネージャーの初期化
        $manager = new ImageManager(new Driver);

        try {
            // オリジナル画像の処理
            $originalImage = $manager->read($file->getPathname());
            $timestamp = now()->format('YmdHis');
            $extension = $file->getClientOriginalExtension();

            // S3パスの生成（差し替え対応）
            $basePath = S3KeyPrefix::forSubdomain($subdomainId)."/classroom_images/{$classroomId}";
            $originalS3Key = "{$basePath}/original_{$timestamp}.{$extension}";
            $mediumS3Key = "{$basePath}/medium_{$timestamp}.jpg";
            $thumbnailS3Key = "{$basePath}/thumbnail_{$timestamp}.jpg";

            // オリジナルサイズをS3にアップロード
            $encoder = match ($extension) {
                'jpg', 'jpeg' => new JpegEncoder($this->jpegQuality),
                default => new AutoEncoder
            };

            // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
            $originalEnvKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? null;
            $originalEnvSecret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null;

            try {
                if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
                    unset($_ENV['AWS_ACCESS_KEY_ID']);
                    putenv('AWS_ACCESS_KEY_ID');
                }
                if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
                    unset($_ENV['AWS_SECRET_ACCESS_KEY']);
                    putenv('AWS_SECRET_ACCESS_KEY');
                }

                $putResult = Storage::disk('s3')->put(
                    $originalS3Key,
                    $originalImage->encode($encoder)->toString()
                );

                // 環境変数を元に戻す
                if ($originalEnvKey !== null) {
                    $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                    putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
                }
                if ($originalEnvSecret !== null) {
                    $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                    putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
                }

                if (! $putResult) {
                    throw new \Exception('S3へのアップロードが失敗しました。put()がfalseを返しました。');
                }
            } catch (\Exception $putException) {
                // 環境変数を元に戻す
                if (isset($originalEnvKey) && $originalEnvKey !== null) {
                    $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                    putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
                }
                if (isset($originalEnvSecret) && $originalEnvSecret !== null) {
                    $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                    putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
                }
                throw $putException;
            }

            // アップロード後の存在確認
            // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
            if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
                unset($_ENV['AWS_ACCESS_KEY_ID']);
                putenv('AWS_ACCESS_KEY_ID');
            }
            if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
                unset($_ENV['AWS_SECRET_ACCESS_KEY']);
                putenv('AWS_SECRET_ACCESS_KEY');
            }

            try {
                $existsAfterPut = Storage::disk('s3')->exists($originalS3Key);
            } finally {
                // 環境変数を元に戻す
                if ($originalEnvKey !== null) {
                    $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                    putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
                }
                if ($originalEnvSecret !== null) {
                    $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                    putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
                }
            }

            if (! $existsAfterPut) {
                throw new \Exception('S3へのアップロードが失敗しました。ファイルが存在しません。');
            }

            // 中サイズ画像の生成・アップロード
            $mediumImage = clone $originalImage;
            $mediumImage->scaleDown(width: $this->mediumSize);

            // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
            if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
                unset($_ENV['AWS_ACCESS_KEY_ID']);
                putenv('AWS_ACCESS_KEY_ID');
            }
            if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
                unset($_ENV['AWS_SECRET_ACCESS_KEY']);
                putenv('AWS_SECRET_ACCESS_KEY');
            }

            try {
                Storage::disk('s3')->put(
                    $mediumS3Key,
                    $mediumImage->encode(new JpegEncoder($this->jpegQuality))->toString()
                );
            } finally {
                // 環境変数を元に戻す
                if ($originalEnvKey !== null) {
                    $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                    putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
                }
                if ($originalEnvSecret !== null) {
                    $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                    putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
                }
            }

            // サムネイル画像の生成・アップロード
            $thumbnailImage = clone $originalImage;
            $thumbnailImage->cover($this->thumbnailSize, $this->thumbnailSize);

            // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
            if ($originalEnvKey === 'null' || $originalEnvKey === '"null"') {
                unset($_ENV['AWS_ACCESS_KEY_ID']);
                putenv('AWS_ACCESS_KEY_ID');
            }
            if ($originalEnvSecret === 'null' || $originalEnvSecret === '"null"') {
                unset($_ENV['AWS_SECRET_ACCESS_KEY']);
                putenv('AWS_SECRET_ACCESS_KEY');
            }

            try {
                Storage::disk('s3')->put(
                    $thumbnailS3Key,
                    $thumbnailImage->encode(new JpegEncoder($this->jpegQuality))->toString()
                );
            } finally {
                // 環境変数を元に戻す
                if ($originalEnvKey !== null) {
                    $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                    putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
                }
                if ($originalEnvSecret !== null) {
                    $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                    putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
                }
            }

            return [
                'classroom_image_original_filename' => $file->getClientOriginalName(),
                'classroom_image_s3_key' => $originalS3Key,
                'classroom_image_file_size' => $file->getSize(),
                'classroom_image_mime_type' => $file->getMimeType(),
                'classroom_image_medium_s3_key' => $mediumS3Key,
                'classroom_image_thumbnail_s3_key' => $thumbnailS3Key,
            ];

        } catch (\Exception $e) {
            Log::error('Classroom image processing failed', [
                'classroom_id' => $classroomId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('画像の処理中にエラーが発生しました: '.$e->getMessage());
        }
    }

    /**
     * 既存画像の削除
     */
    public function deleteClassroomImage(
        ?string $originalS3Key,
        ?string $mediumS3Key,
        ?string $thumbnailS3Key
    ): void {
        $keysToDelete = array_filter([
            $originalS3Key,
            $mediumS3Key,
            $thumbnailS3Key,
        ]);

        // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
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
            foreach ($keysToDelete as $key) {
                try {
                    if (Storage::disk('s3')->exists($key)) {
                        Storage::disk('s3')->delete($key);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete image from S3', [
                        's3_key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } finally {
            // 環境変数を元に戻す
            if ($originalEnvKey !== null) {
                $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
            }
            if ($originalEnvSecret !== null) {
                $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
            }
        }
    }

    /**
     * 画像バリデーション
     */
    private function validateImage(UploadedFile $file): void
    {
        // ファイルサイズチェック
        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException(
                '画像ファイルサイズが大きすぎます。'.
                round($this->maxFileSize / 1024 / 1024, 1).'MB以下のファイルをアップロードしてください。'
            );
        }

        // ファイル拡張子チェック（優先）
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException(
                '対応していないファイル拡張子です。.jpg、.jpeg、.png形式のファイルをアップロードしてください。'
            );
        }

        // MIMEタイプチェック（補助的）
        $mimeType = $file->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];

        // 拡張子がPNGの場合、MIMEタイプがapplication/octet-streamでも許可
        if ($extension === 'png' && $mimeType === 'application/octet-stream') {
            return; // PNG形式として扱う
        }

        if (! in_array($mimeType, $allowedMimes)) {
            throw new \InvalidArgumentException(
                '対応していない画像形式です。JPEG、PNG形式のファイルをアップロードしてください。MIMEタイプ: '.$mimeType
            );
        }

        // 画像として有効かチェック
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('有効な画像ファイルではありません。');
        }
    }

    /**
     * S3から画像を取得
     */
    public function getImageFromS3(string $s3Key): string
    {
        // 環境変数が"null"という文字列の場合、一時的にunsetしてAWS SDKが環境変数を参照しないようにする
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
            if (! Storage::disk('s3')->exists($s3Key)) {
                throw new \Exception('画像ファイルが見つかりません。');
            }

            $fileContent = Storage::disk('s3')->get($s3Key);
        } finally {
            // 環境変数を元に戻す
            if ($originalEnvKey !== null) {
                $_ENV['AWS_ACCESS_KEY_ID'] = $originalEnvKey;
                putenv('AWS_ACCESS_KEY_ID='.$originalEnvKey);
            }
            if ($originalEnvSecret !== null) {
                $_ENV['AWS_SECRET_ACCESS_KEY'] = $originalEnvSecret;
                putenv('AWS_SECRET_ACCESS_KEY='.$originalEnvSecret);
            }
        }

        return $fileContent;
    }

    /**
     * MIMEタイプを推測
     */
    public function guessMimeTypeFromS3Key(string $s3Key): string
    {
        $extension = strtolower(pathinfo($s3Key, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg'
        };
    }
}
