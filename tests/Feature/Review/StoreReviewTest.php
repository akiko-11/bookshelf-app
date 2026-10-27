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
}
