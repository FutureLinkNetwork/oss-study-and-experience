<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PostalCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostalCodeController extends Controller
{
    protected PostalCodeService $postalCodeService;

    public function __construct(PostalCodeService $postalCodeService)
    {
        $this->postalCodeService = $postalCodeService;
    }

    /**
     * 郵便番号から住所情報を取得
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'postcode' => ['required', 'string', 'regex:/^\d{3}-?\d{4}$/'],
        ], [
            'postcode.required' => '郵便番号が指定されていません。',
            'postcode.regex' => '郵便番号の形式が正しくありません。',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors(),
            ], 400);
        }

        $postcode = $request->get('postcode');
        $result = $this->postalCodeService->searchAddress($postcode);

        if ($result['result'] === 1) {
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? '郵便番号の検索に失敗しました。',
            'details' => $result['details'] ?? null,
        ], 500);
    }
}


