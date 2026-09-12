<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReservationTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservation(string $status = 'reserved'): Reservation
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        return Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => $status === 'reserved' ? 50000 : 0,
            'status' => $status,
        ]);
    }

    public function test_admin_can_list_and_filter_by_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reserved = $this->makeReservation('reserved');
        $this->makeReservation('refunded');

        $response = $this->actingAs($admin)->get('/admin/reservations?status=reserved');

        $response->assertOk();
        $response->assertSee("#{$reserved->id}");
    }

    public function test_admin_can_view_reservation_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reservation = $this->makeReservation();

        $response = $this->actingAs($admin)->get("/admin/reservations/{$reservation->id}");

        $response->assertOk();
        $response->assertSee($reservation->user->name);
    }

    public function test_guest_and_customer_cannot_access_admin_reservations()
    {
        $reservation = $this->makeReservation();
        $customer = User::factory()->create(['role' => 'user']);

        $this->get('/admin/reservations')->assertRedirect('/login');
        $this->get("/admin/reservations/{$reservation->id}")->assertRedirect('/login');

        $this->actingAs($customer)->get('/admin/reservations')->assertForbidden();
        $this->actingAs($customer)->get("/admin/reservations/{$reservation->id}")->assertForbidden();
    }
}
