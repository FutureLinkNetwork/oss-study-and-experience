<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankController extends Controller
{
    protected BankService $bankService;

    public function __construct(BankService $bankService)
    {
        $this->bankService = $bankService;
    }

    /**
     * 銀行一覧を取得
     */
    public function banks(): JsonResponse
    {
        try {
            $banks = $this->bankService->getBanksForSelect();
            
            return response()->json([
                'success' => true,
                'data' => $banks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '銀行一覧の取得に失敗しました。'
            ], 500);
        }
    }

    /**
     * 指定された銀行の支店一覧を取得
     */
    public function branches(Request $request): JsonResponse
    {
        $bankCode = $request->get('bank_code');
        
        if (!$bankCode) {
            return response()->json([
                'success' => false,
                'message' => '銀行コードが指定されていません。'
            ], 400);
        }

        try {
            $branches = $this->bankService->getBranchesForSelect($bankCode);
            
            return response()->json([
                'success' => true,
                'data' => $branches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '支店一覧の取得に失敗しました。'
            ], 500);
        }
    }
}
