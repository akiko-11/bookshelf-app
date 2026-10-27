<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    // いいねしたユーザーを取得できる
    public function test_review_like_belongs_to_user(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $likedUser = User::factory()->create();

        $reviewLike = ReviewLike::create([
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);

        $reviewLikeUser = $reviewLike->user;

        $this->assertTrue($reviewLikeUser->is($likedUser));
    }

    // いいね対象のレビューを取得できる
    public function test_review_like_belongs_to_review(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $likedUser = User::factory()->create();

        $reviewLike = ReviewLike::create([
            'user_id' => $likedUser->id,
            'review_id' => $review->id,
        ]);

        $reviewLikeReview = $reviewLike->review;

        $this->assertTrue($reviewLikeReview->is($review));
    }
}
