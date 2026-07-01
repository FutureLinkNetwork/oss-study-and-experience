<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessInfo;
use App\Models\CourseInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * コース一覧を表示
     */
    public function index()
    {
        // ログインユーザーの事業者情報を取得
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return redirect()->route('business.dashboard')
                ->with('error', '事業者情報が見つかりません。');
        }

        // 事業者に属するコース一覧を取得（教室情報も含む）
        $courses = $businessInfo->courses()
            ->with('classroomInfo')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('business.courses.index', compact('businessInfo', 'courses'));
    }

    /**
     * コース作成画面を表示
     */
    public function create(Request $request)
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return redirect()->route('business.dashboard')
                ->with('error', '事業者情報が見つかりません。');
        }

        // 事業者の教室一覧を取得
        $classrooms = $businessInfo->classrooms()
            ->where('is_active', true)
            ->orderBy('classroom_name')
            ->get();

        if ($classrooms->isEmpty()) {
            return redirect()->route('business.classrooms.index')
                ->with('error', 'コースを作成するには、まず教室を登録してください。');
        }

        // 複製元コースがある場合は取得
        $sourceCourse = null;
        if ($request->has('source')) {
            $sourceCourse = CourseInfo::where('id', $request->source)
                ->where('business_info_id', $businessInfo->id)
                ->first();
        }

        // サブドメインから学年リストを取得
        $user = \App\Models\User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? \App\Models\Subdomain::first();
        $grades = $subdomain ? $subdomain->getGrades() : [];

        return view('business.courses.form', compact('businessInfo', 'classrooms', 'sourceCourse', 'grades'));
    }

    /**
     * コースを保存
     */
    public function store(Request $request)
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return redirect()->route('business.dashboard')
                ->with('error', '事業者情報が見つかりません。');
        }

        // サブドメインから学年リストを取得
        $user = \App\Models\User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? \App\Models\Subdomain::first();
        $availableGrades = $subdomain ? $subdomain->getGrades() : [];

        // バリデーション
        $rules = [
            'classroom_info_id' => [
                'required',
                'integer',
                Rule::exists('classroom_infos', 'id')->where(function ($query) use ($businessInfo) {
                    $query->where('business_info_id', $businessInfo->id)
                        ->where('is_active', true);
                }),
            ],
            'course_name' => 'required|string|max:100',
            'course_description' => 'nullable|string|max:1000',
            'price' => 'required|integer|min:0',
            'tax_type' => 'nullable|string|max:255',
            'open_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:open_date',
            'is_active' => 'boolean',
        ];
        if (count($availableGrades) > 0) {
            $rules['grades'] = 'required|array|min:1';
            $rules['grades.*'] = 'required|string|in:'.implode(',', $availableGrades);
        }
        $validated = $request->validate($rules, [
            'grades.required' => '対象学年を1つ以上選択してください。',
            'grades.min' => '対象学年を1つ以上選択してください。',
            'grades.*.in' => '選択された学年が無効です。',
        ]);

        // 事業者IDと作成者を設定
        $validated['business_info_id'] = $businessInfo->id;
        $validated['created_user'] = Auth::id();
        $validated['updated_user'] = Auth::id();
        $validated['is_active'] = $validated['is_active'] ?? true;

        // コースを作成
        $course = CourseInfo::create($validated);

        return redirect()
            ->route('business.courses.index')
            ->with('success', 'コースを作成しました。');
    }

    /**
     * コース詳細を表示
     */
    public function show(CourseInfo $course)
    {
        // 権限チェック
        if (! $this->canAccessCourse($course)) {
            abort(403, 'このコースにアクセスする権限がありません。');
        }

        $course->load('classroomInfo');

        return view('business.courses.show', compact('course'));
    }

    /**
     * コース編集画面を表示
     */
    public function edit(CourseInfo $course)
    {
        // 権限チェック
        if (! $this->canAccessCourse($course)) {
            abort(403, 'このコースを編集する権限がありません。');
        }

        $businessInfo = $this->getUserBusinessInfo();

        // 事業者の教室一覧を取得
        $classrooms = $businessInfo->classrooms()
            ->where('is_active', true)
            ->orderBy('classroom_name')
            ->get();

        $course->load('classroomInfo');

        // サブドメインから学年リストを取得
        $user = \App\Models\User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? \App\Models\Subdomain::first();
        $grades = $subdomain ? $subdomain->getGrades() : [];
        $sourceCourse = null;

        return view('business.courses.form', compact('course', 'classrooms', 'businessInfo', 'grades', 'sourceCourse'));
    }

    /**
     * コースを更新
     */
    public function update(Request $request, CourseInfo $course)
    {
        // 権限チェック
        if (! $this->canAccessCourse($course)) {
            abort(403, 'このコースを編集する権限がありません。');
        }

        $businessInfo = $this->getUserBusinessInfo();

        // サブドメインから学年リストを取得
        $user = \App\Models\User::with('subdomain')->find(Auth::id());
        $subdomain = $user->subdomain ?? \App\Models\Subdomain::first();
        $availableGrades = $subdomain ? $subdomain->getGrades() : [];

        // バリデーション
        $validated = $request->validate([
            'classroom_info_id' => [
                'required',
                'integer',
                Rule::exists('classroom_infos', 'id')->where(function ($query) use ($businessInfo) {
                    $query->where('business_info_id', $businessInfo->id)
                        ->where('is_active', true);
                }),
            ],
            'course_name' => 'required|string|max:100',
            'course_description' => 'nullable|string|max:1000',
            'price' => 'required|integer|min:0',
            'tax_type' => 'nullable|string|max:255',
            'open_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:open_date',
            'is_active' => 'boolean',
            'grades' => 'required|array|min:1',
            'grades.*' => 'required|string|in:'.implode(',', $availableGrades),
        ], [
            'grades.required' => '対象学年を1つ以上選択してください。',
            'grades.min' => '対象学年を1つ以上選択してください。',
            'grades.*.in' => '選択された学年が無効です。',
        ]);

        // 更新者を設定
        $validated['updated_user'] = Auth::id();
        $validated['is_active'] = $validated['is_active'] ?? $course->is_active;

        // コースを更新
        $course->update($validated);

        return redirect()
            ->route('business.courses.index')
            ->with('success', 'コース情報を更新しました。');
    }

    /**
     * コース複製画面を表示（複製元データが表示された作成画面）
     */
    public function duplicate(CourseInfo $course)
    {
        // 権限チェック
        if (! $this->canAccessCourse($course)) {
            abort(403, 'このコースを複製する権限がありません。');
        }

        return redirect()->route('business.courses.create', ['source' => $course->id]);
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
     * 指定されたコースにアクセス可能かチェック
     */
    private function canAccessCourse(CourseInfo $course): bool
    {
        $businessInfo = $this->getUserBusinessInfo();

        if (! $businessInfo) {
            return false;
        }

        return $course->business_info_id === $businessInfo->id;
    }
}
