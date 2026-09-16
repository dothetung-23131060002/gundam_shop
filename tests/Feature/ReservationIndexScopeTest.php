<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationIndexScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_see_other_users_refunded_reservations(): void
    {
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $batch = Batch::create([
            'product_id' => Product::factory()->create(['quantity' => 100])->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'failed',
        ]);

        // User B có reservation refunded 3 ngày trước (trong cửa sổ 7 ngày)
        $reservationB = Reservation::create([
            'user_id' => $userB->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'refunded',
        ]);
        DB::table('reservations')->where('id', $reservationB->id)->update([
            'updated_at' => now()->subDays(3)->toDateTimeString(),
        ]);

        // User A gọi reservations.index — không được thấy reservation của User B
        $response = $this->actingAs($userA)->get(route('reservations.index'));

        $response->assertOk();
        $viewReservations = $response->viewData('reservations');
        $this->assertEquals(0, $viewReservations->total());
    }

    public function test_user_cannot_see_other_users_reserved_reservations(): void
    {
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $batch = Batch::create([
            'product_id' => Product::factory()->create(['quantity' => 100])->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'open',
        ]);

        // User B có reservation đang reserved
        Reservation::create([
            'user_id' => $userB->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => 100000,
            'status' => 'reserved',
        ]);

        // User A không thấy reservation của User B
        $response = $this->actingAs($userA)->get(route('reservations.index'));

        $response->assertOk();
        $viewReservations = $response->viewData('reservations');
        $this->assertEquals(0, $viewReservations->total());
    }

    public function test_user_sees_own_reserved_reservations(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $batch = Batch::create([
            'product_id' => Product::factory()->create(['quantity' => 100])->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'open',
        ]);

        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($user)->get(route('reservations.index'));

        $response->assertOk();
        $viewReservations = $response->viewData('reservations');
        $this->assertEquals(1, $viewReservations->total());
    }

    public function test_user_sees_own_recent_refunded_reservations(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $batch = Batch::create([
            'product_id' => Product::factory()->create(['quantity' => 100])->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'failed',
        ]);

        // Reservation refunded 3 ngày trước — trong cửa sổ 7 ngày
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'refunded',
        ]);
        DB::table('reservations')->where('id', $reservation->id)->update([
            'updated_at' => now()->subDays(3)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get(route('reservations.index'));

        $response->assertOk();
        $viewReservations = $response->viewData('reservations');
        $this->assertEquals(1, $viewReservations->total());
    }

    public function test_user_does_not_see_own_old_refunded_reservations(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $batch = Batch::create([
            'product_id' => Product::factory()->create(['quantity' => 100])->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'failed',
        ]);

        // Reservation refunded 10 ngày trước — ngoài cửa sổ 7 ngày
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'refunded',
        ]);
        DB::table('reservations')->where('id', $reservation->id)->update([
            'updated_at' => now()->subDays(10)->toDateTimeString(),
        ]);

        $response = $this->actingAs($user)->get(route('reservations.index'));

        $response->assertOk();
        $viewReservations = $response->viewData('reservations');
        $this->assertEquals(0, $viewReservations->total());
    }

    public function test_converted_reservations_are_excluded(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $batch = Batch::create([
            'product_id' => Product::factory()->create(['quantity' => 100])->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        // Reservation đã convert — KHÔNG hiển thị
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'converted',
        ]);

        $response = $this->actingAs($user)->get(route('reservations.index'));

        $response->assertOk();
        $viewReservations = $response->viewData('reservations');
        $this->assertEquals(0, $viewReservations->total());
    }

    public function test_guest_cannot_access_reservation_index(): void
    {
        $response = $this->get(route('reservations.index'));

        $response->assertRedirect();
    }
}
