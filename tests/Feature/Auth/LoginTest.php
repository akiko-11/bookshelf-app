<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // メールアドレスが未入力の場合、バリデーションに失敗する
    public function test_email_is_required_for_login(): void
    {
        $response = $this->post('/login', $this->validLoginData([
            'email' => '',
        ]));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードが未入力の場合、バリデーションに失敗する
    public function test_password_is_required_for_login(): void
    {
        $response = $this->post('/login', $this->validLoginData([
            'password' => '',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 登録したメールアドレスと一致しない場合、ログインに失敗する
    public function test_login_fails_when_email_is_incorrect(): void
    {
        $this->createUser();

        $response = $this->post('/login', $this->validLoginData([
            'email' => 'wrong@example.com',
        ]));

        $response->assertSessionHasErrors([
            'email' => '入力情報が誤っています',
        ]);

        $this->assertGuest();
    }

    // 登録済みメールアドレスでもパスワードが一致しない場合、ログインに失敗する
    public function test_login_fails_when_password_is_incorrect(): void
    {
        $this->createUser();

        $response = $this->post('/login', $this->validLoginData([
            'password' => 'wrong_password',
        ]));

        $response->assertSessionHasErrors([
            'email' => '入力情報が誤っています',
        ]);

        $this->assertGuest();
    }

    // 登録情報と一致する場合、正常にログインできる
    public function test_user_can_login_with_correct_email_and_password(): void
    {
        $user = $this->createUser();

        $response = $this->post('/login', $this->validLoginData());

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    // ログイン用の正常な入力値
    private function validLoginData(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'password',
        ], $overrides);
    }

    // ログインテスト用ユーザーを作成する
    private function createUser(): User
    {
        return User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
