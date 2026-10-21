<?php

namespace Tests\Feature\Ranking;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankIndexTest extends TestCase
{
    use RefreshDatabase;

    // ゲストがランキング画面を閲覧でき、平均評価の高い順に表示される
    public function test_guest_can_view_ranking(): void
    {
        // 評価の低い書籍を先に作成
        $this->createBookWithReviews('書籍B', [3]);
        $this->createBookWithReviews('書籍A', [5]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['書籍A', '書籍B']);
    }

    // 認証済みユーザーがランキング画面を閲覧でき、平均評価の高い順に表示される
    public function test_authenticated_user_can_view_ranking(): void
    {
        $user = User::factory()->create();

        // 評価の低い書籍を先に作成
        $this->createBookWithReviews('書籍B', [3]);
        $this->createBookWithReviews('書籍A', [5]);

        $response = $this->actingAs($user)->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['書籍A', '書籍B']);
    }

    // レビューがない書籍はランキングに表示されない
    public function test_book_without_reviews_is_excluded_from_ranking(): void
    {
        $this->createBookWithReviews('書籍B', [3]);
        $this->createBookWithReviews('書籍A', [5]);

        Book::factory()->create([
            'title' => '書籍C',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['書籍A', '書籍B']);
        $response->assertDontSee('書籍C');
    }

    // ランキングが最大10冊まで表示される
    public function test_ranking_shows_top_10_books(): void
    {
        $books = Book::factory()->count(11)->create();

        foreach ($books as $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertViewHas('rankedBooks', function ($rankedBooks) {
            return $rankedBooks->count() === 10;
        });
    }

    // 平均評価とレビュー件数が正しく表示される
    public function test_ranking_displays_average_rating_and_review_count(): void
    {
        $this->createBookWithReviews('書籍B', [3, 1, 2]);
        $this->createBookWithReviews('書籍A', [5, 4]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['書籍A', '書籍B']);

        // 平均評価
        $response->assertSee('4.50');
        $response->assertSee('2.00');

        // レビュー件数
        $response->assertSee('(2件のレビュー)');
        $response->assertSee('(3件のレビュー)');
    }

    // 平均評価が同じ場合、1位・1位・3位と表示される
    public function test_books_with_same_average_rating_share_rank(): void
    {
        $this->createBookWithReviews('書籍A', [5, 5]);
        $this->createBookWithReviews('書籍B', [5]);
        $this->createBookWithReviews('書籍C', [4]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);

        $response->assertSeeInOrder([
            'data-rank="1"',
            '書籍A',
            'data-rank="1"',
            '書籍B',
            'data-rank="3"',
            '書籍C',
        ], false);
    }

    // レビューが1件もない場合、案内メッセージが表示される
    public function test_ranking_shows_message_when_no_reviews_exist(): void
    {
        Book::factory()->create([
            'title' => '書籍A',
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSee('まだレビューが投稿された書籍がありません。');
        $response->assertDontSee('書籍A');
    }

    // ランキングから書籍詳細画面へのリンクが表示される
    public function test_ranking_displays_book_detail_links(): void
    {
        $bookA = $this->createBookWithReviews('書籍A', [5]);
        $bookB = $this->createBookWithReviews('書籍B', [4]);

        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);

        $response->assertSee(
            'href="'.route('books.show', $bookA).'"',
            false
        );

        $response->assertSee(
            'href="'.route('books.show', $bookB).'"',
            false
        );
    }

    /**
     * 指定した書籍とレビューを作成する。
     *
     * @param  string  $title  書籍タイトル
     * @param  array  $ratings  レビュー評価の配列
     * @return Book 作成した書籍
     */
    private function createBookWithReviews(string $title, array $ratings): Book
    {
        $book = Book::factory()->create([
            'title' => $title,
        ]);

        foreach ($ratings as $rating) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        return $book;
    }
}
