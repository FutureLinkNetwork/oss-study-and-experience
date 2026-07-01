<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PostalCodeService
{
    /**
     * 郵便番号から住所情報を取得
     *
     * @param  string  $postcode  郵便番号（ハイフンあり/なし両対応）
     * @return array 住所情報またはエラー情報
     */
    public function searchAddress(string $postcode): array
    {
        // 郵便番号からハイフンを除去
        $postcode = str_replace('-', '', $postcode);

        // 環境変数の取得（.envファイルから直接取得）
        $apiUrl = env('JP_POST_API_BASE_URL');
        $clientId = env('JP_POST_API_ID');
        $secretKey = env('JP_POST_API_SECRET');

        if (! $apiUrl || ! $clientId || ! $secretKey) {
            return [
                'result' => 0,
                'error' => '郵便番号検索APIの設定が不完全です',
            ];
        }

        try {
            // トークン取得
            $token = $this->getAccessToken($apiUrl, $clientId, $secretKey);

            if (isset($token['error_code'])) {
                Log::error('郵便番号検索API: トークン取得失敗', $token);

                return [
                    'result' => 0,
                    'error' => 'トークンの取得に失敗しました',
                    'details' => $token,
                ];
            }

            if (! isset($token['token'])) {
                Log::error('郵便番号検索API: トークンが取得できませんでした', $token);

                return [
                    'result' => 0,
                    'error' => 'トークンの取得に失敗しました',
                    'details' => $token,
                ];
            }

            // 郵便番号検索
            $addressData = $this->searchPostalCode($apiUrl, $token['token'], $postcode);

            if (isset($addressData['error_code'])) {
                Log::error('郵便番号検索API: 検索失敗', $addressData);

                $errorMessage = ($addressData['error_code'] === 'HTTP_ERROR' && ($addressData['http_code'] ?? null) === 404)
                    ? '郵便場号が見つかりませんでした'
                    : '郵便番号の検索に失敗しました';

                return [
                    'result' => 0,
                    'error' => $errorMessage,
                    'details' => $addressData,
                ];
            }

            // 成功フラグを追加
            $addressData['result'] = 1;

            return $addressData;
        } catch (\Exception $e) {
            Log::error('郵便番号検索API: 例外発生', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'result' => 0,
                'error' => '郵便番号の検索中にエラーが発生しました',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * アクセストークンを取得
     *
     * @param  string  $apiUrl  APIのベースURL
     * @param  string  $clientId  クライアントID
     * @param  string  $secretKey  シークレットキー
     * @return array トークン情報またはエラー情報
     */
    private function getAccessToken(string $apiUrl, string $clientId, string $secretKey): array
    {
        $tokenUrl = rtrim($apiUrl, '/').'/api/v1/j/token';

        $requestData = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'secret_key' => $secretKey,
        ];

        // DNS解決の手動確認（CURLOPT_RESOLVE用）
        $hostname = parse_url($tokenUrl, PHP_URL_HOST);
        $resolvedIp = gethostbyname($hostname);

        // プロキシ設定の確認
        $proxyEnv = [
            'HTTP_PROXY' => $_SERVER['HTTP_PROXY'] ?? getenv('HTTP_PROXY'),
            'HTTPS_PROXY' => $_SERVER['HTTPS_PROXY'] ?? getenv('HTTPS_PROXY'),
            'http_proxy' => $_SERVER['http_proxy'] ?? getenv('http_proxy'),
            'https_proxy' => $_SERVER['https_proxy'] ?? getenv('https_proxy'),
        ];

        $proxyUrl = $proxyEnv['HTTPS_PROXY'] ?: $proxyEnv['https_proxy'] ?: $proxyEnv['HTTP_PROXY'] ?: $proxyEnv['http_proxy'];

        if ($resolvedIp !== $hostname && filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
            // まずCURLOPT_RESOLVEを試す
            $curlHandle = curl_init($tokenUrl);
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: curl/8.9.1',
                ],
                // タイムアウト設定を追加
                CURLOPT_CONNECTTIMEOUT => 10,  // 接続タイムアウト（秒）
                CURLOPT_TIMEOUT => 30,         // 全体のタイムアウト（秒）
                // SSL/TLS設定を追加
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                // HTTP/3を無効化してHTTP/2を使用
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                // CURLOPT_RESOLVEを使用して、ホスト名をIPアドレスに解決
                CURLOPT_RESOLVE => [$hostname.':443:'.$resolvedIp],
            ];

            if (! empty($proxyUrl)) {
                $curlOptions[CURLOPT_PROXY] = $proxyUrl;
            }

            curl_setopt_array($curlHandle, $curlOptions);
        } else {
            // IPアドレスが取得できない場合は通常の接続
            $curlHandle = curl_init($tokenUrl);
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: curl/8.9.1',
                ],
                // タイムアウト設定を追加
                CURLOPT_CONNECTTIMEOUT => 10,  // 接続タイムアウト（秒）
                CURLOPT_TIMEOUT => 30,         // 全体のタイムアウト（秒）
                // SSL/TLS設定を追加
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                // HTTP/3を無効化してHTTP/2を使用
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            ];

            if (! empty($proxyUrl)) {
                $curlOptions[CURLOPT_PROXY] = $proxyUrl;
            }

            curl_setopt_array($curlHandle, $curlOptions);
        }

        $tokenResponse = curl_exec($curlHandle);
        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curlHandle);
        $curlErrno = curl_errno($curlHandle);
        $curlInfo = curl_getinfo($curlHandle);

        // CURLOPT_RESOLVEが機能しない場合、IPアドレスを直接使用するフォールバック
        if ($curlErrno === 7 && $resolvedIp !== $hostname && filter_var($resolvedIp, FILTER_VALIDATE_IP)) {
            // IPアドレスを直接使用してURLを構築
            $tokenUrlWithIp = str_replace($hostname, $resolvedIp, $tokenUrl);
            $curlHandle2 = curl_init($tokenUrlWithIp);
            $curlOptions2 = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'User-Agent: curl/8.9.1',
                    'Host: '.$hostname,  // 元のホスト名をHostヘッダーで指定
                ],
                // タイムアウト設定を追加
                CURLOPT_CONNECTTIMEOUT => 10,  // 接続タイムアウト（秒）
                CURLOPT_TIMEOUT => 30,         // 全体のタイムアウト（秒）
                // SSL/TLS設定を追加（IPアドレスで接続するため、ホスト名検証を緩和）
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 0,  // IPアドレスで接続するため、ホスト名検証を無効化
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,  // HTTP/2を使用
            ];

            curl_setopt_array($curlHandle2, $curlOptions2);
            $tokenResponse = curl_exec($curlHandle2);
            $httpCode = curl_getinfo($curlHandle2, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curlHandle2);
            $curlErrno = curl_errno($curlHandle2);
            $curlInfo = curl_getinfo($curlHandle2);
        }

        if ($curlError) {
            return [
                'error_code' => 'CURL_ERROR',
                'error' => $curlError,
            ];
        }

        if ($httpCode !== 200) {
            return [
                'error_code' => 'HTTP_ERROR',
                'error' => "HTTPステータスコード: {$httpCode}",
                'http_code' => $httpCode,
            ];
        }

        $tokenResult = json_decode($tokenResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error_code' => 'JSON_ERROR',
                'error' => 'レスポンスの解析に失敗しました',
                'response' => $tokenResponse,
            ];
        }

        return $tokenResult;
    }

    /**
     * 郵便番号を検索
     *
     * @param  string  $apiUrl  APIのベースURL
     * @param  string  $accessToken  アクセストークン
     * @param  string  $postcode  郵便番号（ハイフンなし）
     * @return array 住所情報またはエラー情報
     */
    private function searchPostalCode(string $apiUrl, string $accessToken, string $postcode): array
    {
        $searchUrl = rtrim($apiUrl, '/').'/api/v1/searchcode/'.$postcode;

        $searchHandle = curl_init($searchUrl);
        curl_setopt_array($searchHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$accessToken,
                'User-Agent: curl/8.9.1',
            ],
            // タイムアウト設定を追加
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            // SSL/TLS設定を追加
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            // HTTP/3を無効化してHTTP/2を使用
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        ]);

        $searchResponse = curl_exec($searchHandle);
        $httpCode = curl_getinfo($searchHandle, CURLINFO_HTTP_CODE);
        $curlError = curl_error($searchHandle);
        // curl_close($searchHandle);

        if ($curlError) {
            return [
                'error_code' => 'CURL_ERROR',
                'error' => $curlError,
            ];
        }

        if ($httpCode !== 200) {
            return [
                'error_code' => 'HTTP_ERROR',
                'error' => "HTTPステータスコード: {$httpCode}",
                'http_code' => $httpCode,
            ];
        }

        $searchResult = json_decode($searchResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error_code' => 'JSON_ERROR',
                'error' => 'レスポンスの解析に失敗しました',
                'response' => $searchResponse,
            ];
        }

        return $searchResult;
    }
}
