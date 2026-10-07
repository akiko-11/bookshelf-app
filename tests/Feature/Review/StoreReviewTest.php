<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreReviewTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーはレビューを投稿できる
    public function test_authenticated_user_can_store_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 3,
                'comment' => 'とても良い本でした。',
            ]
        );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => 'とても良い本でした。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );
    }

    // 未認証ユーザーはレビューを投稿できない
    public function test_guest_cannot_store_review(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => 'とても良い本でした。',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'comment' => 'とても良い本でした。',
        ]);
    }

    // 評価が未選択の場合はバリデーションエラーになる
    public function test_rating_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'comment' => 'とても良い本でした。',
                ]
            );

        $response->assertSessionHasErrors([
            'rating' => '評価を選択してください',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    // 評価が整数でない場合はバリデーションエラーになる
    public function test_rating_must_be_integer(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 'wrong',
                    'comment' => 'とても良い本でした。',
                ]
            );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の整数を選択してください',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    // 評価が1未満の場合はバリデーションエラーになる
    public function test_rating_must_not_be_less_than_1(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 0,
                    'comment' => 'とても難しい本でした。',
                ]
            );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    // 評価が1の場合はレビューを投稿できる
    public function test_rating_can_be_1(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 1,
                    'comment' => 'とても難しい本でした。',
                ]
            );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 1,
            'comment' => 'とても難しい本でした。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );
    }

    // 評価が5の場合はレビューを投稿できる
    public function test_rating_can_be_5(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 5,
                    'comment' => 'とても良い本でした。',
                ]
            );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );
    }

    // 評価が5を超える場合はバリデーションエラーになる
    public function test_rating_must_not_exceed_5(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 6,
                    'comment' => 'とても良い本でした。',
                ]
            );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    // コメントが未入力の場合はバリデーションエラーになる
    public function test_comment_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 5,
                ]
            );

        $response->assertSessionHasErrors([
            'comment' => 'コメントを入力してください',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    // コメントが文字列でない場合はバリデーションエラーになる
    public function test_comment_must_be_string(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 5,
                    'comment' => ['a'],
                ]
            );

        $response->assertSessionHasErrors([
            'comment' => 'コメントは文字列で入力してください',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    // コメントが1000文字を超える場合はバリデーションエラーになる
    public function test_comment_must_not_exceed_1000_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 5,
                    'comment' => str_repeat('あ', 1001),
                ]
            );

        $response->assertSessionHasErrors([
            'comment' => 'コメントは1000文字以内で入力してください',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    // コメントが1000文字の場合はレビューを投稿できる
    public function test_comment_can_be_1000_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 5,
                    'comment' => str_repeat('あ', 1000),
                ]
            );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => str_repeat('あ', 1000),
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );
    }
}
