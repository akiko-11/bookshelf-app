<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍一覧からいいねを追加した場合、
     * ページ番号と検索条件が保持される。
     */
    public function test_like_preserves_book_list_search_conditions(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $params = [
            'from' => 'books',
            'page' => 2,
            'keyword' => 'Laravel',
            'genre' => 1,
            'sort' => 'rating',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review), $params);

        // いいねが追加される
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 書籍詳細画面への遷移時に検索条件を保持
        $response->assertRedirect(
            route('books.show', [
                'book' => $book,
                ...$params,
            ])
        );

        $response->assertSessionHas(
            'success',
            'いいねしました。'
        );
    }

    /**
     * 書籍一覧からいいねを解除した場合も、
     * ページ番号と検索条件が保持される。
     */
    public function test_unlike_preserves_book_list_search_conditions(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        // 事前にいいねを登録
        $user->likedReviews()->attach($review->id);

        $params = [
            'from' => 'books',
            'page' => 2,
            'keyword' => 'Laravel',
            'genre' => 1,
            'sort' => 'rating',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review), $params);

        // いいねが解除される
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // いいね解除後も検索条件を保持
        $response->assertRedirect(
            route('books.show', [
                'book' => $book,
                ...$params,
            ])
        );

        $response->assertSessionHas(
            'success',
            'いいねを解除しました。'
        );
    }

    // 認証済みユーザーは自分のレビューにはいいねできない
    public function test_authenticated_user_cannot_like_own_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review),
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    // 未認証ユーザーはレビューにいいねできない
    public function test_guest_cannot_like_review(): void
    {
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $response = $this->post(
            route('reviews.like', $review),
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }

    /**
     * いいね前のフォームに書籍一覧の検索条件が設定される。
     */
    public function test_like_form_preserves_book_list_search_conditions(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();
        $genre = Genre::create(['name' => 'テストジャンル']);

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $params = [
            'from' => 'books',
            'page' => 2,
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'rating',
        ];

        $response = $this->actingAs($user)
            ->get(route('books.show', [
                'book' => $book,
                ...$params,
            ]));

        $response->assertOk();
        $response->assertSeeText('いいね (0)');

        // いいねフォームに検索条件が含まれている
        $this->assertLikeFormHasParameters(
            $response->getContent(),
            $review,
            $params
        );
    }

    /**
     * いいね解除用フォームにも書籍一覧の検索条件が設定される。
     */
    public function test_unlike_form_preserves_book_list_search_conditions(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();
        $genre = Genre::create(['name' => 'テストジャンル']);

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        // いいね済みの状態にする
        $user->likedReviews()->attach($review->id);

        $params = [
            'from' => 'books',
            'page' => 2,
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'rating',
        ];

        $response = $this->actingAs($user)
            ->get(route('books.show', [
                'book' => $book,
                ...$params,
            ]));

        $response->assertOk();
        $response->assertSeeText('いいね済み (1)');

        // いいね解除用フォームにも検索条件が含まれている
        $this->assertLikeFormHasParameters(
            $response->getContent(),
            $review,
            $params
        );
    }

    /**
     * いいね後の書籍詳細画面で、
     * 「一覧に戻る」リンクが元の検索条件を保持する。
     */
    public function test_back_link_preserves_book_list_search_conditions_after_like(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();
        $genre = Genre::create(['name' => 'テストジャンル']);

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $params = [
            'from' => 'books',
            'page' => 2,
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'rating',
        ];

        // いいねを追加
        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review), $params);

        $detailUrl = route('books.show', [
            'book' => $book,
            ...$params,
        ]);

        $response->assertRedirect($detailUrl);

        // リダイレクト先の書籍詳細画面を表示
        $detailResponse = $this->get($detailUrl);

        $detailResponse->assertOk();

        // 検索条件付きの書籍一覧URL
        $backUrl = route('books.index', [
            'page' => 2,
            'keyword' => 'Laravel',
            'genre' => $genre->id,
            'sort' => 'rating',
        ]);

        // 「一覧に戻る」のリンク先が正しい
        $detailResponse->assertSee(
            'href="'.e($backUrl).'"',
            false
        );
    }

    /**
     * 書籍一覧以外からのいいね操作でも、
     * 既存の遷移元情報が保持される。
     */
    public function test_like_preserves_other_navigation_origins(): void
    {
        $user = User::factory()->create();
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();
        $genre = Genre::create(['name' => 'テストジャンル']);

        $review = Review::create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $cases = [
            [
                'from' => 'favorites',
                'page' => 2,
            ],
            [
                'from' => 'genre',
                'genre_id' => $genre->id,
                'page' => 2,
            ],
            [
                'from' => 'reading-plans',
                'status' => 'expired',
            ],
            [
                'from' => 'reports',
            ],
            [
                'from' => 'ranking',
            ],
        ];

        foreach ($cases as $params) {
            $response = $this->actingAs($user)
                ->post(route('reviews.like', $review), $params);

            $response->assertRedirect(
                route('books.show', [
                    'book' => $book,
                    ...$params,
                ])
            );
        }
    }

    /**
     * レビューいいねフォーム内のhidden inputを検証する。
     *
     * 他のフォームではなく、対象レビューのいいねフォームを
     * 抽出して検証する。
     */
    private function assertLikeFormHasParameters(
        string $html,
        Review $review,
        array $params
    ): void {
        $action = preg_quote(route('reviews.like', $review), '~');

        preg_match_all(
            '~<form\b[^>]*action="'.$action.'"[^>]*>.*?</form>~s',
            $html,
            $matches
        );

        // 対象レビューのいいねフォームが1つ存在する
        $this->assertCount(1, $matches[0]);

        $formHtml = $matches[0][0];

        foreach ($params as $key => $value) {
            // hidden inputに値が設定されている
            $this->assertStringContainsString(
                'name="'.$key.'" value="'.e((string) $value).'"',
                $formHtml
            );

            // 同じnameのinputが重複していない
            $this->assertSame(
                1,
                substr_count($formHtml, 'name="'.$key.'"')
            );
        }
    }
}
