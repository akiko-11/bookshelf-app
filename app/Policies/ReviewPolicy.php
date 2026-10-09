<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * ユーザーがレビューにいいねできるか判定する。
     */
    public function like(User $user, Review $review): bool
    {
        return $user->id !== $review->user_id;
    }
}
