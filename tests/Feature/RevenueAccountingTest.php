<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accounting contract (source of truth):
 * Gross = SUM(total_amount WHERE payment_status = paid), không lọc order_status.
 * Refund = SUM(amount WHERE status = completed AND reason != EXPIRED_FORFEITED).
 * Net = Gross - Refund. Forfeiture = dòng riêng, không trừ Net.
 */
class RevenueAccountingTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $method, string $pay, string $status, int $total): Order
    {
        $product = Product::factory()->create(['price' => $total]);

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test',
            'total_amount' => $total,
            'payment_method' => $method,
            'payment_status' => $pay,
            'order_status' => $status,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $total,
            'quantity' => 1,
            'subtotal' => $total,
        ]);

        return $order;
    }

    private function makeCompletedRefund(?int $orderId, float $amount, string $reason = 'PAYMENT_ERROR'): RefundTransaction
    {
        return RefundTransaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'reason' => $reason,
            'refunded_at' => now(),
            'status' => RefundTransaction::STATUS_COMPLETED,
            'type' => RefundTransaction::TYPE_ORDER,
        ]);
    }

    private function gross(): float
    {
        return (float) Order::paid()->sum('total_amount');
    }

    private function refunds(): float
    {
        return (float) RefundTransaction::monetary()->sum('amount');
    }

    private function forfeitures(): float
    {
        return (float) RefundTransaction::forfeitures()->sum('amount');
    }

    public function test_paid_completed_counts_in_gross()
    {
        $this->makeOrder('qr', Order::PAY_PAID, 'completed', 600000);

        $this->assertEquals(600000, $this->gross());
        $this->assertEquals(600000, $this->gross() - $this->refunds());
    }

    public function test_paid_cancelled_stays_in_gross()
    {
        $this->makeOrder('qr', Order::PAY_PAID, 'cancelled', 100000);

        $this->assertEquals(100000, $this->gross());
    }

    public function test_paid_cancelled_full_refund_nets_zero()
    {
        $order = $this->makeOrder('qr', Order::PAY_PAID, 'cancelled', 600000);
        $this->makeCompletedRefund($order->id, 600000);

        $this->assertEquals(600000, $this->gross());
        $this->assertEquals(600000, $this->refunds());
        $this->assertEquals(0, $this->gross() - $this->refunds());
    }

    public function test_paid_completed_partial_refund()
    {
        $order = $this->makeOrder('qr', Order::PAY_PAID, 'completed', 500000);
        $this->makeCompletedRefund($order->id, 200000);

        $this->assertEquals(500000, $this->gross());
        $this->assertEquals(200000, $this->refunds());
        $this->assertEquals(300000, $this->gross() - $this->refunds());
    }

    public function test_cod_unpaid_cancelled_is_zero_everywhere()
    {
        $this->makeOrder('cod', 'unpaid', 'cancelled', 300000);

        $this->assertEquals(0, $this->gross());
        $this->assertEquals(0, $this->refunds());
    }

    public function test_cod_paid_completed_counts_in_gross()
    {
        $this->makeOrder('cod', Order::PAY_PAID, 'completed', 300000);

        $this->assertEquals(300000, $this->gross());
    }

    public function test_qr_pending_awaiting_rejected_excluded()
    {
        $this->makeOrder('qr', Order::PAY_PENDING, 'pending', 100000);
        $this->makeOrder('qr', Order::PAY_AWAITING, 'pending', 100000);
        $this->makeOrder('qr', Order::PAY_REJECTED, 'pending', 100000);

        $this->assertEquals(0, $this->gross());
    }

    public function test_balance_paid_counts_balance_unpaid_cancelled_zero()
    {
        $this->makeOrder('balance', Order::PAY_PAID, 'pending', 500000);
        $this->makeOrder('balance', Order::PAY_PENDING, 'cancelled', 500000);

        $this->assertEquals(500000, $this->gross());
    }

    public function test_pending_refund_does_not_subtract()
    {
        $order = $this->makeOrder('qr', Order::PAY_PAID, 'pending', 500000);
        RefundTransaction::create([
            'order_id' => $order->id,
            'amount' => 500000,
            'reason' => 'PAYMENT_ERROR',
            'refunded_at' => null,
            'status' => RefundTransaction::STATUS_PENDING,
            'type' => RefundTransaction::TYPE_ORDER,
        ]);

        $this->assertEquals(500000, $this->gross());
        $this->assertEquals(0, $this->refunds());
        $this->assertEquals(500000, $this->gross() - $this->refunds());
    }

    public function test_failed_refund_does_not_subtract()
    {
        $order = $this->makeOrder('qr', Order::PAY_PAID, 'pending', 500000);
        RefundTransaction::create([
            'order_id' => $order->id,
            'amount' => 500000,
            'reason' => 'PAYMENT_ERROR',
            'refunded_at' => null,
            'status' => RefundTransaction::STATUS_FAILED,
            'type' => RefundTransaction::TYPE_ORDER,
        ]);

        $this->assertEquals(0, $this->refunds());
    }

    public function test_forfeiture_is_separate_income_not_net_reduction()
    {
        RefundTransaction::create([
            'amount' => 50000,
            'reason' => RefundTransaction::REASON_FORFEITED,
            'refunded_at' => now(),
            'status' => RefundTransaction::STATUS_COMPLETED,
            'type' => RefundTransaction::TYPE_DEPOSIT,
        ]);

        $this->assertEquals(0, $this->refunds());
        $this->assertEquals(50000, $this->forfeitures());
    }

    public function test_multiple_refunds_same_order_accumulate()
    {
        $order = $this->makeOrder('qr', Order::PAY_PAID, 'completed', 500000);
        $this->makeCompletedRefund($order->id, 200000, 'DAMAGED_TRANSIT');
        $this->makeCompletedRefund($order->id, 100000, 'WRONG_ITEM');

        $this->assertEquals(300000, $this->refunds());
        $this->assertEquals(200000, $this->gross() - $this->refunds());
    }

    public function test_empty_data_is_zero()
    {
        $this->assertEquals(0, $this->gross());
        $this->assertEquals(0, $this->refunds());
        $this->assertEquals(0, $this->forfeitures());
    }

    public function test_dashboard_shows_gross_refund_net_forfeiture()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeOrder('qr', Order::PAY_PAID, 'completed', 600000);
        $cancelled = $this->makeOrder('qr', Order::PAY_PAID, 'cancelled', 100000);
        $this->makeCompletedRefund($cancelled->id, 100000);
        RefundTransaction::create([
            'amount' => 50000,
            'reason' => RefundTransaction::REASON_FORFEITED,
            'refunded_at' => now(),
            'status' => RefundTransaction::STATUS_COMPLETED,
            'type' => RefundTransaction::TYPE_DEPOSIT,
        ]);

        // Gross 700.000 (giữ cả paid+cancelled), refund 100.000, net 600.000.
        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertSee('700.000');
        $response->assertSee('600.000');
        $response->assertSee('100.000');
        $response->assertSee('50.000');
    }
}
