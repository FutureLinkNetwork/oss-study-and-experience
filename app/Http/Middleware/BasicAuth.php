<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuth
{
    private const ROUTE_CONFIG_KEYS = [
        'user' => 'user_login',
        'business' => 'business_login',
        'admin' => 'admin_login',
    ];

    /**
     * HTTP Basic 認証を行う。設定で無効または対象ルートでない場合は通過。
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $routeType): Response
    {
        $configKey = self::ROUTE_CONFIG_KEYS[$routeType] ?? null;
        if ($configKey === null || ! config("basic_auth.routes.{$configKey}", false)) {
            return $next($request);
        }

        if (! config('basic_auth.enabled', false)) {
            return $next($request);
        }

        $username = config('basic_auth.username');
        $password = config('basic_auth.password');
        if ($username === null || $username === '' || $password === null) {
            return $next($request);
        }

        $user = $this->getCredentials($request);
        if ($user === null) {
            return $this->unauthorizedResponse();
        }

        if (! $this->validateCredentials($user['username'], $user['password'], $username, $password)) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    /**
     * @return array{username: string, password: string}|null
     */
    private function getCredentials(Request $request): ?array
    {
        $header = $request->header('Authorization');
        if (is_string($header) && str_starts_with(strtolower($header), 'basic ')) {
            $decoded = base64_decode(substr($header, 6), true);
            if ($decoded !== false && str_contains($decoded, ':')) {
                [$user, $pass] = explode(':', $decoded, 2);

                return ['username' => $user, 'password' => $pass];
            }
        }

        if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            return [
                'username' => (string) $_SERVER['PHP_AUTH_USER'],
                'password' => (string) $_SERVER['PHP_AUTH_PW'],
            ];
        }

        return null;
    }

    private function validateCredentials(string $givenUser, string $givenPass, string $expectedUser, string $expectedPass): bool
    {
        return hash_equals($expectedUser, $givenUser) && hash_equals($expectedPass, $givenPass);
    }

    private function unauthorizedResponse(): Response
    {
        $realm = config('basic_auth.realm', 'Application');

        return new Response(
            'Unauthorized.',
            401,
            [
                'WWW-Authenticate' => 'Basic realm="'.addslashes($realm).'"',
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]
        );
    }
}
