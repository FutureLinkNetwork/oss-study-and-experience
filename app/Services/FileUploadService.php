<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * ファイルアップロード統一サービス
 */
class FileUploadService
{
    /**
     * 一時ファイルアップロード（確認画面用）
     */
    public function uploadTemporaryFile(
        UploadedFile $file, 
        string $fileType, 
        int $index = null
    ): array {
        try {
            // セッションIDを使用して一時的に保存
            $sessionId = session()->getId();
            
            // パスとファイル名の生成
            if ($index !== null) {
                // 教室画像など配列インデックスがある場合
                $tempPath = "temp/{$sessionId}/{$fileType}";
                $filename = "{$fileType}_{$index}_{$file->getClientOriginalName()}";
            } else {
                // 単一ファイルの場合
                $tempPath = "temp/{$sessionId}/{$fileType}";
                $filename = $file->getClientOriginalName();
            }
            
            Log::info('Starting temporary file upload', [
                'file_type' => $fileType,
                'index' => $index,
                'temp_path' => $tempPath,
                'filename' => $filename,
                'file_valid' => $file->isValid(),
                'file_size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName()
            ]);
            
            // Laravel Storage を使用してファイルを保存
            $path = $file->storeAs($tempPath, $filename, 'local');
            
            Log::info('storeAs result', [
                'file_type' => $fileType,
                'path_result' => $path,
                'path_is_false' => $path === false,
                'temp_path' => $tempPath,
                'filename' => $filename
            ]);
            
            if ($path === false) {
                Log::warning('storeAs failed, trying alternative method', [
                    'file_type' => $fileType,
                    'index' => $index,
                    'temp_path' => $tempPath,
                    'filename' => $filename,
                    'storage_path_exists' => is_dir(storage_path('app')),
                    'storage_path_writable' => is_writable(storage_path('app'))
                ]);
                
                // 代替手段: 手動でディレクトリ作成＋move_uploaded_file
                $storagePath = storage_path('app/' . $tempPath);
                if (!is_dir($storagePath)) {
                    mkdir($storagePath, 0755, true);
                }
                
                $destinationPath = $storagePath . '/' . $filename;
                
                if (move_uploaded_file($file->getPathname(), $destinationPath)) {
                    $path = $tempPath . '/' . $filename;
                    Log::info('Alternative upload method succeeded', [
                        'file_type' => $fileType,
                        'path' => $path
                    ]);
                } else {
                    throw new \Exception('ファイルの保存に失敗しました（storeAsとmove_uploaded_fileの両方が失敗）');
                }
            }
            
            $fullPath = storage_path('app/' . $path);
            
            // 保存結果の検証
            $fileExists = file_exists($fullPath);
            $fileSizeOnDisk = $fileExists ? filesize($fullPath) : 0;
            
            Log::info('Temporary file upload completed', [
                'file_type' => $fileType,
                'index' => $index,
                'temp_path' => $path,
                'full_path' => $fullPath,
                'file_exists' => $fileExists,
                'file_size_on_disk' => $fileSizeOnDisk,
                'original_size' => $file->getSize()
            ]);
            
            if (!$fileExists) {
                throw new \Exception('ファイルが正常に保存されませんでした');
            }
            
            if ($fileSizeOnDisk !== $file->getSize()) {
                Log::warning('File size mismatch', [
                    'expected' => $file->getSize(),
                    'actual' => $fileSizeOnDisk
                ]);
            }
            
            // 統一されたファイル情報を返す
            return [
                'original_name' => $file->getClientOriginalName(),
                'temp_path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'file_exists' => $fileExists
            ];
            
        } catch (\Exception $e) {
            Log::error('Temporary file upload failed', [
                'file_type' => $fileType,
                'index' => $index,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * セッションにファイル情報を保存
     */
    public function saveToSession(string $sessionKey, array $fileInfo): void
    {
        session([$sessionKey => $fileInfo]);
        
        // セッション保存の確認
        $savedData = session($sessionKey);
        Log::info('File info saved to session', [
            'session_key' => $sessionKey,
            'saved_successfully' => $savedData === $fileInfo
        ]);
    }
    
    /**
     * セッションからファイル情報を取得
     */
    public function getFromSession(string $sessionKey): ?array
    {
        return session($sessionKey);
    }
    
    /**
     * 一時ファイルを削除
     */
    public function deleteTempFile(string $tempPath): bool
    {
        try {
            $fullPath = storage_path('app/' . $tempPath);
            
            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info('Temporary file deleted', ['temp_path' => $tempPath]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete temporary file', [
                'temp_path' => $tempPath,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * セッションデータをクリア
     */
    public function clearSession(array $sessionKeys): void
    {
        session()->forget($sessionKeys);
        Log::info('Session data cleared', ['keys' => $sessionKeys]);
    }
}