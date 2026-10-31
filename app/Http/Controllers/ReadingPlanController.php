<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Models\Book;
use App\Services\ReadingPlanService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * ログインユーザーの読書計画を一覧表示する。
     *
     * 指定されたステータスに応じて読書計画を絞り込み、
     * 期日の昇順で取得して一覧画面へ渡す。
     *
     * @param  IndexReadingPlanRequest  $request  検証済みの一覧表示リクエスト
     * @param  ReadingPlanService  $readingPlanService  読書計画サービス
     * @return View 読書計画一覧画面
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

    /**
     * 読書計画の登録画面を表示する。
     */
    public function create(): View
    {
        // 全ユーザーが登録した書籍を取得
        $books = Book::all();

        // 登録画面に書籍データを渡す
        return view('reading-plans.create', [
            'books' => $books,
        ]);
    }

    /**
     * 読書計画を新規登録する。
     *
     * 登録成功時は一覧画面へリダイレクトし、
     * DB登録エラー時は入力内容を保持して作成画面へ戻す。
     *
     * @param  StoreReadingPlanRequest  $request  検証済みの登録リクエスト
     * @param  ReadingPlanService  $readingPlanService  読書計画サービス
     * @return RedirectResponse 登録結果に応じたリダイレクトレスポンス
     */
    public function store(
        StoreReadingPlanRequest $request,
        ReadingPlanService $readingPlanService
    ): RedirectResponse {
        try {
            $readingPlanService->createForUser(
                $request->user(),
                $request->validated()
            );
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('reading-plans.create')
                ->withInput()
                ->with('error', '読書計画の登録に失敗しました。');
        }

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }
}
