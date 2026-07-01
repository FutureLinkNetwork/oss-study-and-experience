<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Subdomain;
use App\Services\S3KeyPrefix;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NoticeController extends Controller
{
    use \App\Http\Controllers\Concerns\StreamsNoticeAttachment;

    /**
     * お知らせ一覧表示
     */
    public function index(Request $request): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        // ユーザーとロール情報を取得
        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。権限レベルが不足しています。');
        }
        $query = Notice::with(['subdomain', 'creator'])
            ->notDeleted()
            ->orderBy('id', 'desc');

        // 自分のサブドメインのみ
        $query->forSubdomain($user->subdomain_id);

        // 検索フィルター
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('subdomain_id')) {
            $query->forSubdomain($request->get('subdomain_id'));
        }

        $notices = $query->paginate(20);

        // システム管理者の場合はサブドメイン一覧も取得
        $subdomains = $role->level >= 100 ? Subdomain::all() : collect();

        return view('admin.notices.index', compact('notices', 'subdomains'));
    }

    /**
     * お知らせ作成フォーム表示
     */
    public function create(): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。');
        }

        // 現在のサブドメインの情報を取得
        $subdomain = json_decode(collect([$user->subdomain]), true);
        $latitude = $subdomain[0]['latitude'] ?? null;
        $longitude = $subdomain[0]['longitude'] ?? null;

        return view('admin.notices.form', [
            'notice' => null,
            'subdomain_id' => $user->subdomain_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'pageTitle' => 'お知らせ作成',
            'formAction' => route('admin.notices.store'),
            'isEdit' => false,
            'isShow' => false,
        ]);
    }

    /**
     * お知らせ作成処理
     */
    public function store(Request $request): RedirectResponse
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。');
        }

        $validated = $request->validate([
            'subdomain_id' => [
                'required',
                'exists:subdomains,id',
                function ($attribute, $value, $fail) use ($user, $role) {
                    // サブドメイン管理者・オペレーターは自分のサブドメインのみ
                    if ($role->level < 100 && $value != $user->subdomain_id) {
                        $fail('指定されたサブドメインは選択できません。');
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notice_date' => 'required|date',
            'publish_start_at' => 'nullable|date',
            'publish_end_at' => 'nullable|date|after_or_equal:publish_start_at',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'link_url' => 'nullable|url|max:1000',
            'show_on_public' => 'boolean',
            'show_on_user_dashboard' => 'boolean',
            'show_on_business_dashboard' => 'boolean',
            'attachment' => [
                'nullable',
                'file',
                'max:8192',
                'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel',
            ],
        ], [
            'attachment.max' => '添付ファイルは8MBまでです。',
            'attachment.mimetypes' => '添付はPDF、Word（.doc/.docx）、Excel（.xls/.xlsx）のみ対応しています。',
        ]);

        $validated['created_user'] = $user->id;
        $validated['updated_user'] = $user->id;

        $notice = Notice::create($validated);

        if ($request->hasFile('attachment')) {
            $this->uploadNoticeAttachment($notice, $request->file('attachment'));
        }

        return redirect()->route('admin.notices.index')
            ->with('success', 'お知らせを作成しました。');
    }

    /**
     * お知らせ編集フォーム表示
     */
    public function edit(Notice $notice): View
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。');
        }

        // アクセス権限チェック
        if ($role->level < 100 && $notice->subdomain_id !== $user->subdomain_id) {
            abort(403, 'アクセス権限がありません。');
        }

        // 現在のサブドメインの情報を取得
        $subdomain = json_decode(collect([$user->subdomain]), true);

        // 緯度軽度が空の場合はサブドメインの値をセット
        if ($notice->latitude && $notice->longitude) {
            $latitude = $notice->latitude;
            $longitude = $notice->longitude;
        } else {
            $latitude = $subdomain[0]['latitude'];
            $longitude = $subdomain[0]['longitude'];
        }

        return view('admin.notices.form', [
            'notice' => $notice,
            'subdomain_id' => $user->subdomain_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'pageTitle' => 'お知らせ編集',
            'formAction' => route('admin.notices.update', $notice),
            'isEdit' => true,
            'isShow' => false,
        ]);
    }

    /**
     * お知らせ更新処理
     */
    public function update(Request $request, Notice $notice): RedirectResponse
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。');
        }

        // アクセス権限チェック
        if ($role->level < 100 && $notice->subdomain_id !== $user->subdomain_id) {
            abort(403, 'アクセス権限がありません。');
        }

        try {
            $validated = $request->validate([
                'subdomain_id' => [
                    'required',
                    'exists:subdomains,id',
                    function ($attribute, $value, $fail) use ($user, $role) {
                        // サブドメイン管理者・オペレーターは自分のサブドメインのみ
                        if ($role->level < 100 && $value != $user->subdomain_id) {
                            $fail('指定されたサブドメインは選択できません。');
                        }
                    },
                ],
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'notice_date' => 'required|date',
                'publish_start_at' => 'nullable|date',
                'publish_end_at' => 'nullable|date|after_or_equal:publish_start_at',
                'address' => 'nullable|string|max:500',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'link_url' => 'nullable|url|max:1000',
                'show_on_public' => 'boolean',
                'show_on_user_dashboard' => 'boolean',
                'show_on_business_dashboard' => 'boolean',
                'attachment' => [
                    'nullable',
                    'file',
                    'max:8192',
                    'mimetypes:application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel',
                ],
            ], [
                'attachment.max' => '添付ファイルは8MBまでです。',
                'attachment.mimetypes' => '添付はPDF、Word（.doc/.docx）、Excel（.xls/.xlsx）のみ対応しています。',
            ]);

            // show_on_publicのチェックが外れた場合はデータが来ないためアップデート対象ではなくなってしまうので対処
            if (! $request->has('show_on_public')) {
                $validated['show_on_public'] = false;
            }
            if (! $request->has('show_on_user_dashboard')) {
                $validated['show_on_user_dashboard'] = false;
            }
            if (! $request->has('show_on_business_dashboard')) {
                $validated['show_on_business_dashboard'] = false;
            }

            $validated['updated_user'] = $user->id;

            // 添付削除または差し替え: 既存S3オブジェクトを削除
            if ($request->boolean('attachment_remove') || $request->hasFile('attachment')) {
                $this->deleteNoticeAttachmentFromS3($notice);
            }

            $notice->update($validated);

            if ($request->hasFile('attachment')) {
                $this->uploadNoticeAttachment($notice, $request->file('attachment'));
            } elseif ($request->boolean('attachment_remove')) {
                $notice->update([
                    'attachment_s3_key' => null,
                    'attachment_original_filename' => null,
                    'attachment_file_size' => null,
                    'attachment_mime_type' => null,
                ]);
            }

            return redirect()->route('admin.notices.index')
                ->with('success', 'お知らせを更新しました。');

        } catch (\Exception $e) {
            Log::error('Notice Update Failed', [
                'notice_id' => $notice->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors(['error' => 'お知らせの更新に失敗しました: '.$e->getMessage()]);
        }
    }

    /**
     * お知らせ削除処理（論理削除）
     */
    public function destroy(Notice $notice): RedirectResponse
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。');
        }

        // アクセス権限チェック
        if ($role->level < 100 && $notice->subdomain_id !== $user->subdomain_id) {
            abort(403, 'アクセス権限がありません。');
        }

        $notice->update([
            'is_deleted' => true,
            'updated_user' => $user->id,
        ]);

        return redirect()->route('admin.notices.index')
            ->with('success', 'お知らせを削除しました。');
    }

    /**
     * 住所から座標を取得するAPI（Nominatim）
     */
    public function geocode(Request $request)
    {
        // 認証チェック
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;

        // レベル40以上のみアクセス可能
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。');
        }
        $request->validate([
            'address' => 'required|string|max:500',
        ]);

        $address = $request->get('address');

        // 日本の住所検索を改善するために複数の検索方法を試す
        $searchQueries = [
            $address, // 元の住所
            $address.', Japan', // 日本を明示的に追加
            str_replace(['県', '市', '町', '村'], [' ', ' ', ' ', ' '], $address).', Japan', // 県市町村の後にスペースを追加
        ];

        $client = new Client([
            'timeout' => 10,
            'verify' => false, // SSL証明書の検証を無効化（開発環境用）
        ]);

        // まず複数のNominatim検索を試す
        foreach ($searchQueries as $query) {
            try {
                // Nominatim APIを使用して座標を取得
                $url = 'https://nominatim.openstreetmap.org/search';
                $params = [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 3,
                    'addressdetails' => 1,
                    'countrycodes' => 'jp',
                    'accept-language' => 'ja,en',
                ];

                $response = $client->get($url, [
                    'query' => $params,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (compatible; StudyAndExperienceSystem/1.0)',
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 15,
                ]);

                $data = json_decode($response->getBody(), true);

                Log::info('Nominatim API Response', [
                    'original_address' => $address,
                    'search_query' => $query,
                    'response_code' => $response->getStatusCode(),
                    'response_count' => count($data ?? []),
                    'response_sample' => $data ? array_slice($data, 0, 1) : null,
                ]);

                if (! empty($data)) {
                    $result = $data[0];

                    return response()->json([
                        'success' => true,
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'display_name' => $result['display_name'],
                        'used_query' => $query,
                        'source' => 'nominatim',
                    ]);
                }

                sleep(1); // API制限対策で1秒待機

            } catch (\Exception $e) {
                Log::error('Nominatim API Error', [
                    'original_address' => $address,
                    'search_query' => $query,
                    'error' => $e->getMessage(),
                    'response_code' => $e->getCode(),
                ]);

                continue;
            }
        }

        // Nominatimで見つからない場合は、代替手段を試す
        // より簡単な形式で再検索
        $simpleQueries = [
            // 市区町村レベルで検索
            preg_replace('/[0-9\-\s]+/', '', $address), // 番地を除去
            // 県市のみで検索
            preg_replace('/[町村区][0-9\-\s].*/', '', $address),
        ];

        foreach ($simpleQueries as $simpleQuery) {
            if (strlen($simpleQuery) < 3) {
                continue;
            } // 短すぎる場合はスキップ

            try {
                $url = 'https://nominatim.openstreetmap.org/search';
                $params = [
                    'q' => $simpleQuery.', Japan',
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'jp',
                ];

                $response = $client->get($url, [
                    'query' => $params,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (compatible; StudyAndExperienceSystem/1.0)',
                    ],
                    'timeout' => 10,
                ]);

                $data = json_decode($response->getBody(), true);

                if (! empty($data)) {
                    $result = $data[0];

                    return response()->json([
                        'success' => true,
                        'latitude' => (float) $result['lat'],
                        'longitude' => (float) $result['lon'],
                        'display_name' => $result['display_name'],
                        'used_query' => $simpleQuery,
                        'source' => 'nominatim_simple',
                        'note' => '完全一致しませんでしたが、近似の位置を取得しました。',
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Simple Nominatim API Error', [
                    'query' => $simpleQuery,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        // 最後の手段として、固定座標を返す（船橋市の中心部）
        if (str_contains($address, '船橋') || str_contains($address, 'ふなばし')) {
            return response()->json([
                'success' => true,
                'latitude' => 35.6948,
                'longitude' => 139.9823,
                'display_name' => '船橋市（おおよその位置）',
                'source' => 'fallback',
                'note' => '正確な住所が見つからなかったため、船橋市の中心部を表示しています。',
            ]);
        }

        // 千葉県の場合
        if (str_contains($address, '千葉') || str_contains($address, 'ちば')) {
            return response()->json([
                'success' => true,
                'latitude' => 35.6074,
                'longitude' => 140.1065,
                'display_name' => '千葉県（おおよその位置）',
                'source' => 'fallback',
                'note' => '正確な住所が見つからなかったため、千葉県の中心部を表示しています。',
            ]);
        }

        // すべての検索方法で結果が見つからなかった場合
        return response()->json([
            'success' => false,
            'message' => '住所が見つかりませんでした。より具体的な住所（市区町村名を含む）を入力してください。',
            'suggestions' => [
                '例: 東京都新宿区西新宿2-8-1',
                '例: 大阪府大阪市北区梅田1-1-1',
                '例: 千葉県船橋市本町',
            ],
            'debug' => [
                'searched_address' => $address,
                'tried_queries' => $searchQueries,
            ],
        ]);
    }

    /**
     * 管理画面: お知らせ添付ファイルをダウンロード
     */
    public function downloadAttachment(Notice $notice): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! Auth::check()) {
            abort(403, 'ログインが必要です。');
        }

        $user = Auth::user();
        $role = $user->role;
        if (! $role || $role->level < 40) {
            abort(403, 'アクセス権限がありません。');
        }
        if ($role->level < 100 && $notice->subdomain_id !== $user->subdomain_id) {
            abort(403, 'アクセス権限がありません。');
        }

        if (! $notice->hasAttachment()) {
            abort(404, '添付ファイルがありません。');
        }

        return $this->streamNoticeAttachment($notice);
    }

    /**
     * お知らせ添付ファイルをS3にアップロードし、Noticeを更新する
     */
    private function uploadNoticeAttachment(Notice $notice, \Illuminate\Http\UploadedFile $file): void
    {
        $subdomainId = $notice->subdomain_id;
        $noticeId = $notice->id;
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION);
        $prefix = S3KeyPrefix::forSubdomain($subdomainId);
        $s3Key = sprintf(
            '%s/notice_attachments/%d/%s.%s',
            $prefix,
            $noticeId,
            Str::uuid()->toString(),
            $extension ?: 'bin'
        );

        $content = $file->get();
        $this->putToS3WithEnvGuard($s3Key, $content);

        $notice->update([
            'attachment_s3_key' => $s3Key,
            'attachment_original_filename' => $originalName,
            'attachment_file_size' => $file->getSize(),
            'attachment_mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * お知らせの既存添付をS3から削除する（差し替え・削除時に呼ぶ）
     */
    private function deleteNoticeAttachmentFromS3(Notice $notice): void
    {
        if (empty($notice->attachment_s3_key)) {
            return;
        }

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
            if (Storage::disk('s3')->exists($notice->attachment_s3_key)) {
                Storage::disk('s3')->delete($notice->attachment_s3_key);
            }
        } catch (\Exception $e) {
            Log::warning('S3 notice attachment delete failed', [
                'notice_id' => $notice->id,
                's3_key' => $notice->attachment_s3_key,
                'error' => $e->getMessage(),
            ]);
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
    }

    /**
     * S3にputする際の環境変数ガード（AWS_* が "null" の場合の一時unset）
     */
    private function putToS3WithEnvGuard(string $key, string $content): void
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
            Storage::disk('s3')->put($key, $content);
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
    }
}
