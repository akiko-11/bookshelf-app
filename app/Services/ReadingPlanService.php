<?php

namespace App\Services;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ReadingPlanService
{
    /**
     * 指定ユーザーの読書計画を取得する。
     *
     * ステータスが指定されている場合は絞り込み、
     * 期日の昇順で取得する。
     *
     * @param  User  $user  対象ユーザー
     * @param  ReadingPlanStatus|null  $status  絞り込み対象のステータス
     * @return Collection<int, ReadingPlan> 読書計画のコレクション
     */
    public function getForUser(User $user, ?ReadingPlanStatus $status = null): Collection
    {
        // ユーザーの読書計画を取得
        $query = $user->readingPlans()
            ->with('book');

        // 状態が指定されている場合のみ絞り込み
        if ($status !== null) {
            $query->where('status', $status->value);
        }

        // 期日の昇順で取得
        $readingPlans = $query
            ->orderBy('target_date')
            ->get();

        return $readingPlans;
    }

    /**
     * 指定ユーザーの読書計画を新規登録する。
     *
     * 初期ステータスを進行中、完了日をNULLとして登録する。
     *
     * @param  User  $user  登録対象のユーザー
     * @param  array  $data  検証済みの登録データ
     * @return ReadingPlan 登録された読書計画
     */
    public function createForUser(User $user, array $data): ReadingPlan
    {
        return $user->readingPlans()->create([
            'book_id' => $data['book_id'],
            'target_date' => $data['target_date'],
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }

    /**
     * 指定された読書計画の期日を更新する。
     *
     * @param  ReadingPlan  $plan  更新対象の読書計画
     * @param  array  $data  検証済みの更新データ
     * @return ReadingPlan 更新後の読書計画
     */
    public function updatePlan(ReadingPlan $plan, array $data): ReadingPlan
    {
        $plan->update([
            'target_date' => $data['target_date'],
        ]);

        return $plan;
    }
}
