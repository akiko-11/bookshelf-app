<?php

namespace Tests\Feature\Genre;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがジャンル一覧を表示できる
    public function test_authenticated_user_can_view_genres_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);
    }

    // 未認証ユーザーはログイン画面へリダイレクトされる
    public function test_guest_cannot_view_genres_index(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }

    // 登録済みジャンル名が表示される
    public function test_authenticated_user_can_view_registered_genres(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertSee($genre->name);
    }

    // ジャンルに紐づく書籍数が正しく表示される
    public function test_genre_displays_correct_book_count(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create();
        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $genre->books()->attach($books->pluck('id'));

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertSee('2冊');
    }

    // ジャンルに紐づく書籍がない場合、0冊と表示される
    public function test_genre_displays_zero_books_when_no_books_attached(): void
    {
        $user = User::factory()->create();
        $genre = Genre::create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertSee('0冊');
    }

    // ジャンルが1件も登録されていない場合、メッセージが正しく表示される
    public function test_message_is_displayed_when_no_genres_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertStatus(200);
        $response->assertSee('ジャンルが登録されていません。');
    }
}
