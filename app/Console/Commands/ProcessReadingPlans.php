<?php

namespace App\Console\Commands;

use App\Services\ReadingPlanExpirationService;
use Illuminate\Console\Command;

class ProcessReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期限切れ更新とリマインダー通知を実行する';

    /**
     * 読書計画の期限切れ更新とリマインダー通知を実行する。
     *
     * @param  ReadingPlanExpirationService  $service  期限切れ処理サービス
     * @return int コマンドの終了コード
     */
    public function handle(ReadingPlanExpirationService $service): int
    {
        // 期限を過ぎた読書計画を更新
        $expiredCount = $service->expireOverduePlans();

        // リマインダー通知を生成
        $sentCount = $service->sendReminders();

        $this->info("期限切れ更新：{$expiredCount}件");
        $this->info("通知生成：{$sentCount}件");

        return self::SUCCESS;
    }
}
