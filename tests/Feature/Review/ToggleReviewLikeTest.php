<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーは他ユーザーのレビューにいいねできる
    public function test_authenticated_user_can_like_other_users_review(): void
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

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review),
        );

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'いいねしました。'
        );
    }

    // 認証済みユーザーは他ユーザーのレビューのいいねを解除できる
    public function test_authenticated_user_can_unlike_other_users_review(): void
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

        $user->likedReviews()->attach($review->id);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review),
        );

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

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
}
