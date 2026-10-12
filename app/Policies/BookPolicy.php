<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * ユーザーが書籍を編集できるか判定する。
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
