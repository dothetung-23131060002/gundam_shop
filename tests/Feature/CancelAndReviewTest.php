<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelAndReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_cancel_reservation_when_batch_is_open()
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
            'quantity' => 2,
            'deposit_paid' => 100000,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($user)->delete("/reservations/{$reservation->id}");

        $response->assertRedirect();
        $reservation->refresh();
        $this->assertEquals('cancelled', $reservation->status);
        $this->assertEquals(0, $reservation->deposit_paid);

        $this->assertDatabaseHas('refund_transactions', [
            'reservation_id' => $reservation->id,
            'amount' => 100000,
        ]);
    }

    public function test_user_cannot_cancel_when_batch_is_success()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'reserved',
        ]);

        $response = $this->actingAs($user)->delete("/reservations/{$reservation->id}");

        $response->assertSessionHas('error');
        $reservation->refresh();
        $this->assertEquals('reserved', $reservation->status);
    }

    public function test_user_cannot_review_without_purchase()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post("/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Great product!',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_can_review_after_completed_order()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $product->price,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        $response = $this->actingAs($user)->post("/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'Great product!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
        ]);
    }

    public function test_user_can_review_after_converted_reservation()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 50000,
            'status' => 'converted',
        ]);

        $response = $this->actingAs($user)->post("/products/{$product->id}/reviews", [
            'rating' => 4,
            'comment' => 'Good pre-order experience.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
        ]);
    }

    public function test_user_cannot_review_twice()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $product->price,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        // First review
        $this->actingAs($user)->post("/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'First review',
        ]);

        // Second review attempt
        $response = $this->actingAs($user)->post("/products/{$product->id}/reviews", [
            'rating' => 4,
            'comment' => 'Second review',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, Review::where('user_id', $user->id)->where('product_id', $product->id)->count());
    }
}
