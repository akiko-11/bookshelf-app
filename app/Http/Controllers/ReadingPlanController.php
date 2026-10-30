<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Services\ReadingPlanService;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画を一覧表示する。
     */
    public function index(
        IndexReadingPlanRequest $request,
        ReadingPlanService $readingPlanService
    ): View {
        // バリデーション済みの状態フィルターを取得
        $currentStatus = $request->validated('status') ?? '';

        // 文字列をEnumに変換（未指定の場合はnull）
        $status = ReadingPlanStatus::tryFrom($currentStatus);

        // Serviceからログインユーザーの読書計画を取得
        $readingPlans = $readingPlanService->getForUser(
            $request->user(),
            $status
        );

        return view('reading-plans.index', [
            'readingPlans' => $readingPlans,
            'currentStatus' => $currentStatus,
        ]);
    }
}
