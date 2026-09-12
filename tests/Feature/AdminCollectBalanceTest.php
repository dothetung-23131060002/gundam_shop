<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCollectBalanceTest extends TestCase
{
    use RefreshDatabase;

    private function makePayableReservation(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $holder = User::factory()->create([
            'role' => 'user',
            'name' => 'Holder Name',
            'phone' => '0912345678',
        ]);
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
            'quantity' => 2,
            'deposit_paid' => 200000,
            'status' => 'reserved',
        ]);

        return [$admin, $holder, $reservation];
    }

    public function test_admin_collect_form_shows_prefilled_holder_info()
    {
        [$admin, $holder, $reservation] = $this->makePayableReservation();

        $response = $this->actingAs($admin)->get("/admin/reservations/{$reservation->id}/collect");

        $response->assertOk();
        $response->assertSee('Holder Name');
        $response->assertSee('800.000');
    }

    public function test_admin_collect_creates_order_for_holder()
    {
        [$admin, $holder, $reservation] = $this->makePayableReservation();

        $response = $this->actingAs($admin)->post("/admin/reservations/{$reservation->id}/collect", [
            'customer_name' => 'Holder Name',
            'customer_phone' => '0912345678',
            'shipping_address' => '123 Test Street',
            'collect_method' => 'cash',
        ]);

        $response->assertRedirect();
        $this->assertEquals('converted', $reservation->fresh()->status);

        $this->assertDatabaseHas('orders', [
            'user_id' => $holder->id,
            'batch_id' => $reservation->batch_id,
            'reservation_id' => $reservation->id,
            'total_amount' => 1000000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'type' => 'balance',
            'amount' => 800000,
        ]);
        $this->assertEquals(1, Order::where('user_id', $holder->id)->count());
    }

    public function test_collect_blocked_when_not_payable()
    {
        [$admin, $holder, $reservation] = $this->makePayableReservation();
        $reservation->update(['status' => 'converted']);

        $response = $this->actingAs($admin)->post("/admin/reservations/{$reservation->id}/collect", [
            'customer_name' => 'Holder Name',
            'customer_phone' => '0912345678',
            'shipping_address' => '123 Test Street',
            'collect_method' => 'cash',
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, Order::where('user_id', $holder->id)->count());
    }

    public function test_guest_and_customer_cannot_collect()
    {
        [, , $reservation] = $this->makePayableReservation();
        $customer = User::factory()->create(['role' => 'user']);

        $this->get("/admin/reservations/{$reservation->id}/collect")->assertRedirect('/login');
        $this->actingAs($customer)->get("/admin/reservations/{$reservation->id}/collect")->assertForbidden();
        $this->actingAs($customer)->post("/admin/reservations/{$reservation->id}/collect", [])->assertForbidden();
    }
}
