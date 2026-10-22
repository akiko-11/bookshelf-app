<?php

namespace Tests\Feature\Genre;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCreateTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがジャンル登録画面を表示できる
    public function test_authenticated_user_can_view_genre_create(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertStatus(200);
    }

    // 未認証ユーザーは登録画面にアクセスできない
    public function test_guest_cannot_view_genre_create(): void
    {
        $response = $this->get(route('genres.create'));

        $response->assertRedirect(route('login'));
    }

    // 認証済みユーザーがジャンルを登録できる
    public function test_authenticated_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), [
            'name' => 'ジャンル登録',
        ]);

        $response->assertRedirect(route('genres.index'));
        $this->assertDatabaseHas('genres', [
            'name' => 'ジャンル登録',
        ]);
    }

    // 未認証ユーザーはジャンルを登録できない
    public function test_guest_cannot_create_genre(): void
    {
        $response = $this->post(route('genres.store'), [
            'name' => 'ジャンル登録',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('genres', [
            'name' => 'ジャンル登録',
        ]);
    }

    // 登録成功後、成功メッセージが設定される
    public function test_success_message_is_set_after_creation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'ジャンル登録',
            ]);

        $response->assertSessionHas(
            'success',
            'ジャンルを作成しました。'
        );
    }
}
