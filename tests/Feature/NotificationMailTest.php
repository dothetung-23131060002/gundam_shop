<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\BatchRefunded;
use App\Notifications\BatchSucceeded;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_channel_off_when_not_configured()
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

        $this->assertEquals(
            ['database'],
            (new BatchSucceeded($batch, 1))->via($user)
        );
    }

    public function test_mail_channel_on_when_smtp_configured()
    {
        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => 'smtp.example.com']);

        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        $this->assertEquals(
            ['database', 'mail'],
            (new BatchSucceeded($batch, 1))->via($user)
        );
    }

    public function test_mail_content_renders()
    {
        $user = User::factory()->create(['role' => 'user', 'name' => 'Test User']);
        $product = Product::factory()->create(['name' => 'Test Gundam']);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        $mail = (new BatchSucceeded($batch, 2))->toMail($user);

        $this->assertStringContainsString("Đợt gom #{$batch->id}", $mail->subject);
        $this->assertNotEmpty($mail->actionUrl);
    }

    public function test_refunded_and_order_mail_content_renders()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'failed',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 0,
            'status' => 'refunded',
        ]);

        $refundMail = (new BatchRefunded($batch, $reservation, 'Không đạt ngưỡng'))->toMail($user);
        $this->assertStringContainsString('hoàn cọc', $refundMail->subject);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'shipping',
        ]);

        $orderMail = (new OrderStatusChanged($order, 'confirmed', 'shipping'))->toMail($user);
        $this->assertStringContainsString("#{$order->id}", $orderMail->subject);
    }
}
