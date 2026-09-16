<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalancePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // processBalancePayment() guards on configured payment methods.
        config()->set('vietqr.account_name', 'TEST USER');
        config()->set('vietqr.template', 'compact2');
        config()->set('vietqr.default', 'mb');
        config()->set('vietqr.methods', [
            'mb' => ['label' => 'MB Bank', 'type' => 'bank', 'bank_id' => 'MB', 'account_no' => '111111'],
            'tcb' => ['label' => 'Techcombank', 'type' => 'bank', 'bank_id' => 'TCB', 'account_no' => '222222'],
            'vpb' => ['label' => 'VPBank', 'type' => 'bank', 'bank_id' => 'VPB', 'account_no' => '333333'],
            'momo' => ['label' => 'MoMo', 'type' => 'wallet', 'account_no' => '0912345678'],
        ]);
    }

    public function test_user_can_pay_balance_on_successful_batch()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Test User',
            'phone' => '0123456789',
        ]);
        $product = Product::factory()->create(['price' => 500000, 'quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 100000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => 200000,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($user)->post("/reservations/{$reservation->id}/process-balance", [
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
        ]);

        $response->assertRedirect();
        $reservation->refresh();
        // Flow xác thực thủ công: chờ shop duyệt, reservation chưa convert.
        $this->assertEquals('reserved', $reservation->status);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'total_amount' => 1000000,
            'payment_method' => 'balance',
            'payment_status' => 'awaiting_confirmation',
            'order_status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_details', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 500000,
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'type' => 'balance',
            'amount' => 800000,
        ]);
    }

    public function test_double_submit_is_blocked()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'Test User',
            'phone' => '0123456789',
        ]);
        $product = Product::factory()->create(['price' => 500000, 'quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 100000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => 200000,
            'status' => 'reserved',
        ]);

        // First payment
        $response = $this->actingAs($user)->post("/reservations/{$reservation->id}/process-balance", [
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
        ]);

        $response->assertRedirect();

        // Second payment attempt - should be blocked
        $response = $this->actingAs($user)->post("/reservations/{$reservation->id}/process-balance", [
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
        ]);

        $response->assertSessionHasErrors();

        // Only one order should exist
        $this->assertEquals(1, Order::where('user_id', $user->id)->count());
    }

    public function test_cannot_pay_balance_on_non_successful_batch()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'open',
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($user)->post("/reservations/{$reservation->id}/process-balance", [
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
        ]);

        $response->assertSessionHasErrors();
    }
}
