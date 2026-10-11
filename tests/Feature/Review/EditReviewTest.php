<?php

namespace Tests\Feature\Review;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditReviewTest extends TestCase
{
    use RefreshDatabase;

    // 投稿者本人はレビュー編集画面を表示できる
    public function test_owner_can_view_review_edit_page(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても良い本でした。',
        ]);

        $response = $this->actingAs($user)->get(
            route('reviews.edit', $review),
        );

        $response->assertStatus(200);

        $response->assertSee('とても良い本でした。');
    }

    // 他ユーザーはレビュー編集画面を表示できない
    public function test_other_user_cannot_view_review_edit_page(): void
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $targetUser->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($otherUser)->get(
            route('reviews.edit', $review),
        );

        $response->assertForbidden();
    }

    // 未認証ユーザーはレビュー編集画面を表示できない
    public function test_guest_cannot_view_review_edit_page(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->get(
            route('reviews.edit', $review),
        );

        $response->assertRedirect(route('login'));
    }

    // レビュー投稿済みの場合、新規レビュー投稿欄が表示されない
    public function test_review_form_is_hidden_when_user_has_already_reviewed_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->get(
            route('books.show', $book),
        );

        $response->assertStatus(200);

        $response->assertDontSee('レビューを投稿');
    }
}
