<?php

namespace App\Http\Controllers;

use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    use HandlesAuth;

    /**
     * 事業についてページ表示
     */
    public function index(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.about.index';
        if ($subdomain) {
            $subdomainViewName = 'default.' . $subdomain->subdomain . '.about.index';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }
}

