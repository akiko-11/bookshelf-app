<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バッチ実行時に期限切れ更新と通知生成が行われる
     */
    public function test_command_expires_plans_and_sends_reminders(): void
    {
        // バッチ実行日時を日本時間20:00に固定
        $this->travelTo(
            Carbon::parse('2026-11-02 20:00:00', 'Asia/Tokyo')
        );

        $user = User::factory()->create();

        // 期限切れ更新の対象
        $overduePlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-01',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 3日前通知の対象
        $reminderPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-05',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Artisan Commandを実行
        $this->artisan('reading-plans:process')
            ->expectsOutput('期限切れ更新：1件')
            ->expectsOutput('通知生成：1件')
            ->assertExitCode(0);

        // 期限切れに更新されている
        $this->assertSame(
            ReadingPlanStatus::Expired,
            $overduePlan->fresh()->status
        );

        // 未来の計画は進行中のまま
        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $reminderPlan->fresh()->status
        );

        // 通知が1件生成されている
        $this->assertDatabaseCount('notifications', 1);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->reading_plan_id' => $reminderPlan->id,
            'data->target_date' => '2026-11-05',
            'data->timing' => 'three_days_before',
        ]);
    }
}
