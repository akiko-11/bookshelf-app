<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreEditTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーがジャンル編集画面を表示できる
    public function test_authenticated_user_can_view_genre_edit(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);
    }

    // 未認証ユーザーはジャンル編集画面にアクセスできない
    public function test_guest_cannot_view_genre_edit(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.edit', $genre));

        $response->assertRedirect(route('login'));
    }

    // 存在しないジャンルIDへのアクセスは404になる
    public function test_nonexistent_genre_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('genres.edit', 99999));

        $response->assertNotFound();
    }

    // 認証済みユーザーがジャンル名を更新できる
    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => 'ジャンル更新',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル更新',
        ]);

        $response->assertSessionHas(
            'success',
            'ジャンルを更新しました。'
        );
    }

    // 未認証ユーザーがジャンル名を更新できない
    public function test_guest_cannot_update_genre(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->put(route('genres.update', $genre), [
            'name' => 'ジャンル更新',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル更新',
        ]);

        // 既存のジャンル名が変更されていないことの確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $genre->name,
        ]);
    }
}
