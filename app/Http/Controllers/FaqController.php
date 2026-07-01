<?php

namespace App\Http\Controllers;

use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    use HandlesAuth;

    /**
     * 対象者向けFAQページ表示
     */
    public function user(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.faq_user';
        if ($subdomain) {
            $subdomainViewName = 'default.' . $subdomain->subdomain . '.faq_user';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }

    /**
     * 事業者向けFAQページ表示
     */
    public function business(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.faq_business';
        if ($subdomain) {
            $subdomainViewName = 'default.' . $subdomain->subdomain . '.faq_business';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }
}

