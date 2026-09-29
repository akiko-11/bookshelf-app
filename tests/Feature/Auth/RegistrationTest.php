<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // 名前が未入力の場合、バリデーションに失敗する
    public function test_name_is_required_for_registration(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'name' => '',
        ]));

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください。',
        ]);
    }

    // メールアドレスが未入力の場合、バリデーションに失敗する
    public function test_email_is_required_for_registration(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'email' => '',
        ]));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください。',
        ]);
    }

    // メールアドレスがメール形式ではない場合、バリデーションに失敗する
    public function test_email_must_be_valid_format_for_registration(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'email' => 'invalid-email',
        ]));

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください。',
        ]);
    }

    // メールアドレスが既に登録されている場合、バリデーションに失敗する
    public function test_email_is_already_registered_for_registration(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/register', $this->validRegistrationData([
            'email' => 'test@example.com',
        ]));

        $response->assertSessionHasErrors([
            'email' => '入力されたメールアドレスは既に登録されています。',
        ]);

        $this->assertDatabaseCount('users', 1);
    }

    // パスワードが7文字の場合、バリデーションに失敗する
    public function test_password_with_7_characters_fails_registration(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]));

        $response->assertSessionHasErrors('password');
    }

    // パスワードが8文字の場合、正常に登録できる
    public function test_password_with_8_characters_can_register(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]));

        $response->assertRedirect('/');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    // パスワードが一致しない場合、バリデーションに失敗する
    public function test_password_confirmation_does_not_match_for_registration(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'password_confirmation' => 'error_password',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワード確認と一致しません。',
        ]);
    }

    // パスワードが未入力の場合、バリデーションに失敗する
    public function test_password_is_required_for_registration(): void
    {
        $response = $this->post('/register', $this->validRegistrationData([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください。',
        ]);
    }

    // 正しく内容が入力されていた場合、正常に登録される
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post('/register', $this->validRegistrationData());

        $response->assertRedirect('/');

        $this->assertDatabaseHas('users', [
            'name' => 'テスト 太郎',
            'email' => 'test@example.com',
        ]);

        $this->assertAuthenticated();
    }

    // 有効な登録データを保持する
    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト 太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $overrides);
    }
}
