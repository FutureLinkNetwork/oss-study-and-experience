<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessInfo;
use App\Models\ClassroomInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ClassroomController extends Controller
{
    /**
     * 教室一覧を表示
     */
    public function index()
    {
        // ログインユーザーの事業者情報を取得
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return redirect()->route('business.dashboard')
                ->with('error', '事業者情報が見つかりません。');
        }

        // 事業者に属する教室一覧を取得
        $classrooms = $businessInfo->classrooms()
            ->with('lessonCategoryInfo')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('business.classrooms.index', compact('businessInfo', 'classrooms'));
    }

    /**
     * 教室詳細・編集画面を表示
     */
    public function show(ClassroomInfo $classroom)
    {
        // 権限チェック（自分の事業者の教室のみアクセス可能）
        if (! $this->canAccessClassroom($classroom)) {
            abort(403, 'この教室にアクセスする権限がありません。');
        }

        // 習い事種別のリレーションを読み込み
        $classroom->load(['lessonCategoryInfo', 'businessInfo']);

        // 事業者情報を取得
        $businessInfo = $classroom->businessInfo;

        // 利用者向けURLを生成（現在のリクエストからドメインを取得）
        $userCourseUrl = url('/user/course/'.$classroom->id);
        // 教室のqr_onlyが有効な場合は?qr=1パラメータを追加
        if ($classroom->qr_only) {
            $userCourseUrl .= '?qr=1';
        }

        // QRコードをSVG形式で生成
        $qrCodeSvg = QrCode::size(200)->generate($userCourseUrl);

        // 印刷用QRコードをSVG形式で生成
        $qrCodeSvgForPrint = QrCode::size(400)->generate($userCourseUrl);

        return view('business.classrooms.show', compact('classroom', 'userCourseUrl', 'qrCodeSvg', 'qrCodeSvgForPrint', 'businessInfo'));
    }

    /**
     * 教室情報を更新
     */
    public function update(Request $request, ClassroomInfo $classroom)
    {
        // 権限チェック
        if (! $this->canAccessClassroom($classroom)) {
            abort(403, 'この教室を編集する権限がありません。');
        }

        // バリデーション（classroom_introductionとis_activeのみ編集可能）
        $validated = $request->validate([
            'classroom_introduction' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
            'disallow_amount_specified_usage' => 'nullable|boolean',
            'qr_only' => 'nullable|boolean',
        ]);

        $validated['disallow_amount_specified_usage'] = $request->boolean('disallow_amount_specified_usage');
        $validated['qr_only'] = $request->boolean('qr_only');

        // 更新ユーザーを設定
        $validated['updated_user'] = Auth::id();

        // 教室情報を更新
        $classroom->update($validated);

        return redirect()
            ->route('business.classrooms.show', $classroom)
            ->with('success', '教室情報を更新しました。');
    }

    /**
     * ログインユーザーの事業者情報を取得
     */
    private function getUserBusinessInfo(): ?BusinessInfo
    {
        return BusinessInfo::where('user_id', Auth::id())
            // ->where('is_active', true)
            ->first();
    }

    /**
     * 指定された教室にアクセス可能かチェック
     */
    private function canAccessClassroom(ClassroomInfo $classroom): bool
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return false;
        }

        return $classroom->business_info_id === $businessInfo->id;
    }

    /**
     * 教室画像をダウンロード
     */
    public function downloadImage(ClassroomInfo $classroom, string $size)
    {
        // 権限チェック（自分の事業者の教室のみアクセス可能）
        if (! $this->canAccessClassroom($classroom)) {
            abort(403, 'この画像にアクセスする権限がありません。');
        }

        // 画像が存在するかを確認
        if (! $classroom->hasClassroomImage()) {
            abort(404, '教室画像が見つかりません。');
        }

        // サイズの検証
        $validSizes = ['original', 'medium', 'thumbnail'];
        if (! in_array($size, $validSizes)) {
            abort(404, '指定された画像サイズが見つかりません。');
        }

        try {
            // S3キーを取得
            $s3Key = $classroom->getImageS3Key($size);

            if (empty($s3Key)) {
                abort(404, '画像ファイルが見つかりません。');
            }

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

            // S3からファイルを取得
            try {
                $fileExists = Storage::disk('s3')->exists($s3Key);
            } catch (\Exception $e) {
                Log::error('S3 existence check failed', [
                    's3_key' => $s3Key,
                    'error' => $e->getMessage(),
                ]);
                $fileExists = false;
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

            if (! $fileExists) {
                abort(404, 'ファイルが見つかりません。');
            }

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
                $fileContent = Storage::disk('s3')->get($s3Key);
            } catch (\Exception $e) {
                Log::error('S3 file get failed', [
                    's3_key' => $s3Key,
                    'error' => $e->getMessage(),
                ]);
                abort(404, 'ファイルの取得に失敗しました。');
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

            $mimeType = $classroom->classroom_image_mime_type ?? 'image/jpeg';

            // ファイル名を生成
            $extension = match ($mimeType) {
                'image/jpeg' => '.jpg',
                'image/png' => '.png',
                default => '.jpg'
            };

            $filename = $classroom->classroom_name.'_'.$size.$extension;

            // ログ記録
            Log::info('Classroom image downloaded (business)', [
                'classroom_id' => $classroom->id,
                'size' => $size,
                'user_id' => Auth::id(),
                'filename' => $filename,
            ]);

            // ファイルをダウンロード
            return response()->streamDownload(
                function () use ($fileContent) {
                    echo $fileContent;
                },
                $filename,
                [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'private, max-age=3600',
                ]
            );

        } catch (\Exception $e) {
            Log::error('Classroom image download failed (business)', [
                'classroom_id' => $classroom->id,
                'size' => $size,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, '画像のダウンロードに失敗しました。');
        }
    }
}
