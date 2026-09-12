<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActionLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_success_writes_audit_log_with_admin_and_reason()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->patch(
            "/admin/batches/{$batch->id}/force-success",
            ['reason' => 'Demo cho khách xem']
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_id' => $admin->id,
            'action' => 'force_success',
            'batch_id' => $batch->id,
            'reason' => 'Demo cho khách xem',
        ]);
    }

    public function test_close_early_and_fail_write_logs()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $fullBatch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 1,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);
        $user = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $fullBatch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'reserved',
        ]);

        $this->actingAs($admin)->patch("/admin/batches/{$fullBatch->id}/close-early");
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_id' => $admin->id,
            'action' => 'close_early',
            'batch_id' => $fullBatch->id,
        ]);

        $failBatch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 9,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        $this->actingAs($admin)->patch("/admin/batches/{$failBatch->id}/force-fail");
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_id' => $admin->id,
            'action' => 'force_fail',
            'batch_id' => $failBatch->id,
        ]);
    }

    public function test_collect_balance_writes_log_with_reservation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $holder = User::factory()->create(['role' => 'user', 'phone' => '0912345678']);
        $product = Product::factory()->create(['price' => 500000]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 100000,
            'deadline' => now()->addDays(3),
            'status' => 'success',
        ]);
        $reservation = Reservation::create([
            'user_id' => $holder->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 100000,
            'status' => 'reserved',
        ]);

        $this->actingAs($admin)->post("/admin/reservations/{$reservation->id}/collect", [
            'customer_name' => 'Holder',
            'customer_phone' => '0912345678',
            'shipping_address' => '123 Street',
            'collect_method' => 'transfer',
            'reason' => 'Khách chuyển khoản ngoài giờ',
        ]);

        $this->assertDatabaseHas('admin_action_logs', [
            'admin_id' => $admin->id,
            'action' => 'collect_balance',
            'batch_id' => $batch->id,
            'reservation_id' => $reservation->id,
            'reason' => 'Khách chuyển khoản ngoài giờ',
        ]);
    }

    public function test_admin_can_view_action_log_index()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        $this->actingAs($admin)->patch("/admin/batches/{$batch->id}/force-fail");

        $response = $this->actingAs($admin)->get('/admin/action-logs');

        $response->assertOk();
        $response->assertSee('Chốt thất bại');
    }

    public function test_guest_and_customer_cannot_view_action_logs()
    {
        $customer = User::factory()->create(['role' => 'user']);

        $this->get('/admin/action-logs')->assertRedirect('/login');
        $this->actingAs($customer)->get('/admin/action-logs')->assertForbidden();
    }
}
