<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ClassroomInfo;
use App\Models\CourseCategory;
use App\Models\CourseInfo;
use App\Models\CourseParentCategory;
use App\Models\Subdomain;
use App\Models\User;
use App\Models\VoucherUsage;
use App\Services\ImmediateCouponAppliedNotificationService;
use App\Support\UserCouponBalanceCalculator;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    use HandlesAuth;

    public function __construct(
        protected ImmediateCouponAppliedNotificationService $immediateCouponNotificationService
    ) {}

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
                'url' => route('user.course.show', $classroom->id),
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

        // サブドメインから学年リストを取得
        $grades = $subdomain ? $subdomain->getGrades() : [];

        return view('user.course.search', compact('subdomain', 'tab', 'classrooms', 'categories', 'keyword', 'lessonCategory', 'grade', 'grades', 'mapClassrooms'));
    }

    /**
     * 教室詳細ページ表示
     */
    public function show(Request $request, $classroom)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            Log::error('User\CourseController::show - Subdomain error', ['error' => $e->getMessage()]);
            abort(404);
        }

        // 教室情報を取得
        $classroomId = (int) $classroom;
        $classroom = ClassroomInfo::find($classroomId);
        if (! $classroom) {
            Log::error('User\CourseController::show - Classroom not found', ['id' => $classroomId]);
            abort(404);
        }

        Log::info('User\CourseController::show - Classroom found', [
            'id' => $classroom->id,
            'apply' => $classroom->apply,
            'is_active' => $classroom->is_active,
        ]);

        // 承認済み・有効な教室のみアクセス可能
        if ($classroom->apply !== 1 || ! $classroom->is_active) {
            Log::warning('User\CourseController::show - Classroom not approved or inactive', [
                'id' => $classroom->id,
                'apply' => $classroom->apply,
                'is_active' => $classroom->is_active,
            ]);
            abort(404);
        }

        // 事業者も承認済み・有効である必要がある
        $business = $classroom->businessInfo;
        if (! $business) {
            Log::warning('User\CourseController::show - Business not found', ['classroom_id' => $classroom->id]);
            abort(404);
        }

        Log::info('User\CourseController::show - Business found', [
            'business_id' => $business->id,
            'apply' => $business->apply,
            'is_active' => $business->is_active,
        ]);

        if ($business->apply !== 1 || ! $business->is_active) {
            Log::warning('User\CourseController::show - Business not approved or inactive', [
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

        // QR決済のみフラグとQRパラメータを取得
        $qrOnly = $classroom->qr_only;
        $qrAccess = $request->has('qr') && $request->get('qr') === '1';

        return view('user.course.show', compact('subdomain', 'business', 'classroom', 'courses', 'qrOnly', 'qrAccess'));
    }

    /**
     * クーポン申し込み画面表示
     */
    public function application(Request $request, $classroom, $course)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            Log::error('User\CourseController::application - Subdomain error', ['error' => $e->getMessage()]);
            abort(404);
        }

        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // 教室情報を取得
        $classroomId = (int) $classroom;
        $classroom = ClassroomInfo::find($classroomId);
        if (! $classroom) {
            Log::error('User\CourseController::application - Classroom not found', ['id' => $classroomId]);
            abort(404);
        }

        // 承認済み・有効な教室のみアクセス可能
        if ($classroom->apply !== 1 || ! $classroom->is_active) {
            abort(404);
        }

        // 事業者も承認済み・有効である必要がある
        $business = $classroom->businessInfo;
        if (! $business || $business->apply !== 1 || ! $business->is_active) {
            abort(404);
        }

        // コース情報を取得
        $courseId = (int) $course;
        $course = null;
        $isAmountSpecified = ($courseId === -1);

        if ($isAmountSpecified && $classroom->disallow_amount_specified_usage) {
            abort(404);
        }

        if (! $isAmountSpecified) {
            $course = CourseInfo::find($courseId);
            if (! $course) {
                Log::error('User\CourseController::application - Course not found', ['id' => $courseId]);
                abort(404);
            }

            // コースが有効で、この教室に属しているか確認
            if (! $course->is_active || $course->classroom_info_id !== $classroom->id) {
                abort(404);
            }
        }

        // 利用可能金額を計算
        $availableBalance = $this->calculateAvailableBalance($user);
        $canApply = false;
        $usageAmount = 0;

        if ($isAmountSpecified) {
            // 金額指定利用モード
            $canApply = true; // 初期値はtrue（入力金額による判定はフロントエンドで行う）
        } else {
            // 通常コースモード
            $canApply = $this->canApply($user, $course, $subdomain);
            $usageAmount = $this->calculateUsageAmount($course, $subdomain);
        }

        return view('user.course.application', compact('subdomain', 'business', 'classroom', 'course', 'availableBalance', 'canApply', 'usageAmount', 'isAmountSpecified'));
    }

    /**
     * クーポン申し込み処理
     */
    public function storeApplication(Request $request, $classroom, $course)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            Log::error('User\CourseController::storeApplication - Subdomain error', ['error' => $e->getMessage()]);
            abort(404);
        }

        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // 教室情報を取得
        $classroomId = (int) $classroom;
        $classroom = ClassroomInfo::find($classroomId);
        if (! $classroom) {
            abort(404);
        }

        // 承認済み・有効な教室のみアクセス可能
        if ($classroom->apply !== 1 || ! $classroom->is_active) {
            abort(404);
        }

        // 事業者も承認済み・有効である必要がある
        $business = $classroom->businessInfo;
        if (! $business || $business->apply !== 1 || ! $business->is_active) {
            abort(404);
        }

        // コース情報を取得
        $courseId = (int) $course;
        $isAmountSpecified = ($courseId === -1);
        $course = null;

        if ($isAmountSpecified && $classroom->disallow_amount_specified_usage) {
            abort(404);
        }

        if (! $isAmountSpecified) {
            $course = CourseInfo::find($courseId);
            if (! $course) {
                abort(404);
            }

            // コースが有効で、この教室に属しているか確認
            if (! $course->is_active || $course->classroom_info_id !== $classroom->id) {
                abort(404);
            }
        }

        // バリデーション
        if ($isAmountSpecified) {
            // 金額指定利用の場合
            $request->validate([
                'amount' => ['required', 'integer', 'min:1'],
                'memo' => ['nullable', 'string', 'max:1000'],
            ]);

            $usageAmount = (int) $request->input('amount');
            $memo = $request->input('memo');

            // 利用可能金額を再計算（二重チェック）
            $availableBalance = $this->calculateAvailableBalance($user);
            $canApply = $this->canApplyWithAmount($user, $usageAmount);

            if (! $canApply) {
                return redirect()->route('user.course.application', ['classroom' => $classroom->id, 'course' => -1])
                    ->with('error', '利用可能なクーポン残高が不足しています。')
                    ->withInput();
            }
        } else {
            // 通常コースの場合
            $request->validate([
                'memo' => ['nullable', 'string', 'max:1000'],
            ]);

            // 利用可能金額を再計算（二重チェック）
            $availableBalance = $this->calculateAvailableBalance($user);
            $canApply = $this->canApply($user, $course, $subdomain);

            if (! $canApply) {
                return redirect()->route('user.course.application', ['classroom' => $classroom->id, 'course' => $course->id])
                    ->with('error', '利用可能なクーポン残高が不足しています。');
            }

            $usageAmount = $this->calculateUsageAmount($course, $subdomain);
            $memo = $request->input('memo');
        }

        // トランザクション内で利用記録を作成
        try {
            $voucherUsage = null;
            DB::transaction(function () use ($user, $subdomain, $business, $classroom, $course, $usageAmount, $memo, &$voucherUsage) {
                $voucherUsage = VoucherUsage::create([
                    'user_id' => $user->id,
                    'subdomain_id' => $subdomain->id,
                    'business_info_id' => $business->id,
                    'classroom_info_id' => $classroom->id,
                    'course_info_id' => $course ? $course->id : null,
                    'amount' => $usageAmount,
                    'used_at' => now(),
                    'memo' => $memo,
                    'is_cancelled' => false,
                ]);
            });

            \assert($voucherUsage instanceof VoucherUsage);
            try {
                $this->immediateCouponNotificationService->sendIfImmediate($business, $subdomain, $voucherUsage);
            } catch (\Throwable $e) {
                Log::warning('User\CourseController::storeApplication - 都度通知メール送信で例外（申し込みは成功）', [
                    'business_info_id' => $business->id,
                    'exception' => $e->getMessage(),
                ]);
            }

            return redirect()->route('user.course.show', $classroom->id)
                ->with('success', 'クーポンでの申し込みが完了しました。');
        } catch (\Exception $e) {
            Log::error('User\CourseController::storeApplication - Error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'course_id' => $courseId,
            ]);

            $redirectRoute = $isAmountSpecified
                ? route('user.course.application', ['classroom' => $classroom->id, 'course' => -1])
                : route('user.course.application', ['classroom' => $classroom->id, 'course' => $course->id]);

            return redirect($redirectRoute)
                ->with('error', '申し込み処理中にエラーが発生しました。もう一度お試しください。')
                ->withInput();
        }
    }

    /**
     * クーポン利用可能金額を計算
     */
    private function calculateAvailableBalance(User $user): int
    {
        $subdomain = Subdomain::query()->find($user->subdomain_id);
        if (! $subdomain) {
            return 0;
        }

        return UserCouponBalanceCalculator::calculate($user)['balance'];
    }

    /**
     * 申込み可否を判定
     */
    private function canApply(User $user, CourseInfo $course, Subdomain $subdomain): bool
    {
        $usageAmount = $this->calculateUsageAmount($course, $subdomain);
        $availableBalance = $this->calculateAvailableBalance($user);

        return $availableBalance >= $usageAmount;
    }

    /**
     * コースの利用料金を計算
     */
    private function calculateUsageAmount(CourseInfo $course, Subdomain $subdomain): int
    {
        $basePrice = $course->price;

        // 外税の場合は消費税を追加
        if ($course->tax_type === 'exclusive') {
            $taxRate = $subdomain->tax_rate ?? 10.0;
            $basePrice = (int) round($basePrice * (1 + $taxRate / 100));
        }

        return $basePrice;
    }

    /**
     * 金額指定利用の可否を判定
     */
    private function canApplyWithAmount(User $user, int $amount): bool
    {
        $availableBalance = $this->calculateAvailableBalance($user);

        return $availableBalance >= $amount;
    }
}
