<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reserve_slot_in_open_batch()
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

        $response = $this->actingAs($user)->post('/reservations', [
            'batch_id' => $batch->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'status' => 'reserved',
        ]);
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 100000,
            'type' => 'deposit',
        ]);
    }

    public function test_user_cannot_reserve_twice_in_same_batch()
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

        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($user)->post('/reservations', [
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('batch_id');
    }

    public function test_user_cannot_exceed_batch_threshold()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 3,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'open',
        ]);

        // Fill up the batch with other users
        for ($i = 0; $i < 3; $i++) {
            $otherUser = User::factory()->create(['role' => 'user']);
            Reservation::create([
                'user_id' => $otherUser->id,
                'batch_id' => $batch->id,
                'quantity' => 1,
                'deposit_paid' => 50000,
                'status' => 'reserved',
            ]);
        }

        $response = $this->actingAs($user)->post('/reservations', [
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_batch_auto_succeeds_when_full()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 2,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'open',
        ]);

        // First reservation
        $otherUser = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $otherUser->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'reserved',
        ]);

        // Second reservation - should trigger success
        $response = $this->actingAs($user)->post('/reservations', [
            'batch_id' => $batch->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect();
        $batch->refresh();
        $this->assertEquals('success', $batch->status);
    }
}
