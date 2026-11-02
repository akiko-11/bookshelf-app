<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    /**
     * 通知インスタンスを生成する。
     *
     * @param  ReadingPlan  $plan  対象の読書計画
     * @param  string  $timing  通知タイミング
     */
    public function __construct(
        private ReadingPlan $plan,
        private string $timing
    ) {}

    /**
     * 通知の配信チャネルを取得する。
     *
     * @return array<int, string> 配信チャネル
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * DBに保存する通知データを取得する。
     *
     * @return array<string, mixed> 通知データ
     */
    public function toArray(object $notifiable): array
    {
        $bookTitle = $this->plan->book->title;
        $targetDate = $this->plan->target_date->format('Y-m-d');

        $messages = [
            'three_days_before' => '期日まであと3日です。',
            'on_due_date' => '今日は読書計画の期日です。',
            'three_days_after' => '期日から3日が経過しました。',
        ];

        return [
            'reading_plan_id' => $this->plan->id,
            'target_date' => $targetDate,
            'timing' => $this->timing,
            'title' => "読書計画のお知らせ：{$bookTitle}（{$targetDate}）",
            'body' => "「{$bookTitle}」の期日（{$targetDate}）：{$messages[$this->timing]}",
        ];
    }
}
