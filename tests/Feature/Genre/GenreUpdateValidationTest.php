<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    // ジャンル名を変更せずに更新できる
    public function test_same_genre_name_can_update_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'ジャンル更新',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => 'ジャンル更新',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル更新',
        ]);
    }

    // 他のジャンルと同じ名前には更新できない
    public function test_other_genre_name_cannot_update_same_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'ジャンル更新',
        ]);
        Genre::factory()->create([
            'name' => '他のジャンル',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '他のジャンル',
            ]);

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '他のジャンル',
        ]);

        // 既存のジャンル名は維持されていることの確認
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ジャンル更新',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'そのジャンル名は既に使用されています',
        ]);
    }

    // ジャンル名が未入力の場合、必須エラーメッセージが表示される
    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は必須です',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $genre->name,
        ]);
    }

    // ジャンル名が文字列以外の場合、型エラーメッセージが表示される
    public function test_genre_name_must_be_string(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => ['invalid_genre'],
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は文字列で入力してください',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $genre->name,
        ]);
    }

    // ジャンル名が256文字の場合、文字数エラーメッセージが表示される
    public function test_genre_name_max_length_message(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => str_repeat('あ', 256),
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は255文字以内で入力してください',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => $genre->name,
        ]);
    }

    // ジャンル名が255文字の場合、正常に更新できる
    public function test_genre_name_accepts_255_characters(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => str_repeat('あ', 255),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => str_repeat('あ', 255),
        ]);
    }
}
