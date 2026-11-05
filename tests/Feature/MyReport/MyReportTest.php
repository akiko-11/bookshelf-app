<?php

namespace Tests\Feature\MyReport;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーはマイ読書レポートにアクセスできない。
     */
    public function test_guest_cannot_access_my_report(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 認証済みユーザーはデータが0件でも
     * マイ読書レポートを正常に表示できる。
     */
    public function test_authenticated_user_can_view_my_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        // ① 画面が正常に表示される
        $response->assertOk();

        // ② データ0件の場合の集計結果
        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 0
                && $stats['summary']['books_read'] === 0
                && (float) $stats['summary']['average_rating'] === 0.0
                && collect($stats['rating_distribution'])
                    ->values()
                    ->all() === [0, 0, 0, 0, 0]
                && count($stats['top_rated_books']) === 0
                && count($stats['genre_ratings']) === 0;
        });

        // ③ データなしの場合の表示メッセージ
        $response->assertSee('4星以上の書籍がありません');
        $response->assertSee('ジャンルが設定された書籍のレビューがありません');

        // ④ 平均評価が「-」と表示される
        $response->assertSeeText('-');
    }

    /**
     * 高評価書籍は評価の降順、同点の場合はレビューIDの昇順で表示される。
     */
    public function test_top_rated_books_are_sorted_correctly(): void
    {
        $user = User::factory()->create();

        // 書籍IDはA → B → C → Dの順
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();
        $bookC = Book::factory()->create();
        $bookD = Book::factory()->create();

        // レビューは意図的に評価順・書籍ID順とは異なる順序で作成
        Review::factory()->for($user)->for($bookC)->create(['rating' => 4]);
        Review::factory()->for($user)->for($bookB)->create(['rating' => 5]);
        Review::factory()->for($user)->for($bookA)->create(['rating' => 5]);
        Review::factory()->for($user)->for($bookD)->create(['rating' => 3]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($bookA, $bookB, $bookC) {
            $actualBookIds = collect($stats['top_rated_books'])
                ->pluck('id')
                ->all();

            return $actualBookIds === [
                $bookB->id,
                $bookA->id,
                $bookC->id,
            ];
        });
    }

    /**
     * マイレポートにはログインユーザー自身のレビューのみを集計する。
     */
    public function test_my_report_only_aggregates_own_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 自分のレビューを2件作成（評価5）
        Review::factory()
            ->count(2)
            ->for($user)
            ->create(['rating' => 5]);

        // 他ユーザーのレビューを3件作成（評価1）
        Review::factory()
            ->count(3)
            ->for($otherUser)
            ->create(['rating' => 1]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 2
                && (float) $stats['summary']['average_rating'] === 5.0;
        });
    }

    /**
     * 高評価書籍は最大5件まで取得される。
     */
    public function test_top_rated_books_are_limited_to_five(): void
    {
        $user = User::factory()->create();

        // 評価5のレビューを6件作成（書籍はそれぞれ別）
        $reviews = Review::factory()
            ->count(6)
            ->for($user)
            ->create(['rating' => 5]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($reviews) {
            $actualBookIds = collect($stats['top_rated_books'])
                ->pluck('id')
                ->all();

            // レビューIDの昇順で先頭5件を期待
            $expectedBookIds = $reviews
                ->take(5)
                ->pluck('book_id')
                ->all();

            return count($actualBookIds) === 5
                && $actualBookIds === $expectedBookIds;
        });
    }

    /**
     * 評価分布はログインユーザー自身のレビューのみを集計する。
     */
    public function test_rating_distribution_only_counts_own_reviews(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 自分のレビュー
        foreach ([1, 1, 2, 4] as $rating) {
            Review::factory()->for($user)->create([
                'rating' => $rating,
            ]);
        }

        // 他ユーザーのレビュー
        Review::factory()
            ->count(2)
            ->for($otherUser)
            ->create(['rating' => 5]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $distribution = collect($stats['rating_distribution'])
                ->values()
                ->all();

            return $distribution === [2, 1, 0, 1, 0];
        });
    }

    /**
     * ジャンル別評価は複数ジャンルを集計し、
     * 他ユーザーのレビューを除外する。
     */
    public function test_genre_ratings_are_aggregated_correctly(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $novel = Genre::factory()->create(['name' => '小説']);
        $technical = Genre::factory()->create(['name' => '技術書']);

        // 書籍A：2つのジャンルに所属
        $bookA = Book::factory()->create();
        $bookA->genres()->attach([$novel->id, $technical->id]);

        Review::factory()->for($user)->for($bookA)->create([
            'rating' => 5,
        ]);

        // 書籍B：小説のみ
        $bookB = Book::factory()->create();
        $bookB->genres()->attach($novel->id);

        Review::factory()->for($user)->for($bookB)->create([
            'rating' => 3,
        ]);

        // 書籍C：他ユーザーのレビュー
        $bookC = Book::factory()->create();
        $bookC->genres()->attach($technical->id);

        Review::factory()->for($otherUser)->for($bookC)->create([
            'rating' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($novel, $technical) {
            $genres = collect($stats['genre_ratings']);

            $novelStats = $genres->firstWhere('id', $novel->id);
            $technicalStats = $genres->firstWhere('id', $technical->id);

            return $novelStats !== null
                && $technicalStats !== null
                && $novelStats['count'] === 2
                && (float) $novelStats['average_rating'] === 4.0
                && $technicalStats['count'] === 1
                && (float) $technicalStats['average_rating'] === 5.0
                && $genres->pluck('id')->all() === [
                    $technical->id,
                    $novel->id,
                ];
        });
    }

    /**
     * ジャンル別評価は最大5件とし、
     * 平均評価が同じ場合はレビュー件数の降順で表示する。
     */
    public function test_genre_ratings_are_limited_and_sorted_by_review_count(): void
    {
        $user = User::factory()->create();

        // 6つのジャンルを作成
        $genres = Genre::factory()->count(6)->create();

        // 各ジャンルに評価5のレビューを1件ずつ作成
        foreach ($genres as $genre) {
            $book = Book::factory()->create();
            $book->genres()->attach($genre->id);

            Review::factory()
                ->for($user)
                ->for($book)
                ->create(['rating' => 5]);
        }

        // 最後のジャンルだけレビューをもう1件追加
        $lastGenre = $genres->last();

        $extraBook = Book::factory()->create();
        $extraBook->genres()->attach($lastGenre->id);

        Review::factory()
            ->for($user)
            ->for($extraBook)
            ->create(['rating' => 5]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($lastGenre) {
            $genreRatings = collect($stats['genre_ratings'])->values();

            return $genreRatings->count() === 5
                && $genreRatings->first()['id'] === $lastGenre->id
                && $genreRatings->first()['count'] === 2
                && (float) $genreRatings->first()['average_rating'] === 5.0;
        });
    }

    /**
     * 読了冊数は自分の読了済み計画から、
     * 重複しない書籍数を集計する。
     */
    public function test_books_read_counts_unique_completed_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();
        $bookC = Book::factory()->create();
        $bookD = Book::factory()->create();

        // 書籍Aを2回読了
        ReadingPlan::factory()
            ->count(2)
            ->for($user)
            ->for($bookA)
            ->create([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        // 書籍Bを1回読了
        ReadingPlan::factory()
            ->for($user)
            ->for($bookB)
            ->create([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        // 書籍Cは進行中
        ReadingPlan::factory()
            ->for($user)
            ->for($bookC)
            ->create();

        // 書籍Dは期限切れ
        ReadingPlan::factory()
            ->for($user)
            ->for($bookD)
            ->create([
                'status' => ReadingPlanStatus::Expired,
                'target_date' => now()->subDay()->toDateString(),
            ]);

        // 他ユーザーの読了計画
        ReadingPlan::factory()
            ->for($otherUser)
            ->for(Book::factory()->create())
            ->create([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['books_read'] === 2;
        });
    }

    /**
     * 読了済みの読書計画や書籍を削除すると、
     * マイレポートの読了冊数が減る。
     */
    public function test_books_read_decreases_after_deleting_plan_or_book(): void
    {
        $user = User::factory()->create();

        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        // 書籍A・Bを読了済みにする
        $planA = ReadingPlan::factory()
            ->for($user)
            ->for($bookA)
            ->create([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($bookB)
            ->create([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        // ① 最初は2冊
        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['books_read'] === 2;
            });

        // ② 書籍Aの読書計画を削除 → 1冊
        $planA->delete();

        $this->get(route('reports.index'))
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['books_read'] === 1;
            });

        // ③ 書籍Bを削除 → 0冊
        $bookB->delete();

        $this->get(route('reports.index'))
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['books_read'] === 0;
            });
    }
}
