<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_settings()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/settings');
        $response->assertOk();
        $response->assertSee('Gundam Shop');

        $response = $this->actingAs($admin)->put('/admin/settings', [
            'shop_name' => 'Test Shop',
            'default_deposit_amount' => 99999,
            'default_deadline_days' => 7,
            'contact_address' => 'Test City',
            'contact_phone' => '0999888777',
            'contact_email' => 'test@shop.vn',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('Test Shop', Setting::get('shop_name'));
        $this->assertEquals('99999', Setting::get('default_deposit_amount'));
    }

    public function test_settings_validation_rejects_bad_values()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'shop_name' => '',
                'default_deposit_amount' => 500,
                'default_deadline_days' => 400,
                'contact_email' => 'not-an-email',
            ]);

        $response->assertSessionHasErrors(['shop_name', 'default_deposit_amount', 'default_deadline_days', 'contact_email']);
    }

    public function test_guest_and_customer_cannot_manage_settings()
    {
        $customer = User::factory()->create(['role' => 'user']);

        $this->get('/admin/settings')->assertRedirect('/login');
        $this->actingAs($customer)->get('/admin/settings')->assertForbidden();
        $this->actingAs($customer)->put('/admin/settings', [])->assertForbidden();
    }
}
