<?php

namespace Tests\Feature;

use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    // ゲストでも書籍詳細画面を表示できる
    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::factory()->create([
            'title' => 'テストの本',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertStatus(200);
        $response->assertSee('テストの本');
    }
}
