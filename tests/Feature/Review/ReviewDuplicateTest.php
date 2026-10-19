<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDuplicateTest extends TestCase
{
    use RefreshDatabase;

    // 同一ユーザーは同一書籍への重複投稿できない
    public function test_user_cannot_submit_second_review_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 5,
                    'comment' => '重複のレビュー',
                ]
            );

        $response->assertSessionHasErrors('review');

        $this->assertDatabaseCount('reviews', 1);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    // 別ユーザーなら同じ書籍に投稿できる
    public function test_different_users_can_review_same_book(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $targetUser->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->post(
                route('reviews.store', $book),
                [
                    'rating' => 5,
                    'comment' => '別のユーザーのレビュー',
                ]
            );

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseCount('reviews', 2);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '別のユーザーのレビュー',
        ]);
    }

    // 同じユーザーでも別書籍なら投稿できる
    public function test_user_can_review_different_books(): void
    {
        $user = User::factory()->create();
        $targetBook = Book::factory()->create();
        $otherBook = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $targetBook->id,
        ]);

        $response = $this->actingAs($user)
            ->post(
                route('reviews.store', $otherBook),
                [
                    'rating' => 5,
                    'comment' => '別書籍のレビュー',
                ]
            );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('books.show', $otherBook));

        $this->assertDatabaseCount('reviews', 2);

        // 既存レビューが保持されていること
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $targetBook->id,
        ]);

        // 別書籍へのレビューが登録されたこと
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $otherBook->id,
            'rating' => 5,
            'comment' => '別書籍のレビュー',
        ]);
    }

    // 投稿済みの場合、フォームを非表示にする
    public function test_review_form_is_hidden_when_user_has_already_reviewed_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('books.show', $book));

        $response->assertDontSee('レビューを投稿');
        $response->assertDontSee('name="rating"', false);
    }

    // 未投稿の場合、フォームを表示にする
    public function test_review_form_is_visible_when_user_has_not_reviewed_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('レビューを投稿');
        $response->assertSee('name="rating"', false);
    }
}
