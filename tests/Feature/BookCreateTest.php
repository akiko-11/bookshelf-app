<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreateTest extends TestCase
{
    use RefreshDatabase;

    // 未認証ユーザーは書籍登録画面へアクセスするとログイン画面へリダイレクトされる
    public function test_guest_is_redirected_to_login_when_accessing_book_create(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    // 認証ユーザーは書籍登録画面へアクセスできる
    public function test_authenticated_user_can_access_book_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('books.create'));

        $response->assertStatus(200);
    }
}
