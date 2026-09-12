<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_batch_statistics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Test Gundam RX-78']);

        $successBatch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 2,
            'deposit_amount' => 100000,
            'deadline' => now()->addDays(3),
            'status' => 'success',
        ]);
        Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->subDay(),
            'status' => 'failed',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $successBatch->id,
            'quantity' => 2,
            'deposit_paid' => 200000,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('50%'); // 1 success / 2 closed
        $response->assertSee('200.000');
        $response->assertSee('Test Gundam RX-78');
    }

    public function test_dashboard_handles_no_closed_batches()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('0%');
        $response->assertSee('Chưa có slot nào đang giữ.');
    }

    public function test_customer_cannot_view_dashboard()
    {
        // Guest trước (actingAs bên dưới sẽ dính sang các call sau trong test)
        $this->get('/admin/dashboard')->assertRedirect('/login');

        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
    }
}
