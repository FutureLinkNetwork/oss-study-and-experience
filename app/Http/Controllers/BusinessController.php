<?php

namespace App\Http\Controllers;

use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    use HandlesAuth;

    /**
     * 事業者登録についてページ表示
     */
    public function registration(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.business.registration';
        if ($subdomain) {
            $subdomainViewName = 'default.' . $subdomain->subdomain . '.business.registration';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }
}

