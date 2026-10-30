<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 読書計画から作成者を取得できる。
     */
    public function test_reading_plan_belongs_to_user(): void
    {
        $plan = ReadingPlan::factory()->create();

        $user = $plan->user;

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($user->id, $plan->user_id);
    }

    /**
     * 読書計画から対象書籍を取得できる。
     */
    public function test_reading_plan_belongs_to_book(): void
    {
        $plan = ReadingPlan::factory()->create();

        $book = $plan->book;

        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals($book->id, $plan->book_id);
    }

    /**
     * ユーザーから作成した読書計画を取得できる。
     */
    public function test_user_has_many_reading_plans(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $readingPlans = $user->readingPlans;

        $this->assertCount(1, $readingPlans);
        $this->assertTrue($readingPlans->contains($plan->id));
    }

    /**
     * 書籍から関連する読書計画を取得できる。
     */
    public function test_book_has_many_reading_plans(): void
    {
        $book = Book::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'book_id' => $book->id,
        ]);

        $readingPlans = $book->readingPlans;

        $this->assertCount(1, $readingPlans);
        $this->assertTrue($readingPlans->contains($plan->id));
    }

    /**
     * 読書計画の状態がEnumとして取得できる。
     */
    public function test_status_is_cast_to_enum(): void
    {
        $plan = ReadingPlan::factory()->create();

        $plan->refresh();

        // EloquentではEnumとして取得できる
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);

        // DBには文字列として保存されている
        $this->assertSame('in_progress', $plan->getRawOriginal('status'));
    }

    /**
     * 読書期限が日付型にキャストされる。
     */
    public function test_target_date_is_cast_to_date(): void
    {
        $plan = ReadingPlan::factory()->create([
            'target_date' => '2026-11-08',
        ]);

        $plan->refresh();

        $this->assertInstanceOf(Carbon::class, $plan->target_date);
        $this->assertSame('2026-11-08', $plan->target_date->format('Y-m-d'));
    }

    /**
     * 読了日時が日時型にキャストされる。
     */
    public function test_completed_at_is_cast_to_datetime(): void
    {
        $plan = ReadingPlan::factory()->create([
            'completed_at' => '2026-11-08 15:30:00',
        ]);

        $plan->refresh();

        $this->assertInstanceOf(Carbon::class, $plan->completed_at);
        $this->assertSame(
            '2026-11-08 15:30:00',
            $plan->completed_at->format('Y-m-d H:i:s')
        );
    }

    /**
     * 未読了の読書計画では読了日時がNULLになる。
     */
    public function test_completed_at_is_null_when_not_completed(): void
    {
        $plan = ReadingPlan::factory()->create();

        $plan->refresh();

        $this->assertNull($plan->completed_at);
    }
}
