<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Exceptions\ReadingPlanAlreadyCompletedException;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
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

    /**
     * 読書計画の編集画面を表示する。
     *
     * @param  ReadingPlan  $plan  編集対象の読書計画
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $plan): View
    {
        $this->authorize('update', $plan);

        return view('reading-plans.edit', [
            'readingPlan' => $plan,
        ]);
    }

    /**
     * 指定した読書計画の期日を更新する。
     *
     * 更新成功時は一覧画面へリダイレクトし、
     * DB更新エラー時は入力内容を保持して編集画面へ戻す。
     *
     * @param  UpdateReadingPlanRequest  $request  検証済みの更新リクエスト
     * @param  ReadingPlan  $plan  更新対象の読書計画
     * @param  ReadingPlanService  $readingPlanService  読書計画サービス
     * @return RedirectResponse 更新結果に応じたリダイレクトレスポンス
     */
    public function update(
        UpdateReadingPlanRequest $request,
        ReadingPlan $plan,
        ReadingPlanService $readingPlanService
    ): RedirectResponse {
        try {
            $readingPlanService->updatePlan(
                $plan,
                $request->validated()
            );
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('reading-plans.edit', $plan)
                ->withInput()
                ->with('error', '読書計画の更新に失敗しました。');
        }

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * 指定された読書計画を読了状態に更新する。
     *
     * @param  ReadingPlan  $plan  読了対象の読書計画
     * @param  ReadingPlanService  $readingPlanService  読書計画サービス
     * @return RedirectResponse 読了後のリダイレクトレスポンス
     */
    public function complete(
        ReadingPlan $plan,
        ReadingPlanService $readingPlanService
    ): RedirectResponse {
        // 所有者の認可
        $this->authorize('complete', $plan);

        try {
            $readingPlanService->completePlan($plan);
        } catch (ReadingPlanAlreadyCompletedException $e) {
            abort(409, 'この読書計画は既に完了しています。');
        }

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了しました。');
    }

    /**
     * 読書計画と関連通知を削除し、一覧画面へリダイレクトする。
     *
     * @param  ReadingPlan  $plan  削除対象の読書計画
     * @param  ReadingPlanService  $readingPlanService  読書計画サービス
     * @return RedirectResponse 削除結果に応じたリダイレクトレスポンス
     */
    public function destroy(
        ReadingPlan $plan,
        ReadingPlanService $readingPlanService
    ): RedirectResponse {
        // 所有者の認可
        $this->authorize('delete', $plan);

        try {
            // Serviceで読書計画と関連通知を削除
            $readingPlanService->deletePlan($plan);
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('reading-plans.index')
                ->with('error', '読書計画の削除に失敗しました。');
        }

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
