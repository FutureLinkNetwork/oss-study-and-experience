<?php

namespace App\Http\Controllers;

use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class ManualController extends Controller
{
    use HandlesAuth;

    /**
     * 利用マニュアル（対象者）ページ表示
     */
    public function user(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.manual_user';
        if ($subdomain) {
            $subdomainViewName = 'default.' . $subdomain->subdomain . '.manual_user';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }

    /**
     * 利用マニュアル（事業者）ページ表示
     */
    public function business(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.manual_business';
        if ($subdomain) {
            $subdomainViewName = 'default.' . $subdomain->subdomain . '.manual_business';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }
}

