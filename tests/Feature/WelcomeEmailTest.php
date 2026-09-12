<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\WelcomeRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_sends_welcome_database_notification()
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'newuser@gundam.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('home'));

        $user = User::where('email', 'newuser@gundam.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->notifications()->count());
        $this->assertStringContainsString(
            'Gundam Shop',
            $user->notifications()->first()->data['message']
        );
    }

    public function test_welcome_mail_content_explains_preorder_model()
    {
        $user = User::factory()->create(['role' => 'user', 'name' => 'Test User']);

        $mail = (new WelcomeRegistered)->toMail($user);

        $this->assertStringContainsString('gom đơn theo đợt', $mail->subject);
        $rendered = implode("\n", $mail->introLines)."\n".$mail->actionText;

        foreach (['Giữ slot bằng cọc', 'đủ ngưỡng', 'Thanh toán đủ'] as $phrase) {
            $this->assertStringContainsString($phrase, $rendered.$mail->subject);
        }

        $this->assertStringNotContainsString('giỏ hàng', strtolower($rendered));
        $this->assertStringNotContainsString('mua ngay', strtolower($rendered));
    }

    public function test_welcome_mail_channel_follows_smtp_config()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->assertEquals(['database'], (new WelcomeRegistered)->via($user));

        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => 'smtp.example.com']);

        $this->assertEquals(['database', 'mail'], (new WelcomeRegistered)->via($user));
    }
}
