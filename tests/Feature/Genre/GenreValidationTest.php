<?php

namespace Tests\Feature\Genre;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreValidationTest extends TestCase
{
    use RefreshDatabase;

    // ジャンル名が未入力の場合、必須エラーメッセージが表示される
    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は必須です',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    // ジャンル名が文字列以外の場合、型エラーメッセージが表示される
    public function test_genre_name_must_be_string(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => ['invalid_genre'],
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は文字列で入力してください',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    // ジャンル名が256文字の場合、文字数エラーメッセージが表示される
    public function test_genre_name_max_length_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => str_repeat('あ', 256),
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は255文字以内で入力してください',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    // ジャンル名が255文字の場合、正常に登録できる
    public function test_genre_name_accepts_255_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => str_repeat('あ', 255),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseCount('genres', 1);
        $this->assertDatabaseHas('genres', [
            'name' => str_repeat('あ', 255),
        ]);
    }

    // 同じジャンル名を登録した場合、重複エラーメッセージが表示される
    public function test_genre_name_unique_message(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '重複',
        ]);

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '重複',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'そのジャンル名は既に使用されています',
        ]);

        $this->assertDatabaseCount('genres', 1);
    }

    // 正常なジャンル名の場合、バリデーションエラーが発生しない
    public function test_valid_genre_name_has_no_validation_errors(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'ジャンル登録',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseCount('genres', 1);
        $this->assertDatabaseHas('genres', [
            'name' => 'ジャンル登録',
        ]);
    }
}
