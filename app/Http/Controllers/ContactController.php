<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    use HandlesAuth;

    /**
     * お問い合わせページ表示
     */
    public function index(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.contact.index';
        if ($subdomain) {
            $subdomainViewName = 'default.'.$subdomain->subdomain.'.contact.index';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }

    /**
     * お問い合わせ送信処理
     */
    public function store(StoreContactRequest $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // IPアドレスを取得
        $ipAddress = $request->ip();

        // データベースに保存
        Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'content' => $request->content,
            'ip_address' => $ipAddress,
            'is_confirmed' => false,
			'subdomain_id' => $subdomain?->id,
        ]);

        // 成功メッセージをセッションに保存
        return redirect()->route('contact')
            ->with('success', 'お問い合わせを送信しました。ありがとうございます。');
    }
}
