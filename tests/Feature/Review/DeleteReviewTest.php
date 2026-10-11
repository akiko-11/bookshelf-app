<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteReviewTest extends TestCase
{
    use RefreshDatabase;

    // 投稿者本人はレビューを削除できる
    public function test_owner_can_delete_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('reviews.destroy', $review),
        );

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

    }

    // レビュー削除後に書籍詳細画面へ遷移する
    public function test_review_delete_redirects_to_book_detail_with_success_message(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('reviews.destroy', $review),
        );

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );
    }

    // 他ユーザーはレビューを削除できない
    public function test_other_user_cannot_delete_review(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $targetUser->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($otherUser)->delete(
            route('reviews.destroy', $review),
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    // 未認証ユーザーはレビューを削除できない
    public function test_guest_cannot_delete_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->delete(
            route('reviews.destroy', $review),
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    // レビュー削除時に関連するいいねも削除される
    public function test_review_likes_are_deleted_when_review_is_deleted(): void
    {
        $user = User::factory()->create();
        $likedUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $likedUser->likedReviews()->attach($review->id);

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('reviews.destroy', $review),
        );

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);
    }
}
