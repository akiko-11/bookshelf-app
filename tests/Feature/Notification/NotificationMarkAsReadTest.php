<?php

namespace Tests\Feature\Notification;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationMarkAsReadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーは自分の未読通知を既読にできる。
     */
    public function test_user_can_mark_own_notification_as_read(): void
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

        // 実行前は未読
        $this->assertNull($notification->read_at);

        // 既読化を実行
        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        // 通知一覧へ戻り、成功メッセージを表示
        $response->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', '通知を既読にしました。');

        // DBに既読日時が保存されている
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * 既読済みの通知を再度既読化しても既読日時を維持する。
     */
    public function test_already_read_notification_remains_read(): void
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

        // 1回目の既読化
        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $firstReadAt = $notification->fresh()->read_at;

        $this->assertNotNull($firstReadAt);

        // 1日後に再度既読化する
        $this->travelTo($firstReadAt->copy()->addDay());

        $response = $this->post(
            route('notifications.read', $notification->id)
        );

        // エラーにならず通知一覧へ戻る
        $response->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success', '通知を既読にしました。');

        // 初回の既読日時が維持されている
        $this->assertEquals(
            $firstReadAt,
            $notification->fresh()->read_at
        );
    }

    /**
     * 他人の通知を既読化しようとすると403を返す。
     */
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 他人の読書計画を作成
        $plan = ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // 他人の通知を生成
        Notification::send(
            $otherUser,
            new ReadingPlanReminderNotification($plan, 'on_due_date')
        );

        $notification = $otherUser->notifications()->firstOrFail();

        // 別のユーザーで他人の通知を既読化しようとする
        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        // 403エラーが返る
        $response->assertForbidden()
            ->assertSeeText('この通知を操作する権限がありません。');

        // 他人の通知は未読のまま
        $this->assertNull($notification->fresh()->read_at);
    }

    /**
     * 存在しない通知IDを既読化しようとすると404を返す。
     */
    public function test_nonexistent_notification_returns_404(): void
    {
        $user = User::factory()->create();

        // 存在しない通知ID
        $notificationId = '00000000-0000-0000-0000-000000000000';

        // 存在しない通知に対して既読化を実行
        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notificationId));

        // 404エラーが返る
        $response->assertNotFound()
            ->assertSeeText('通知が見つかりません。');
    }
}
