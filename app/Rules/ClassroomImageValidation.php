<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ClassroomImageValidation implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 値がnullまたは空の場合は通す（nullable対応）
        if (is_null($value) || $value === '') {
            return;
        }

        // セッションからの復元時（確認画面から最終登録時）の特別処理
        // この場合、$valueは通常のUploadedFileではないため、特別な処理が必要
        if (!$value instanceof UploadedFile) {
            // セッションに画像情報があるかチェック
            $uploadedImages = session('uploaded_classroom_images', []);
            
            // 属性からインデックスを抽出 (例: classrooms.0.classroom_image -> 0)
            if (preg_match('/classrooms\.(\d+)\.classroom_image/', $attribute, $matches)) {
                $index = (int)$matches[1];
                
                // セッションに対応するインデックスの画像があれば OK
                if (isset($uploadedImages[$index]) && 
                    isset($uploadedImages[$index]['temp_path']) && 
                    file_exists(storage_path('app/' . $uploadedImages[$index]['temp_path']))) {
                    return; // セッションに有効な画像ファイルが存在する
                }
            }
            
            $fail('教室画像は有効なファイルをアップロードしてください。');
            return;
        }

        // ファイルが正常にアップロードされているかチェック
        if (!$value->isValid()) {
            $fail('教室画像のアップロードに失敗しました。');
            return;
        }

        // ファイルサイズチェック（10MB）
        if ($value->getSize() > 10 * 1024 * 1024) {
            $fail('教室画像は10MB以下のファイルをアップロードしてください。');
            return;
        }

        // 拡張子チェック
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $extension = strtolower($value->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            $fail('教室画像はJPEG、PNG形式のファイルをアップロードしてください。');
            return;
        }

        // MIMEタイプチェック（PNGの特別処理含む）
        $mimeType = $value->getMimeType();
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
        
        // PNGファイルが application/octet-stream として認識される場合を許可
        if ($extension === 'png' && $mimeType === 'application/octet-stream') {
            return;
        }
        
        if (!in_array($mimeType, $allowedMimes)) {
            $fail('教室画像は有効な画像ファイル（JPEG、PNG）をアップロードしてください。');
            return;
        }

        // 実際に画像ファイルかチェック
        $imageInfo = @getimagesize($value->getPathname());
        if ($imageInfo === false) {
            $fail('教室画像は有効な画像ファイルをアップロードしてください。');
            return;
        }
    }
}
