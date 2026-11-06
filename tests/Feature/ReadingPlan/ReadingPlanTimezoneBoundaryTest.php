<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Services\ReadingPlanExpirationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTimezoneBoundaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * JSTの日付変更直後、前日を期日に指定すると
     * 読書計画を登録できない。
     */
    public function test_past_target_date_is_rejected_after_midnight_in_japan(): void
    {
        $this->travelToJstMidnight();

        // JSTとUTCで日付が異なることを確認
        $this->assertSame(
            '2026-11-07',
            now('Asia/Tokyo')->toDateString()
        );

        $this->assertSame(
            '2026-11-06',
            now('UTC')->toDateString()
        );

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-11-06',
            ]);

        // JSTでは過去日付なのでバリデーションエラー
        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください',
        ]);

        // 読書計画が登録されていない
        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * JSTの日付変更直後、前日への期日変更は
     * バリデーションエラーになる。
     */
    public function test_past_target_date_cannot_be_updated_after_midnight_in_japan(): void
    {
        $this->travelToJstMidnight();

        $user = User::factory()->create();

        // 未来の期日を持つ進行中の計画
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-10',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-11-06',
            ]);

        // JSTでは前日なので更新不可
        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください',
        ]);

        // 更新前の期日が保持される
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => '2026-11-10',
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    /**
     * JSTで当日の日付なら登録できる。
     */
    public function test_today_in_japan_is_valid_target_date(): void
    {
        $this->travelToJstMidnight();

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '2026-11-07',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => '2026-11-07',
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    /**
     * JSTでは期日を過ぎた期限切れ計画を
     * 編集できない。
     */
    public function test_expired_plan_cannot_be_edited_at_jst_date_boundary(): void
    {
        $this->travelToJstMidnight();

        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-06',
            'status' => ReadingPlanStatus::Expired,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $plan));

        // JSTでは期日を過ぎているので403
        $response->assertForbidden();

        $response->assertSeeText(
            '期限切れの読書計画は編集できません。'
        );
    }

    /**
     * JSTの日付変更直後に失効処理を行うと、
     * 前日期限の計画が期限切れになる。
     */
    public function test_plan_expires_at_jst_date_boundary(): void
    {
        $this->travelToJstMidnight();

        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-06',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $service = app(ReadingPlanExpirationService::class);

        $updatedCount = $service->expireOverduePlans();

        // 期限切れ更新が1件実行される
        $this->assertSame(1, $updatedCount);

        // DBでも期限切れ状態になっている
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => '2026-11-06',
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }

    /**
     * 期日を過ぎた期限切れ計画には編集リンクを表示せず、
     * 編集可能な進行中の計画には表示する。
     */
    public function test_expired_plan_does_not_show_edit_link(): void
    {
        $this->travelToJstMidnight();

        $user = User::factory()->create();

        $expiredPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-06',
            'status' => ReadingPlanStatus::Expired,
        ]);

        $activePlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-10',
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();

        // 期限切れ計画は一覧に存在する
        $response->assertSeeText($expiredPlan->book->title);

        // 期限切れ計画の編集リンクは表示しない
        $response->assertDontSee(
            'href="'.e(route('reading-plans.edit', $expiredPlan)).'"',
            false
        );

        // 編集可能な進行中の計画にはリンクを表示する
        $response->assertSee(
            'href="'.e(route('reading-plans.edit', $activePlan)).'"',
            false
        );
    }

    /**
     * JSTで期日を過ぎた期限切れ計画は、
     * 未来の日付を指定しても更新できない。
     */
    public function test_expired_plan_cannot_be_updated_at_jst_date_boundary(): void
    {
        $this->travelToJstMidnight();

        $user = User::factory()->create();

        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2026-11-06',
            'status' => ReadingPlanStatus::Expired,
        ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $plan), [
                'target_date' => '2026-11-10',
            ]);

        // 期限切れ計画の更新は認可エラー
        $response->assertForbidden();

        // DBの期日とステータスが変更されていない
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => '2026-11-06',
            'status' => ReadingPlanStatus::Expired->value,
        ]);
    }

    /**
     * JST 2026-11-07 00:30にテスト時刻を固定する。
     * 同じ瞬間のUTCは2026-11-06 15:30。
     */
    private function travelToJstMidnight(): void
    {
        $this->travelTo(
            Carbon::parse('2026-11-07 00:30:00', 'Asia/Tokyo')
        );
    }
}
