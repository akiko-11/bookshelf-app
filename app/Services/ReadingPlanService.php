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
     * @return Collection<int, ReadingPlan>
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
}
