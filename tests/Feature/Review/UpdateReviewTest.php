<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateReviewTest extends TestCase
{
    use RefreshDatabase;

    // 投稿者本人はレビューを更新できる
    public function test_owner_can_update_review(): void
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
                'rating' => 3,
                'comment' => '更新しました。',
            ]
        );

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新しました。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );
    }

    // 他ユーザーはレビューを更新できない
    public function test_other_user_cannot_update_review(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $targetUser->id,
            'book_id' => $book->id,
        ]);

        $original = $review->fresh()->getAttributes();

        $response = $this->actingAs($otherUser)->put(
            route('reviews.update', $review),
            [
                'rating' => 3,
                'comment' => '更新しました。',
            ]
        );

        $response->assertForbidden();

        $this->assertSame($original, $review->fresh()->getAttributes());
    }

    // 未認証ユーザーはレビューを更新できない
    public function test_guest_cannot_update_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $original = $review->fresh()->getAttributes();

        $response = $this->put(
            route('reviews.update', $review),
            [
                'rating' => 3,
                'comment' => '更新しました。',
            ]
        );

        $response->assertRedirect(route('login'));

        $this->assertSame($original, $review->fresh()->getAttributes());
    }

    // 他ユーザーが不正な入力でレビューを更新しようとしても403になる
    public function test_other_user_cannot_update_review_with_invalid_data(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $targetUser->id,
            'book_id' => $book->id,
        ]);

        $original = $review->fresh()->getAttributes();

        $response = $this->actingAs($otherUser)->put(
            route('reviews.update', $review),
            [
                'rating' => 0,
                'comment' => '',
            ]
        );

        $response->assertForbidden();

        $this->assertSame($original, $review->fresh()->getAttributes());
    }
}
