<?php

namespace App\Http\Controllers\Business;

use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInquiryRequest;
use App\Mail\InquiryReceivedMail;
use App\Models\Inquiry;
use App\Traits\HandlesAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InquiryController extends Controller
{
    use HandlesAuth;

    /**
     * 問い合わせ一覧を表示
     */
    public function index(Request $request): View
    {
        $user = Auth::user()->load('role');
        if ($user->role->name !== 'subdomain_business') {
            abort(403, '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        $inquiries = Inquiry::query()
            ->where('user_id', $user->id)
            ->forSubdomain($subdomain->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('business.inquiries.index', compact('subdomain', 'inquiries'));
    }

    /**
     * 問い合わせ作成フォームを表示
     */
    public function create(Request $request): View
    {
        $user = Auth::user()->load('role');
        if ($user->role->name !== 'subdomain_business') {
            abort(403, '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        return view('business.inquiries.create', compact('subdomain'));
    }

    /**
     * 問い合わせを保存してメール送信
     */
    public function store(StoreInquiryRequest $request): RedirectResponse
    {
        $user = Auth::user()->load('role');
        if ($user->role->name !== 'subdomain_business') {
            abort(403, '事業者権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);

        $inquiry = Inquiry::create([
            'subdomain_id' => $subdomain->id,
            'user_id' => $user->id,
            'inquiry_type' => InquiryType::Business,
            'content' => $request->validated('content'),
            'status' => InquiryStatus::Pending,
            'created_user_id' => $user->id,
        ]);

        // Mail::to($user->email)->send(new InquiryReceivedMail($inquiry, $subdomain));

        return redirect()->route('business.inquiries.index')
            ->with('success', '問い合わせを送信しました。');
    }

    /**
     * 問い合わせ詳細を表示
     */
    public function show(Request $request, Inquiry $inquiry): View|RedirectResponse
    {
        $user = Auth::user()->load('role');
        if ($user->role->name !== 'subdomain_business') {
            abort(403, '事業者権限がありません。');
        }

        if ($inquiry->user_id !== $user->id) {
            abort(403, 'この問い合わせを表示する権限がありません。');
        }

        $subdomain = $this->getCurrentSubdomain($request);
        if ($inquiry->subdomain_id !== $subdomain->id) {
            abort(403, 'この問い合わせを表示する権限がありません。');
        }

        return view('business.inquiries.show', compact('subdomain', 'inquiry'));
    }
}
