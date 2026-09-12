<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BatchExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_batch_with_enough_slots_succeeds()
    {
        Notification::fake();

        $product = Product::factory()->create(['quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 2,
            'deposit_amount' => 50000,
            'deadline' => now()->subDay(),
            'status' => 'open',
        ]);

        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);

        Reservation::create(['user_id' => $user1->id, 'batch_id' => $batch->id, 'quantity' => 1, 'deposit_paid' => 50000, 'status' => 'reserved']);
        Reservation::create(['user_id' => $user2->id, 'batch_id' => $batch->id, 'quantity' => 1, 'deposit_paid' => 50000, 'status' => 'reserved']);

        Artisan::call('batches:check-expired');

        $batch->refresh();
        $this->assertEquals('success', $batch->status);
    }

    public function test_expired_batch_fails_and_refunds()
    {
        Notification::fake();

        $product = Product::factory()->create(['quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->subDay(),
            'status' => 'open',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => 100000,
            'status' => 'reserved',
        ]);

        Artisan::call('batches:check-expired');

        $batch->refresh();
        $this->assertEquals('failed', $batch->status);

        $reservation->refresh();
        $this->assertEquals('refunded', $reservation->status);
        $this->assertEquals(0, $reservation->deposit_paid);

        $this->assertDatabaseHas('refund_transactions', [
            'reservation_id' => $reservation->id,
            'amount' => 100000,
        ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'reservation_id' => $reservation->id,
            'type' => 'refund',
            'amount' => 100000,
        ]);
    }

    public function test_only_open_expired_batches_are_processed()
    {
        $product = Product::factory()->create(['quantity' => 100]);

        Batch::create([
            'product_id' => $product->id,
            'threshold' => 2,
            'deposit_amount' => 50000,
            'deadline' => now()->subDay(),
            'status' => 'success',
        ]);

        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 2,
            'deposit_amount' => 50000,
            'deadline' => now()->subDay(),
            'status' => 'open',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'reserved',
        ]);

        Artisan::call('batches:check-expired');

        $batch->refresh();
        $this->assertEquals('failed', $batch->status);
    }
}
