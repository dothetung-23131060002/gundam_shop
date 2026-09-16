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

    // ==========================================
    // HARDENING: review eligibility
    // ==========================================

    public function test_other_users_order_cannot_review()
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $other->id,
            'customer_name' => $other->name,
            'customer_phone' => '0999999999',
            'shipping_address' => '456 Other St',
            'total_amount' => $product->price,
            'payment_method' => 'qr',
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
            'comment' => 'Trying to review product I did not buy',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_product_not_in_order_details_cannot_review()
    {
        $user = User::factory()->create(['role' => 'user']);
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $productA->price,
            'payment_method' => 'qr',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'product_name' => $productA->name,
            'price' => $productA->price,
            'quantity' => 1,
            'subtotal' => $productA->price,
        ]);

        // Try to review productB (not in order)
        $response = $this->actingAs($user)->post("/products/{$productB->id}/reviews", [
            'rating' => 5,
            'comment' => 'Review product I did not buy',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $productB->id,
        ]);
    }

    public function test_pending_order_cannot_review()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $product->price,
            'payment_method' => 'qr',
            'payment_status' => 'pending_payment',
            'order_status' => 'pending',
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
            'comment' => 'Review before order completed',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cancelled_order_cannot_review()
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
            'order_status' => 'cancelled',
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
            'comment' => 'Review cancelled order product',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_unpaid_completed_order_cannot_review()
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
            'payment_status' => 'unpaid',
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
            'comment' => 'Review unpaid completed order',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    // ==========================================
    // HARDENING: validation
    // ==========================================

    public function test_rating_zero_rejected()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $product->price,
            'payment_method' => 'qr',
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
            'rating' => 0,
            'comment' => 'Zero stars',
        ]);

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_rating_six_rejected()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $product->price,
            'payment_method' => 'qr',
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
            'rating' => 6,
            'comment' => 'Six stars',
        ]);

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_comment_too_long_rejected()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $product->price,
            'payment_method' => 'qr',
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
            'comment' => str_repeat('A', 1001),
        ]);

        $response->assertSessionHasErrors('comment');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_empty_comment_allowed()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $product->price,
            'payment_method' => 'qr',
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
            'rating' => 4,
            'comment' => '',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 4,
        ]);
    }

    public function test_converted_reservation_without_order_cannot_review()
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

        // Converted reservation WITHOUT a completed+paid order → cannot review
        $response = $this->actingAs($user)->post("/products/{$product->id}/reviews", [
            'rating' => 4,
            'comment' => 'Pre-order review',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }
}
