<?php

namespace App\Http\Controllers;

use App\Traits\HandlesAuth;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    use HandlesAuth;

    /**
     * プライバシーポリシーページ表示
     */
    public function index(Request $request)
    {
        try {
            $subdomain = $this->getCurrentSubdomain($request);
        } catch (\Exception $e) {
            abort(404);
        }

        // サブドメイン固有のビューを決定
        $viewName = 'default.privacy_policy.index';
        if ($subdomain) {
            $subdomainViewName = 'default.'.$subdomain->subdomain.'.privacy_policy.index';
            if (view()->exists($subdomainViewName)) {
                $viewName = $subdomainViewName;
            }
        }

        return view($viewName, compact('subdomain'));
    }
}
