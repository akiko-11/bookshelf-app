<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReadingPlanPolicy
{
    /**
     * 読書計画の編集・更新権限を判定する。
     *
     * @param  User  $user  ログインユーザー
     * @param  ReadingPlan  $readingPlan  対象の読書計画
     * @return Response 認可結果
     */
    public function update(User $user, ReadingPlan $readingPlan): Response
    {
        // 所有者以外は編集不可
        if ($user->id !== $readingPlan->user_id) {
            return Response::deny('この読書計画を操作する権限がありません。');
        }

        // 完了済み計画は編集不可
        if ($readingPlan->status === ReadingPlanStatus::Completed) {
            return Response::deny('完了済みの読書計画は編集できません。');
        }

        // 期限切れの計画で、期日が日本時間の今日より前の場合は編集不可
        if (
            $readingPlan->status === ReadingPlanStatus::Expired
            && $readingPlan->target_date->toDateString()
                < today('Asia/Tokyo')->toDateString()
        ) {
            return Response::deny('期限切れの読書計画は編集できません。');
        }

        return Response::allow();
    }

    /**
     * 読書計画の読了操作権限を判定する。
     *
     * @param  User  $user  ログインユーザー
     * @param  ReadingPlan  $readingPlan  対象の読書計画
     * @return Response 認可結果
     */
    public function complete(User $user, ReadingPlan $readingPlan): Response
    {
        if ($user->id !== $readingPlan->user_id) {
            return Response::deny('この読書計画を操作する権限がありません。');
        }

        return Response::allow();
    }

    /**
     * 指定された読書計画の削除を認可する。
     *
     * @param  User  $user  操作ユーザー
     * @param  ReadingPlan  $readingPlan  削除対象の読書計画
     * @return Response 認可結果
     */
    public function delete(User $user, ReadingPlan $readingPlan): Response
    {
        if ($user->id !== $readingPlan->user_id) {
            return Response::deny('この読書計画を操作する権限がありません。');
        }

        return Response::allow();
    }
}
