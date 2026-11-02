<?php

namespace App\Services;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;

class ReadingPlanExpirationService
{
    /**
     * 期日を過ぎた進行中の読書計画を期限切れに更新する。
     *
     * @return int 更新された読書計画の件数
     */
    public function expireOverduePlans(): int
    {
        return ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->where('target_date', '<', today('Asia/Tokyo')->toDateString())
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);
    }

    /**
     * 読書計画のリマインダー通知を生成する。
     *
     * @return int 生成した通知件数
     */
    public function sendReminders(): int
    {
        $sentCount = 0;

        $today = today('Asia/Tokyo');

        $timings = [
            'before_3_days' => $today->copy()->addDays(3)->toDateString(),
            'on_due_date' => $today->toDateString(),
            'after_3_days' => $today->copy()->subDays(3)->toDateString(),
        ];

        foreach ($timings as $timing => $targetDate) {
            $query = ReadingPlan::query()
                ->with(['user', 'book'])
                ->where('target_date', $targetDate);

            if ($timing === 'after_3_days') {
                // 3日後は進行中・期限切れが対象
                $query->whereIn('status', [
                    ReadingPlanStatus::InProgress->value,
                    ReadingPlanStatus::Expired->value,
                ]);
            } else {
                // 3日前・当日は進行中のみ
                $query->where(
                    'status',
                    ReadingPlanStatus::InProgress->value
                );
            }

            $plans = $query->get();

            foreach ($plans as $plan) {
                $alreadyNotified = $plan->user->notifications()
                    ->where('data->reading_plan_id', $plan->id)
                    ->where('data->target_date', $targetDate)
                    ->where('data->timing', $timing)
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                $plan->user->notify(
                    new ReadingPlanReminderNotification($plan, $timing)
                );

                $sentCount++;
            }
        }

        return $sentCount;
    }
}
