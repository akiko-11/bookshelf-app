<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    // ログアウト後はログイン画面へ遷移し、未認証状態になる
    public function test_user_is_redirected_to_login_after_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    // ログアウト後に認証必須ページへアクセスできない
    public function test_logged_out_user_cannot_access_favorites_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $response = $this->get('/favorites');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
