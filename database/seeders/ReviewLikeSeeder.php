<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $userIds = User::where('id', '!=', $review->user_id)
                ->inRandomOrder()
                ->limit(rand(0, 3))
                ->pluck('id');

            $review->likedUsers()
                ->syncWithoutDetaching($userIds);
        }
    }
}
