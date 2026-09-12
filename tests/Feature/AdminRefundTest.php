<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRefundTest extends TestCase
{
    use RefreshDatabase;

    private function makeRefund(): RefundTransaction
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->subDay(),
            'status' => 'failed',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => 0,
            'status' => 'refunded',
        ]);

        return RefundTransaction::create([
            'reservation_id' => $reservation->id,
            'amount' => 100000,
            'reason' => 'Batch failed: threshold not met',
            'refunded_at' => now(),
        ]);
    }

    public function test_admin_can_list_refunds()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $refund = $this->makeRefund();

        $response = $this->actingAs($admin)->get('/admin/refunds');

        $response->assertOk();
        $response->assertSee("#{$refund->id}");
        $response->assertSee('100.000');
    }

    public function test_admin_can_view_refund_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $refund = $this->makeRefund();

        $response = $this->actingAs($admin)->get("/admin/refunds/{$refund->id}");

        $response->assertOk();
        $response->assertSee($refund->reason);
        $response->assertSee($refund->reservation->user->name);
    }

    public function test_guest_and_customer_cannot_access_admin_refunds()
    {
        $refund = $this->makeRefund();
        $customer = User::factory()->create(['role' => 'user']);

        $this->get('/admin/refunds')->assertRedirect('/login');
        $this->get("/admin/refunds/{$refund->id}")->assertRedirect('/login');

        $this->actingAs($customer)->get('/admin/refunds')->assertForbidden();
        $this->actingAs($customer)->get("/admin/refunds/{$refund->id}")->assertForbidden();
    }
}
