<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateReviewValidationTest extends TestCase
{
    use RefreshDatabase;

    // 評価が未選択の場合、バリデーションメッセージが表示される
    public function test_rating_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'comment' => '更新しました。',
            ]
        );

        $response->assertSessionHasErrors([
            'rating' => '評価を選択してください',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '更新しました。',
        ]);
    }

    // 評価が整数でない場合、バリデーションメッセージが表示される
    public function test_rating_must_be_integer(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 'abc',
                'comment' => '更新しました。',
            ]
        );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の整数を選択してください',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '更新しました。',
        ]);
    }

    // 評価が1未満の場合、バリデーションメッセージが表示される
    public function test_rating_must_not_be_less_than_1(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 0,
                'comment' => '更新しました。',
            ]
        );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '更新しました。',
        ]);
    }

    // 評価が1の場合、レビューを更新できる
    public function test_rating_can_be_1(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 1,
                'comment' => '更新しました。',
            ]
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 1,
            'comment' => '更新しました。',
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    // 評価が5の場合、レビューを更新できる
    public function test_rating_can_be_5(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => '更新しました。',
            ]
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新しました。',
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    // 評価が5を超える場合、バリデーションメッセージが表示される
    public function test_rating_must_not_exceed_5(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 6,
                'comment' => '更新しました。',
            ]
        );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => '更新しました。',
        ]);
    }

    // コメントが未入力の場合、バリデーションメッセージが表示される
    public function test_comment_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 3,
            ]
        );

        $response->assertSessionHasErrors([
            'comment' => 'コメントを入力してください',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);
    }

    // コメントが文字列でない場合、バリデーションメッセージが表示される
    public function test_comment_must_be_string(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 3,
                'comment' => ['a'],
            ]
        );

        $response->assertSessionHasErrors([
            'comment' => 'コメントは文字列で入力してください',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);
    }

    // コメントが1000文字の場合、レビューを更新できる
    public function test_comment_can_be_1000_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 1,
                'comment' => str_repeat('あ', 1000),
            ]
        );

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 1,
            'comment' => str_repeat('あ', 1000),
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    // コメントが1000文字を超える場合、バリデーションメッセージが表示される
    public function test_comment_must_not_exceed_1000_characters(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $comment = str_repeat('あ', 1001);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 1,
                'comment' => $comment,
            ]
        );

        $response->assertSessionHasErrors([
            'comment' => 'コメントは1000文字以内で入力してください',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'comment' => $comment,
        ]);
    }
}
