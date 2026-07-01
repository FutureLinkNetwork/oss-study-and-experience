<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AccountingReportController;
use App\Http\Controllers\Admin\BusinessManagementController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Business\BusinessDashboardController;
use App\Http\Controllers\Business\BusinessLoginController;
use App\Http\Controllers\Business\BusinessNoticeController;
use App\Http\Controllers\Business\BusinessPasswordChangeController;
use App\Http\Controllers\Business\BusinessPasswordResetController;
use App\Http\Controllers\BusinessApplicationController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DefaultController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HelloController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\SessionKeepAliveController;
use App\Http\Controllers\User\CourseController as UserCourseController;
use App\Http\Controllers\User\InquiryController as UserInquiryController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\User\UserLoginController;
use App\Http\Controllers\User\UserNoticeController;
use App\Http\Controllers\User\UserPasswordChangeController;
use App\Http\Controllers\User\UserPasswordResetController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\User\VoucherApplicationController;
use App\Http\Controllers\UserApplicationController;
use Illuminate\Support\Facades\Route;

// ランディングページ
Route::get('/', [DefaultController::class, 'index'])->name('welcome');

// お知らせ
Route::prefix('notices')->name('notices.')->group(function () {
    Route::get('/{noticeId}/attachment/download', [NoticeController::class, 'downloadAttachment'])->name('attachment.download');
    Route::get('/{noticeId}', [NoticeController::class, 'show'])->name('show');
    Route::post('/load-more', [NoticeController::class, 'loadMore'])->name('load-more');
});

Route::get('/about', [AboutController::class, 'index'])->name('about');

// FAX
Route::get('/faq_user', [FaqController::class, 'user'])->name('faq_user');
Route::get('/faq_business', [FaqController::class, 'business'])->name('faq_business');

// 利用マニュアル
Route::get('/manual_user', [ManualController::class, 'user'])->name('manual_user');
Route::get('/manual_business', [ManualController::class, 'business'])->name('manual_business');

Route::prefix('course')->name('course.')->group(function () {
    Route::get('/search', [CourseController::class, 'search'])->name('search');
    Route::get('/request', [CourseController::class, 'request'])->name('request');
    Route::post('/request', [CourseController::class, 'store'])->name('request.store');
    Route::get('/{classroom}/image/{size}/download', [CourseController::class, 'downloadImage'])
        ->name('classroom.image.download')
        ->where('size', 'original|medium|thumbnail');
    Route::get('/{classroom}', [CourseController::class, 'show'])->name('show');
});

Route::prefix('business')->name('business.')->group(function () {
    Route::get('/registration', [BusinessController::class, 'registration'])->name('registration');
});

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// セッション延命・CSRF 同期（長時間フォーム用・GET は CSRF 検証対象外）
Route::get('/session/keep-alive', SessionKeepAliveController::class)
    ->name('session.keep_alive')
    ->middleware('throttle:60,1');

// 事業者申請フォーム（ログイン不要）
Route::get('/business_form', [BusinessApplicationController::class, 'create'])->name('business_application.create');
Route::post('/business_form/confirm', [BusinessApplicationController::class, 'confirm'])->name('business_application.confirm');
Route::get('/business_form/confirm', function () {
    return redirect()->route('business_application.create')
        ->with('error', 'セッションが切れました。最初から申請を行ってください。');
})->name('business_application.confirm.get');
Route::post('/business_form/store', [BusinessApplicationController::class, 'store'])->name('business_application.store');
Route::get('/business_form/complete', [BusinessApplicationController::class, 'complete'])->name('business_application.complete');

// 利用者申請フォーム（ログイン不要）
Route::get('/user_application', [UserApplicationController::class, 'create'])->name('user_application.create');
Route::post('/user_application/confirm', [UserApplicationController::class, 'confirm'])->name('user_application.confirm');
Route::get('/user_application/confirm', function () {
    return redirect()->route('user_application.create')
        ->with('error', 'セッションが切れました。最初から申請を行ってください。');
})->name('user_application.confirm.get');
Route::post('/user_application/store', [UserApplicationController::class, 'store'])->name('user_application.store');
Route::get('/user_application/complete', [UserApplicationController::class, 'complete'])->name('user_application.complete');

// API Routes
Route::prefix('api')->name('api.')->middleware(['web', 'validate.form.session', 'throttle:60,1'])->group(function () {
    Route::get('/banks', [\App\Http\Controllers\Api\BankController::class, 'banks'])->name('banks');
    Route::get('/branches', [\App\Http\Controllers\Api\BankController::class, 'branches'])->name('branches');
    Route::get('/postal-code/search', [\App\Http\Controllers\Api\PostalCodeController::class, 'search'])->name('postal-code.search');
});

// 旧ルート（テスト用）
Route::get('/hello', [HelloController::class, 'hello'])->name('hello');

// 1. 利用者ログイン（/login）
Route::middleware(['clear.auth', 'basic.auth:user'])->group(function () {
    Route::get('/login', [UserLoginController::class, 'showLoginForm'])->name('login');
});
Route::post('/login', [UserLoginController::class, 'login'])->middleware('basic.auth:user');
Route::post('/user/logout', [UserLoginController::class, 'logout'])->name('user.logout');

// パスワードリセット（ログイン不要）
Route::prefix('user')->name('user.')->group(function () {
    Route::get('/forgot-password', [UserPasswordResetController::class, 'showForgotPasswordForm'])->name('forgot-password');
    Route::post('/forgot-password', [UserPasswordResetController::class, 'sendResetLink'])->middleware('throttle:10,60')->name('forgot-password');
    Route::get('/reset', [UserPasswordResetController::class, 'showResetForm'])->name('reset');
    Route::post('/reset', [UserPasswordResetController::class, 'reset'])->name('reset');
});

// 2. 事業者ログイン（/business/login）
Route::prefix('business')->name('business.')->group(function () {
    Route::middleware(['clear.auth', 'basic.auth:business'])->group(function () {
        Route::get('/login', [BusinessLoginController::class, 'showLoginForm'])->name('login');
    });
    Route::post('/login', [BusinessLoginController::class, 'login'])->middleware('basic.auth:business');
    Route::post('/logout', [BusinessLoginController::class, 'logout'])->name('logout');

    // パスワードリセット（ログイン不要）
    Route::get('/forgot-password', [BusinessPasswordResetController::class, 'showForgotPasswordForm'])->name('forgot-password');
    Route::post('/forgot-password', [BusinessPasswordResetController::class, 'sendResetLink'])->middleware('throttle:10,60')->name('forgot-password');
    Route::get('/reset', [BusinessPasswordResetController::class, 'showResetForm'])->name('reset');
    Route::post('/reset', [BusinessPasswordResetController::class, 'reset'])->name('reset');

    // 事業者ダッシュボード（認証が必要・サブドメイン事業者のみ）
    Route::middleware(['auth', 'role:subdomain_business'])->group(function () {
        Route::get('/', [BusinessDashboardController::class, 'index'])->name('dashboard');

        // パスワード変更（初回ログイン時）
        Route::get('/password/change', [BusinessPasswordChangeController::class, 'show'])->name('password.change');
        Route::post('/password/change', [BusinessPasswordChangeController::class, 'update'])->name('password.change');

        // 事業者情報管理
        Route::get('/profile/edit', [App\Http\Controllers\Business\BusinessProfileController::class, 'show'])->name('profile.edit');
        Route::get('/profile/registration/confirm', [App\Http\Controllers\Business\BusinessProfileController::class, 'showRegistrationConfirm'])->name('profile.registration.confirm');
        Route::get('/profile/edit-email', [App\Http\Controllers\Business\BusinessProfileController::class, 'showEmailEdit'])->name('profile.edit.email');
        Route::put('/profile/email', [App\Http\Controllers\Business\BusinessProfileController::class, 'updateEmail'])->name('profile.update.email');
        Route::get('/profile/edit-password', [App\Http\Controllers\Business\BusinessProfileController::class, 'showPasswordEdit'])->name('profile.edit.password');
        Route::put('/profile/password', [App\Http\Controllers\Business\BusinessProfileController::class, 'updatePassword'])->name('profile.update.password');
        Route::put('/profile/notification', [App\Http\Controllers\Business\BusinessProfileController::class, 'updateNotification'])->name('profile.update.notification');

        // 教室管理
        Route::get('/classrooms', [App\Http\Controllers\Business\ClassroomController::class, 'index'])->name('classrooms.index');
        Route::get('/classrooms/{classroom}', [App\Http\Controllers\Business\ClassroomController::class, 'show'])->name('classrooms.show');
        Route::put('/classrooms/{classroom}', [App\Http\Controllers\Business\ClassroomController::class, 'update'])->name('classrooms.update');

        // 教室画像ダウンロード
        Route::get('/classrooms/{classroom}/image/{size}/download', [App\Http\Controllers\Business\ClassroomController::class, 'downloadImage'])
            ->name('classrooms.image.download')
            ->where('size', 'original|medium|thumbnail');

        // コース管理
        Route::get('/courses', [App\Http\Controllers\Business\CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/create', [App\Http\Controllers\Business\CourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [App\Http\Controllers\Business\CourseController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}', [App\Http\Controllers\Business\CourseController::class, 'show'])->name('courses.show');
        Route::get('/courses/{course}/edit', [App\Http\Controllers\Business\CourseController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [App\Http\Controllers\Business\CourseController::class, 'update'])->name('courses.update');
        Route::get('/courses/{course}/duplicate', [App\Http\Controllers\Business\CourseController::class, 'duplicate'])->name('courses.duplicate');

        // お知らせ
        Route::get('/notices/{noticeId}/attachment/download', [BusinessNoticeController::class, 'downloadAttachment'])->name('notices.attachment.download');
        Route::get('/notices/{noticeId}', [BusinessNoticeController::class, 'show'])->name('notices.show');

        // 申込管理
        Route::get('/applications', [App\Http\Controllers\Business\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [App\Http\Controllers\Business\ApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/{application}', [App\Http\Controllers\Business\ApplicationController::class, 'show'])->name('applications.show');
        Route::put('/applications/{application}', [App\Http\Controllers\Business\ApplicationController::class, 'update'])->name('applications.update');

        // レポート
        Route::get('/reports', [App\Http\Controllers\Business\ReportController::class, 'index'])->name('reports.index');

        // 支払管理
        Route::get('/payments', [App\Http\Controllers\Business\PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{year_month}/pdf', [App\Http\Controllers\Business\PaymentController::class, 'downloadPdf'])->name('payments.pdf')->where('year_month', '\d{4}-\d{2}');
        Route::get('/payments/{year_month}/csv', [App\Http\Controllers\Business\PaymentController::class, 'downloadCsv'])->name('payments.csv')->where('year_month', '\d{4}-\d{2}');

        // 問い合わせ
        Route::resource('inquiries', App\Http\Controllers\Business\InquiryController::class)->only(['index', 'create', 'store', 'show']);
    });
});

// 3. 管理者ログイン（/admin/login）
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['clear.auth', 'basic.auth:admin'])->group(function () {
        Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    });
    Route::post('/login', [AdminLoginController::class, 'login'])->middleware('basic.auth:admin');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// 利用者ダッシュボード（認証が必要・サブドメイン利用者のみ）
Route::middleware(['auth', 'role:subdomain_user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/', [UserDashboardController::class, 'index'])->name('dashboard');

    // パスワード変更（初回ログイン時）
    Route::get('/password/change', [UserPasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [UserPasswordChangeController::class, 'update'])->name('password.change');

    // 利用者情報管理
    Route::get('/profile/edit', [UserProfileController::class, 'show'])->name('profile.edit');
    Route::get('/profile/edit-email', [UserProfileController::class, 'showEmailEdit'])->name('profile.edit.email');
    Route::put('/profile/email', [UserProfileController::class, 'updateEmail'])->name('profile.update.email');
    Route::get('/profile/edit-password', [UserProfileController::class, 'showPasswordEdit'])->name('profile.edit.password');
    Route::put('/profile/password', [UserProfileController::class, 'updatePassword'])->name('profile.update.password');

    // お知らせ
    Route::get('/notices/{noticeId}/attachment/download', [UserNoticeController::class, 'downloadAttachment'])->name('notices.attachment.download');
    Route::get('/notices/{noticeId}', [UserNoticeController::class, 'show'])->name('notices.show');

    // 申込管理
    Route::get('/applications', [VoucherApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{voucherUsage}', [VoucherApplicationController::class, 'show'])->name('applications.show');
    Route::post('/applications/{voucherUsage}/cancel', [VoucherApplicationController::class, 'cancel'])->name('applications.cancel');

    // コース検索
    Route::prefix('course')->name('course.')->group(function () {
        Route::get('/search', [UserCourseController::class, 'search'])->name('search');
        // より具体的なルートを先に定義する必要がある
        Route::get('/{classroom}/{course}/application', [UserCourseController::class, 'application'])->name('application');
        Route::post('/{classroom}/{course}/application', [UserCourseController::class, 'storeApplication'])->name('application.store');
        Route::get('/{classroom}', [UserCourseController::class, 'show'])->name('show');
    });

    // 問い合わせ
    Route::resource('inquiries', UserInquiryController::class)->only(['index', 'create', 'store', 'show']);
});

// 管理画面ルート（認証が必要・管理者ロールのみ: サブドメイン閲覧者/作業者/管理者、システム管理者）
Route::middleware(['auth', 'role:super_admin|subdomain_admin|subdomain_operator|subdomain_viewer'])->prefix('admin')->name('admin.')->group(function () {
    // ダッシュボード
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // レポート
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');

    // サブドメイン管理
    Route::get('/subdomain/edit', [DashboardController::class, 'edit'])->name('subdomain.edit');
    Route::put('/subdomain', [DashboardController::class, 'update'])->name('subdomain.update');

    // ユーザー管理
    Route::resource('users', UserController::class);

    // 事業者管理
    Route::prefix('business')->name('business.')->group(function () {
        // 事業者基本管理
        Route::get('/', [BusinessManagementController::class, 'index'])->name('index');
        Route::get('/export-csv', [BusinessManagementController::class, 'exportCsv'])->name('export-csv');
        Route::get('/create', [BusinessManagementController::class, 'create'])->name('create');
        Route::post('/', [BusinessManagementController::class, 'store'])->name('store');
        Route::get('/{business}/edit', [BusinessManagementController::class, 'edit'])->name('edit');
        Route::put('/{business}', [BusinessManagementController::class, 'update'])->name('update');
        Route::patch('/{business}/deactivate', [BusinessManagementController::class, 'deactivate'])->name('deactivate');
        Route::patch('/{business}/activate', [BusinessManagementController::class, 'activate'])->name('activate');
        Route::post('/{business}/send-login-info', [BusinessManagementController::class, 'sendLoginInfo'])->name('send-login-info');

        // ファイルダウンロード（申請書類は documents JSON の書類キーで指定）
        Route::get('/{business}/document/{type}/download', [BusinessManagementController::class, 'downloadDocument'])
            ->name('document.download')
            ->where('type', '[a-z0-9_]+');

        // 管理者用添付ダウンロード
        Route::get('/{business}/admin-attachment/download', [BusinessManagementController::class, 'downloadAdminAttachment'])
            ->name('admin-attachment.download');

        // 教室画像ダウンロード
        Route::get('/{business}/classrooms/{classroom}/image/{size}/download', [BusinessManagementController::class, 'downloadClassroomImage'])
            ->name('classroom-image.download')
            ->where('size', 'original|medium|thumbnail');

        // 教室管理
        Route::get('/{business}/classrooms', [BusinessManagementController::class, 'classrooms'])->name('classrooms');
        Route::get('/{business}/classrooms/create', [BusinessManagementController::class, 'createClassroom'])->name('create-classroom');
        Route::post('/{business}/classrooms', [BusinessManagementController::class, 'storeClassroom'])->name('store-classroom');
        Route::get('/{business}/classrooms/{classroom}/edit', [BusinessManagementController::class, 'editClassroom'])->name('edit-classroom');
        Route::put('/{business}/classrooms/{classroom}', [BusinessManagementController::class, 'updateClassroom'])->name('update-classroom');
        Route::patch('/{business}/classrooms/{classroom}/deactivate', [BusinessManagementController::class, 'deactivateClassroom'])->name('deactivate-classroom');
        Route::patch('/{business}/classrooms/{classroom}/activate', [BusinessManagementController::class, 'activateClassroom'])->name('activate-classroom');

        // コース管理
        Route::get('/{business}/classrooms/{classroom}/courses', [BusinessManagementController::class, 'courses'])->name('courses');
        Route::get('/{business}/classrooms/{classroom}/courses/create', [BusinessManagementController::class, 'createCourse'])->name('create-course');
        Route::post('/{business}/classrooms/{classroom}/courses', [BusinessManagementController::class, 'storeCourse'])->name('store-course');
        Route::get('/{business}/classrooms/{classroom}/courses/{course}/edit', [BusinessManagementController::class, 'editCourse'])->name('edit-course');
        Route::put('/{business}/classrooms/{classroom}/courses/{course}', [BusinessManagementController::class, 'updateCourse'])->name('update-course');
        Route::patch('/{business}/classrooms/{classroom}/courses/{course}/deactivate', [BusinessManagementController::class, 'deactivateCourse'])->name('deactivate-course');
        Route::patch('/{business}/classrooms/{classroom}/courses/{course}/activate', [BusinessManagementController::class, 'activateCourse'])->name('activate-course');
    });

    // 習い事種別管理
    Route::prefix('course-categories')->name('course-categories.')->group(function () {
        Route::get('/', [CourseCategoryController::class, 'index'])->name('index');

        // 親分類管理
        Route::post('/parent-categories', [CourseCategoryController::class, 'storeParentCategory'])->name('parent-categories.store');
        Route::put('/parent-categories/{parentCategory}', [CourseCategoryController::class, 'updateParentCategory'])->name('parent-categories.update');
        Route::delete('/parent-categories/{parentCategory}', [CourseCategoryController::class, 'destroyParentCategory'])->name('parent-categories.destroy');

        // 分類管理
        Route::post('/categories', [CourseCategoryController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [CourseCategoryController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [CourseCategoryController::class, 'destroyCategory'])->name('categories.destroy');
    });

    // お知らせ管理
    Route::prefix('notices')->name('notices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\NoticeController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\NoticeController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\NoticeController::class, 'store'])->name('store');
        Route::get('/{notice}/attachment/download', [\App\Http\Controllers\Admin\NoticeController::class, 'downloadAttachment'])->name('attachment.download');
        Route::get('/{notice}', [\App\Http\Controllers\Admin\NoticeController::class, 'show'])->name('show');
        Route::get('/{notice}/edit', [\App\Http\Controllers\Admin\NoticeController::class, 'edit'])->name('edit');
        Route::put('/{notice}', [\App\Http\Controllers\Admin\NoticeController::class, 'update'])->name('update');
        Route::delete('/{notice}', [\App\Http\Controllers\Admin\NoticeController::class, 'destroy'])->name('destroy');

        // 住所から座標取得API
        Route::post('/geocode', [\App\Http\Controllers\Admin\NoticeController::class, 'geocode'])->name('geocode');
    });

    // 習い事リクエスト管理
    Route::prefix('course-requests')->name('course-requests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CourseRequestController::class, 'index'])->name('index');
        Route::get('/{courseRequest}', [\App\Http\Controllers\Admin\CourseRequestController::class, 'show'])->name('show');
        Route::put('/{courseRequest}', [\App\Http\Controllers\Admin\CourseRequestController::class, 'update'])->name('update');
    });

    // 利用者申請管理
    Route::prefix('user-applications')->name('user-applications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserApplicationController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\UserApplicationController::class, 'export'])->name('export');
        Route::put('/{userApplication}', [\App\Http\Controllers\Admin\UserApplicationController::class, 'update'])->name('update');
        Route::get('/{userApplication}', [\App\Http\Controllers\Admin\UserApplicationController::class, 'show'])->name('show');
        Route::get('/{userApplication}/document/download', [\App\Http\Controllers\Admin\UserApplicationController::class, 'downloadDocument'])->name('document.download');
    });

    // 利用者管理
    Route::prefix('beneficiaries')->name('beneficiaries.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'export'])->name('export');
        Route::post('/import', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'import'])->name('import');
        Route::post('/send-bulk-login-info', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'sendBulkLoginInfo'])->name('send-bulk-login-info');
        Route::post('/{beneficiary}/issue-voucher', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'issueVoucher'])->name('issue-voucher');
        Route::post('/{beneficiary}/send-login-info', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'sendLoginInfo'])->name('send-login-info');
        Route::post('/{beneficiary}/vouchers/{voucher}/expire', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'expireVoucher'])->name('expire-voucher');
        Route::get('/{beneficiary}', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'show'])->name('show');
        Route::put('/{beneficiary}', [\App\Http\Controllers\Admin\BeneficiaryController::class, 'update'])->name('update');
    });

    // クーポン管理
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VoucherController::class, 'index'])->name('index');
        Route::get('/export-csv', [\App\Http\Controllers\Admin\VoucherController::class, 'exportCsv'])->name('export-csv');
        Route::get('/export-attribute-csv', [\App\Http\Controllers\Admin\VoucherController::class, 'exportAttributeCsv'])->name('export-attribute-csv');
    });

    // 支払集計
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('index');
        Route::get('/download-zengin', [\App\Http\Controllers\Admin\PaymentController::class, 'downloadZengin'])->name('download-zengin');
        Route::get('/pdf', [\App\Http\Controllers\Admin\PaymentController::class, 'downloadPdf'])->name('pdf');
    });

    // 会計用月次レポート（CSV/PDFダウンロードのみ。一覧は支払集計に統合）
    Route::prefix('accounting-reports')->name('accounting-reports.')->group(function () {
        Route::get('/download-csv', [AccountingReportController::class, 'downloadCsv'])->name('download-csv');
        Route::get('/download-pdf', [AccountingReportController::class, 'downloadPdf'])->name('download-pdf');
    });

    // ダウンロード管理（利用者CSV月次など）
    Route::prefix('downloads')->name('downloads.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminDownloadController::class, 'index'])->name('index');
        Route::get('/{adminDownload}/download', [\App\Http\Controllers\Admin\AdminDownloadController::class, 'download'])->name('download');
    });

    // クーポンの利用状況管理
    Route::prefix('coupon-usages')->name('coupon-usages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CouponUsageController::class, 'index'])->name('index');
        Route::get('/export-csv', [\App\Http\Controllers\Admin\CouponUsageController::class, 'exportCsv'])->name('export-csv');
        Route::get('/{voucherUsage}', [\App\Http\Controllers\Admin\CouponUsageController::class, 'show'])->name('show');
        Route::put('/{voucherUsage}', [\App\Http\Controllers\Admin\CouponUsageController::class, 'update'])->name('update');
    });

    // お問い合わせ管理
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ContactController::class, 'index'])->name('index');
        Route::get('/{contact}', [\App\Http\Controllers\Admin\ContactController::class, 'show'])->name('show');
        Route::put('/{contact}', [\App\Http\Controllers\Admin\ContactController::class, 'update'])->name('update');
    });

    // 問い合わせ管理（利用者・事業者）
    Route::prefix('inquiries')->name('inquiries.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\InquiryController::class, 'index'])->name('index');
        Route::get('/{inquiry}', [\App\Http\Controllers\Admin\InquiryController::class, 'show'])->name('show');
        Route::put('/{inquiry}', [\App\Http\Controllers\Admin\InquiryController::class, 'update'])->name('update');
    });
});

// 旧ログインルート（互換性のため残しておく）
Route::get('/old_login', [LoginController::class, 'showLoginForm'])->name('old_login');
Route::post('/old_login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// UIコンポーネント チートシート
Route::get('/ui-cheat-sheet', function () {
    return view('ui-cheat-sheet');
})->name('ui.cheat-sheet');
