<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $bookIds = Book::inRandomOrder()
                ->limit(rand(3, 5))
                ->pluck('id');

            $user->favoriteBooks()
                ->syncWithoutDetaching($bookIds);
        }
    }
}
