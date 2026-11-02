<?php

namespace Tests\Feature\Notification;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザー自身の通知のみを一覧表示する。
     */
    public function test_user_sees_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 自分の読書計画
        $myPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        // 他人の読書計画
        $otherPlan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // それぞれのユーザーに通知を生成
        Notification::send(
            $user,
            new ReadingPlanReminderNotification($myPlan, 'on_due_date')
        );

        Notification::send(
            $otherUser,
            new ReadingPlanReminderNotification($otherPlan, 'on_due_date')
        );

        $myNotification = $user->notifications()->firstOrFail();

        // 自分のアカウントで通知一覧にアクセス
        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertViewIs('notifications.index')
            ->assertViewHas(
                'notifications',
                fn ($notifications) => $notifications->pluck('id')->all() === [$myNotification->id]
            );
    }

    /**
     * 通知を作成日時の新しい順に一覧表示する。
     */
    public function test_notifications_are_ordered_newest_first(): void
    {
        $user = User::factory()->create();

        $oldPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $newPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        // 新しい通知を先にDBへ保存
        $this->travelTo(
            Carbon::parse('2026-11-03 12:00:00', 'Asia/Tokyo')
        );

        Notification::send(
            $user,
            new ReadingPlanReminderNotification($newPlan, 'on_due_date')
        );

        // 古い通知を後からDBへ保存
        $this->travelTo(
            Carbon::parse('2026-11-02 12:00:00', 'Asia/Tokyo')
        );

        Notification::send(
            $user,
            new ReadingPlanReminderNotification($oldPlan, 'on_due_date')
        );

        // それぞれの通知IDを取得
        $newNotification = $user->notifications()
            ->where('data->reading_plan_id', $newPlan->id)
            ->firstOrFail();

        $oldNotification = $user->notifications()
            ->where('data->reading_plan_id', $oldPlan->id)
            ->firstOrFail();

        // 通知一覧にアクセス
        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertViewIs('notifications.index')
            ->assertViewHas(
                'notifications',
                fn ($notifications) => $notifications->pluck('id')->all() === [
                    $newNotification->id,
                    $oldNotification->id,
                ]
            );
    }

    /**
     * 通知が0件の場合、メッセージを表示する。
     */
    public function test_empty_notification_list_shows_message(): void
    {
        $user = User::factory()->create();

        // 自分のアカウントで通知一覧にアクセス
        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertViewIs('notifications.index')
            ->assertViewHas(
                'notifications',
                fn ($notifications) => $notifications->isEmpty()
            )
            ->assertSeeText('通知はありません。');
    }

    /**
     * 未認証ユーザーはログイン画面にリダイレクトされる。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 未読通知には未読ラベルと既読化ボタンを表示する。
     */
    public function test_unread_notification_shows_label_and_button(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        Notification::send(
            $user,
            new ReadingPlanReminderNotification($plan, 'on_due_date')
        );

        $notification = $user->notifications()->firstOrFail();

        // 通知は未読状態
        $this->assertNull($notification->read_at);

        // 通知一覧を表示
        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertSeeText('未読')
            ->assertSeeText('既読にする');
    }

    /**
     * 既読済み通知には未読ラベルと既読化ボタンを表示しない。
     */
    public function test_read_notification_hides_label_and_button(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        Notification::send(
            $user,
            new ReadingPlanReminderNotification($plan, 'on_due_date')
        );

        $notification = $user->notifications()->firstOrFail();

        // 通知を既読状態にする
        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);

        // 通知一覧を表示
        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk()
            ->assertSeeText($notification->data['title'])
            ->assertDontSeeText('未読')
            ->assertDontSeeText('既読にする');
    }
}
