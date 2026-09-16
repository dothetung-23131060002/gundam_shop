<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nguyen Van A',
            'email' => 'a@example.com',
            'phone' => '0912345678',
            'address' => '123 Test Street',
        ], $overrides);
    }

    public function test_guest_cannot_access_profile()
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->put('/profile', $this->validPayload())->assertRedirect('/login');
    }

    public function test_user_cannot_update_other_user()
    {
        $userA = User::factory()->create(['role' => 'user', 'name' => 'User A']);
        $userB = User::factory()->create(['role' => 'user', 'name' => 'User B', 'email' => 'b@example.com']);

        // user_id trong payload phải bị lờ: chỉ user đang đăng nhập được cập nhật.
        $this->actingAs($userA)->put('/profile', array_merge(
            $this->validPayload(['name' => 'Hacker']),
            ['user_id' => $userB->id]
        ))->assertRedirect();

        $this->assertSame('User B', $userB->refresh()->name);
        $this->assertSame('Hacker', $userA->refresh()->name);
    }

    public function test_update_name_successfully()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->put('/profile', $this->validPayload(['name' => 'Tran Thi B']))
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('Tran Thi B', $user->refresh()->name);
    }

    public function test_update_valid_email_successfully()
    {
        $user = User::factory()->create(['role' => 'user', 'email' => 'old@example.com']);

        $this->actingAs($user)->put('/profile', $this->validPayload(['email' => 'new@example.com']))
            ->assertRedirect();

        $this->assertSame('new@example.com', $user->refresh()->email);
    }

    public function test_duplicate_email_is_rejected()
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['role' => 'user', 'email' => 'mine@example.com']);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->put('/profile', $this->validPayload(['email' => 'taken@example.com']));

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors('email');
        $this->assertSame('mine@example.com', $user->refresh()->email);
    }

    public function test_invalid_phone_is_rejected()
    {
        $user = User::factory()->create(['role' => 'user', 'phone' => '0912345678']);

        foreach (['abc123456', '12345', '09-12-34-AB'] as $badPhone) {
            $response = $this->actingAs($user)
                ->from('/profile')
                ->put('/profile', $this->validPayload(['phone' => $badPhone]));

            $response->assertSessionHasErrors('phone');
        }

        $this->assertSame('0912345678', $user->refresh()->phone);
    }

    public function test_phone_allows_valid_formats_and_nullable()
    {
        $user = User::factory()->create(['role' => 'user']);

        foreach (['0912345678', '+84 912 345 678', '(091) 234-5678', '0912.345.678'] as $phone) {
            $this->actingAs($user)->put('/profile', $this->validPayload(['phone' => $phone]))
                ->assertSessionHasNoErrors();
            $this->assertSame($phone, $user->refresh()->phone);
        }

        $this->actingAs($user)->put('/profile', $this->validPayload(['phone' => null]))
            ->assertSessionHasNoErrors();
        $this->assertNull($user->refresh()->phone);
    }

    public function test_validation_keeps_old_input()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->put('/profile', $this->validPayload(['name' => 'Ten Moi', 'email' => 'not-an-email']));

        $response->assertSessionHasErrors('email');
        $this->assertSame('Ten Moi', session()->getOldInput('name'));
    }

    public function test_success_message_and_reload_keeps_new_info()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->put('/profile', $this->validPayload(['name' => 'Le Van C', 'phone' => '0987654321']));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success', 'Đã cập nhật thông tin cá nhân.');

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Le Van C', false)
            ->assertSee('0987654321', false);
    }

    public function test_role_and_password_cannot_be_mass_assigned()
    {
        $user = User::factory()->create(['role' => 'user']);
        $originalHash = $user->password;

        $this->actingAs($user)->put('/profile', array_merge(
            $this->validPayload(),
            ['role' => 'admin', 'password' => 'hacked123']
        ))->assertRedirect();

        $user->refresh();
        $this->assertSame('user', $user->role);
        $this->assertSame($originalHash, $user->password);
    }
}
