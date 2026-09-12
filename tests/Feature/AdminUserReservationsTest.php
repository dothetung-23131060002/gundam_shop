<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_page_shows_all_reservation_statuses()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['name' => 'History Gundam']);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 75000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        foreach (['reserved', 'converted', 'refunded', 'cancelled'] as $status) {
            Reservation::create([
                'user_id' => $user->id,
                'batch_id' => $batch->id,
                'quantity' => 1,
                'deposit_paid' => in_array($status, ['reserved', 'converted']) ? 75000 : 0,
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertOk();
        $response->assertSee('Lịch sử giữ slot (4)');
        $response->assertSee('History Gundam');
        $response->assertSee('Đang giữ');
        $response->assertSee('Đã chuyển đơn');
        $response->assertSee('Đã hoàn cọc');
        $response->assertSee('Đã hủy');
        $response->assertSee('75.000');
    }

    public function test_admin_user_page_shows_empty_reservation_state()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}");

        $response->assertOk();
        $response->assertSee('Chưa từng giữ slot nào');
    }
}
