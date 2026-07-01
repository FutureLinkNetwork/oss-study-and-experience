<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequestRequest;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseParentCategory;
use App\Models\CourseRequest;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    use HandlesAuth;

    /**
     * 習い事検索ページ表示
     */
    public function search(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        $tab = $request->get('tab', 'condition'); // 'condition' or 'map'
        $currentDate = Carbon::now();

        // 検索条件取得
        $keyword = $request->get('keyword');
        $lessonCategory = $request->get('lesson_category', []);
        // 配列でない場合は配列に変換（後方互換性のため）
        if (! is_array($lessonCategory)) {
            $lessonCategory = $lessonCategory ? [$lessonCategory] : [];
        }
        $grade = $request->get('grade');

        // 承認済み・有効な教室を取得するベースクエリ
        $classroomsQuery = ClassroomInfo::query()
            ->where('apply', 1) // 承認済み
            ->where('is_active', 1) // 有効
            ->whereHas('businessInfo', function ($query) {
                $query->where('apply', 1)
                    ->where('is_active', 1);
            })
            ->with(['businessInfo', 'lessonCategoryInfo', 'lessonCategoryInfo.parentCategory']);

        // 条件検索の場合のみフィルタを適用
        if ($tab === 'condition') {
            // フリーワード検索（教室名、教室紹介、コース名、コース詳細）
            if ($keyword) {
                $classroomsQuery->where(function ($query) use ($keyword, $currentDate) {
                    $query->where('classroom_name', 'like', '%'.$keyword.'%')
                        ->orWhere('classroom_introduction', 'like', '%'.$keyword.'%')
                        ->orWhereHas('courses', function ($q) use ($keyword, $currentDate) {
                            $q->where('is_active', 1)
                                ->where(function ($subQ) use ($currentDate) {
                                    // 期間判定：open_dateとend_dateがnullの場合は期間制限なし
                                    $subQ->where(function ($dateQ) {
                                        // 両方null：期間制限なし
                                        $dateQ->whereNull('open_date')
                                            ->whereNull('end_date');
                                    })
                                        ->orWhere(function ($dateQ) use ($currentDate) {
                                            // open_dateのみnull：end_dateが未来
                                            $dateQ->whereNull('open_date')
                                                ->where('end_date', '>=', $currentDate);
                                        })
                                        ->orWhere(function ($dateQ) use ($currentDate) {
                                            // end_dateのみnull：open_dateが過去
                                            $dateQ->whereNull('end_date')
                                                ->where('open_date', '<=', $currentDate);
                                        })
                                        ->orWhere(function ($dateQ) use ($currentDate) {
                                            // 両方設定：現在日付が期間内
                                            $dateQ->whereNotNull('open_date')
                                                ->whereNotNull('end_date')
                                                ->where('open_date', '<=', $currentDate)
                                                ->where('end_date', '>=', $currentDate);
                                        });
                                })
                                ->where(function ($nameQ) use ($keyword) {
                                    $nameQ->where('course_name', 'like', '%'.$keyword.'%')
                                        ->orWhere('course_description', 'like', '%'.$keyword.'%');
                                });
                        });
                });
            }

            if (! empty($lessonCategory) && is_array($lessonCategory)) {
                $classroomsQuery->whereIn('lesson_category', $lessonCategory);
            }

            // 学年検索
            if ($grade) {
                $classroomsQuery->where(function ($query) use ($grade, $currentDate) {
                    // コースがあり、指定学年が含まれる教室
                    $query->whereHas('courses', function ($q) use ($grade, $currentDate) {
                        $q->where('is_active', 1)
                            ->where(function ($subQ) use ($currentDate) {
                                // 期間判定
                                $subQ->where(function ($dateQ) {
                                    $dateQ->whereNull('open_date')
                                        ->whereNull('end_date');
                                })
                                    ->orWhere(function ($dateQ) use ($currentDate) {
                                        $dateQ->whereNull('open_date')
                                            ->where('end_date', '>=', $currentDate);
                                    })
                                    ->orWhere(function ($dateQ) use ($currentDate) {
                                        $dateQ->whereNull('end_date')
                                            ->where('open_date', '<=', $currentDate);
                                    })
                                    ->orWhere(function ($dateQ) use ($currentDate) {
                                        $dateQ->whereNotNull('open_date')
                                            ->whereNotNull('end_date')
                                            ->where('open_date', '<=', $currentDate)
                                            ->where('end_date', '>=', $currentDate);
                                    });
                            })
                            ->whereJsonContains('grades', $grade);
                    })
                    // またはコースがない教室
                        ->orWhereDoesntHave('courses');
                });
            }

            // 並び順：登録日時（新しい順）
            $classroomsQuery->orderBy('created_at', 'desc');

            // ページネーション
            $classrooms = $classroomsQuery->paginate(10)->withQueryString();
        } else {
            // 地図検索：位置情報がある教室のみ
            $classroomsQuery->whereNotNull('classroom_latitude')
                ->whereNotNull('classroom_longitude');
            $classrooms = $classroomsQuery->get();
        }

        // 地図検索用のデータを準備（常に準備する）
        $mapClassrooms = [];
        // 地図検索用のクエリを実行（位置情報がある教室のみ）
        $mapClassroomsQuery = ClassroomInfo::query()
            ->where('apply', 1)
            ->where('is_active', 1)
            ->whereNotNull('classroom_latitude')
            ->whereNotNull('classroom_longitude')
            ->whereHas('businessInfo', function ($query) {
                $query->where('apply', 1)
                    ->where('is_active', 1);
            })
            ->with(['lessonCategoryInfo']);

        $mapClassrooms = $mapClassroomsQuery->get()->map(function ($classroom) {
            return [
                'id' => $classroom->id,
                'name' => $classroom->classroom_name,
                'latitude' => (float) $classroom->classroom_latitude,
                'longitude' => (float) $classroom->classroom_longitude,
                'category' => $classroom->lessonCategoryInfo ? $classroom->lessonCategoryInfo->name : '',
                'business_hours' => $classroom->business_hours,
                'holiday' => $classroom->holiday,
                'url' => route('course.show', $classroom->id),
            ];
        })->toArray();

        // 習い事の種別カテゴリ取得（条件検索フォーム用）
        $categories = collect();
        if ($subdomain) {
            $parentCategories = CourseParentCategory::where('subdomain_id', $subdomain->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            foreach ($parentCategories as $parent) {
                $childCategories = CourseCategory::where('parent_category_id', $parent->id)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();
                $categories->push([
                    'parent' => $parent,
                    'children' => $childCategories,
                ]);
            }
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.course.search';
        if ($subdomain) {
            $subdomainViewName = 'default.'.$subdomain->subdomain.'.course.search';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        $grades = $subdomain->getGrades();

        return view($viewName, compact('subdomain', 'tab', 'classrooms', 'categories', 'keyword', 'lessonCategory', 'grade', 'grades', 'mapClassrooms'));
    }

    /**
     * 教室詳細ページ表示
     */
    public function show(Request $request, $classroom)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            Log::error('CourseController::show - Subdomain error', ['error' => $e->getMessage()]);
            abort(404);
        }

        // 教室情報を取得
        $classroomId = (int) $classroom;
        $classroom = ClassroomInfo::find($classroomId);
        if (! $classroom) {
            Log::error('CourseController::show - Classroom not found', ['id' => $classroomId]);
            abort(404);
        }

        Log::info('CourseController::show - Classroom found', [
            'id' => $classroom->id,
            'apply' => $classroom->apply,
            'is_active' => $classroom->is_active,
        ]);

        // 承認済み・有効な教室のみアクセス可能
        if ($classroom->apply !== 1 || ! $classroom->is_active) {
            Log::warning('CourseController::show - Classroom not approved or inactive', [
                'id' => $classroom->id,
                'apply' => $classroom->apply,
                'is_active' => $classroom->is_active,
            ]);
            abort(404);
        }

        // 事業者も承認済み・有効である必要がある
        $business = $classroom->businessInfo;
        if (! $business) {
            Log::warning('CourseController::show - Business not found', ['classroom_id' => $classroom->id]);
            abort(404);
        }

        Log::info('CourseController::show - Business found', [
            'business_id' => $business->id,
            'apply' => $business->apply,
            'is_active' => $business->is_active,
        ]);

        if ($business->apply !== 1 || ! $business->is_active) {
            Log::warning('CourseController::show - Business not approved or inactive', [
                'business_id' => $business->id,
                'apply' => $business->apply,
                'is_active' => $business->is_active,
            ]);
            abort(404);
        }

        // 有効なコースのみ取得（期間判定含む）
        $currentDate = Carbon::now();
        $courses = $classroom->courses()
            ->where('is_active', 1)
            ->where(function ($query) use ($currentDate) {
                // 期間判定：open_dateとend_dateがnullの場合は期間制限なし
                $query->where(function ($q) {
                    // 両方null：期間制限なし
                    $q->whereNull('open_date')
                        ->whereNull('end_date');
                })
                    ->orWhere(function ($q) use ($currentDate) {
                        // open_dateのみnull：end_dateが未来
                        $q->whereNull('open_date')
                            ->where('end_date', '>=', $currentDate);
                    })
                    ->orWhere(function ($q) use ($currentDate) {
                        // end_dateのみnull：open_dateが過去
                        $q->whereNull('end_date')
                            ->where('open_date', '<=', $currentDate);
                    })
                    ->orWhere(function ($q) use ($currentDate) {
                        // 両方設定：現在日付が期間内
                        $q->whereNotNull('open_date')
                            ->whereNotNull('end_date')
                            ->where('open_date', '<=', $currentDate)
                            ->where('end_date', '>=', $currentDate);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // サブドメインから学年リストを取得
        $grades = $subdomain ? $subdomain->getGrades() : [];

        // サブドメイン固有のビューを決定
        $viewName = 'default.course.show';
        if ($subdomain) {
            $subdomainViewName = 'default.'.$subdomain->subdomain.'.course.show';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain', 'business', 'classroom', 'courses', 'grades'));
    }

    /**
     * 教室画像ダウンロード（一般公開用）
     */
    public function downloadImage($classroom, string $size)
    {
        // 教室情報を取得
        $classroom = ClassroomInfo::find($classroom);
        if (! $classroom) {
            abort(404);
        }

        // 承認済み・有効な教室のみアクセス可能
        if ($classroom->apply !== 1 || ! $classroom->is_active) {
            abort(404);
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

            // ファイルをダウンロード
            return response()->streamDownload(
                function () use ($fileContent) {
                    echo $fileContent;
                },
                $filename,
                [
                    'Content-Type' => $mimeType,
                    'Cache-Control' => 'public, max-age=3600',
                ]
            );

        } catch (\Exception $e) {
            abort(500, '画像のダウンロードに失敗しました。');
        }
    }

    /**
     * 習い事リクエストページ表示
     */
    public function request(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.course.request';
        if ($subdomain) {
            $subdomainViewName = 'default.'.$subdomain->subdomain.'.course.request';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }

    /**
     * 習い事リクエスト送信処理
     */
    public function store(StoreCourseRequestRequest $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // IPアドレスを取得
        $ipAddress = $request->ip();

        // データベースに保存
        CourseRequest::create([
            'classroom_name' => $request->classroom_name,
            'address' => $request->address,
            'phone' => $request->phone,
            'requester_name' => $request->requester_name,
            'requester_email' => $request->requester_email,
            'requester_phone' => $request->requester_phone,
            'ip_address' => $ipAddress,
            'is_confirmed' => false,
            'subdomain_id' => $subdomain->id,
        ]);

        // 成功メッセージをセッションに保存
        return redirect()->route('course.request')
            ->with('success', 'リクエストを送信しました。ありがとうございます。');
    }
}
