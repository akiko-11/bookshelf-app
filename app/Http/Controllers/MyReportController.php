<?php

namespace App\Http\Controllers;

use App\Services\MyReportService;
use Illuminate\View\View;

class MyReportController extends Controller
{
    /**
     * ログインユーザーの読書レポートを表示する。
     *
     * Serviceから読書・レビューの統計情報を取得し、
     * マイ読書レポート画面へ渡す。
     *
     * @param  MyReportService  $myReportService  読書レポート集計サービス
     * @return View マイ読書レポート画面
     */
    public function index(MyReportService $myReportService): View
    {
        $user = auth()->user();

        $stats = $myReportService->getStatsForUser($user);

        return view('reports.index', [
            'stats' => $stats,
        ]);
    }
}
