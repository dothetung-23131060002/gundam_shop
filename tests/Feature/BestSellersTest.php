<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\RefundTransactionDetail;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestSellersTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $method, string $payStatus, string $orderStatus, array $items): Order
    {
        $total = 0;
        foreach ($items as [$product, $qty]) {
            $total += $product->price * $qty;
        }

        $order = Order::create([
            'user_id' => User::factory()->create()->id,
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $total,
            'payment_method' => $method,
            'payment_status' => $payStatus,
            'order_status' => $orderStatus,
        ]);

        foreach ($items as [$product, $qty]) {
            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'quantity' => $qty,
                'subtotal' => $product->price * $qty,
            ]);
        }

        return $order;
    }

    private function soldOf(int $productId): ?int
    {
        $row = Product::bestSellers()->get()->firstWhere('id', $productId);

        return $row ? (int) $row->total_sold : null;
    }

    public function test_sums_quantities_across_orders_and_sorts_desc()
    {
        $a = Product::factory()->create(['price' => 100000]);
        $b = Product::factory()->create(['price' => 100000]);
        $c = Product::factory()->create(['price' => 100000]);

        $this->makeOrder('qr', 'paid', 'confirmed', [[$a, 2], [$b, 1]]);
        $this->makeOrder('qr', 'paid', 'shipping', [[$a, 3], [$c, 4]]);

        $this->assertSame(5, $this->soldOf($a->id));
        $this->assertSame(1, $this->soldOf($b->id));
        $this->assertSame(4, $this->soldOf($c->id));

        $ids = Product::bestSellers()->get()->pluck('id')->all();
        $this->assertSame([$a->id, $c->id, $b->id], $ids);
    }

    public function test_pending_awaiting_rejected_are_excluded()
    {
        $p = Product::factory()->create(['price' => 100000]);

        $this->makeOrder('qr', 'pending_payment', 'pending', [[$p, 5]]);
        $this->makeOrder('qr', 'awaiting_confirmation', 'pending', [[$p, 5]]);
        $this->makeOrder('qr', 'payment_rejected', 'pending', [[$p, 5]]);

        $this->assertNull($this->soldOf($p->id));
    }

    public function test_paid_cancelled_stays_in_gross_sold()
    {
        $p = Product::factory()->create(['price' => 100000]);

        // paid + cancelled vẫn tính đã bán (phần hoàn trừ riêng ở refund layer).
        $this->makeOrder('qr', 'paid', 'cancelled', [[$p, 5]]);

        $this->assertSame(5, $this->soldOf($p->id));
    }

    public function test_paid_and_completed_cod_are_counted()
    {
        $p = Product::factory()->create(['price' => 100000]);

        $this->makeOrder('qr', 'paid', 'pending', [[$p, 2]]);
        $this->makeOrder('cod', 'paid', 'completed', [[$p, 3]]);
        // COD completed nhưng chưa thu (unpaid) thì KHÔNG tính.
        $this->makeOrder('cod', 'unpaid', 'completed', [[$p, 7]]);

        $this->assertSame(5, $this->soldOf($p->id));
    }

    public function test_incomplete_cod_is_not_counted()
    {
        $p = Product::factory()->create(['price' => 100000]);

        $this->makeOrder('cod', 'unpaid', 'pending', [[$p, 2]]);
        $this->makeOrder('cod', 'unpaid', 'shipping', [[$p, 3]]);

        $this->assertNull($this->soldOf($p->id));
    }

    public function test_balance_order_counts_once_no_double_count()
    {
        $user = User::factory()->create(['role' => 'user']);
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
            'status' => 'converted',
        ]);

        // Đặt cọc không tạo order_details; chỉ 1 balance order chứa quantity thật.
        $this->makeOrder('balance', 'paid', 'pending', [[$product, 2]]);

        $this->assertSame(2, $this->soldOf($product->id));
        $this->assertSame(1, Product::bestSellers()->get()->count());
    }

    public function test_deleted_product_never_appears()
    {
        $p = Product::factory()->create(['price' => 100000]);
        $p->delete();

        $this->assertNull($this->soldOf($p->id));
        $this->get('/')->assertOk();
    }

    public function test_homepage_hides_section_without_data()
    {
        Product::factory()->create(['price' => 100000]);

        $this->get('/')->assertOk()->assertDontSee('SẢN PHẨM BÁN CHẠY', false);
    }

    public function test_homepage_shows_section_with_sold_counts()
    {
        $p = Product::factory()->create(['price' => 100000, 'quantity' => 7]);
        $this->makeOrder('qr', 'paid', 'confirmed', [[$p, 5]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('SẢN PHẨM BÁN CHẠY', false)
            ->assertSee('Đã bán 5', false)
            ->assertSee('Còn 7 sản phẩm', false);
    }

    private function makeCompletedDetailRefund(OrderDetail $detail, int $qty): void
    {
        $refund = RefundTransaction::create([
            'order_id' => $detail->order_id,
            'amount' => $detail->price * $qty,
            'reason' => 'DAMAGED_TRANSIT',
            'refunded_at' => now(),
            'status' => RefundTransaction::STATUS_COMPLETED,
            'type' => RefundTransaction::TYPE_ORDER,
        ]);

        RefundTransactionDetail::create([
            'refund_transaction_id' => $refund->id,
            'order_detail_id' => $detail->id,
            'quantity_refunded' => $qty,
            'amount_refunded' => $detail->price * $qty,
        ]);
    }

    public function test_partial_refund_subtracts_net_sold()
    {
        $p = Product::factory()->create(['price' => 100000]);
        $order = $this->makeOrder('qr', 'paid', 'completed', [[$p, 5]]);

        $this->makeCompletedDetailRefund($order->details()->first(), 2);

        $this->assertSame(3, $this->soldOf($p->id));
    }

    public function test_multiple_refunds_exact_detail_mapping_no_double_count()
    {
        $a = Product::factory()->create(['price' => 100000]);
        $b = Product::factory()->create(['price' => 100000]);
        $order = $this->makeOrder('qr', 'paid', 'completed', [[$a, 5], [$b, 4]]);

        $details = $order->details()->orderBy('id')->get();
        // Hai lần hoàn trên cùng dòng A (2+2), một lần trên dòng B (1).
        $this->makeCompletedDetailRefund($details[0], 2);
        $this->makeCompletedDetailRefund($details[0], 2);
        $this->makeCompletedDetailRefund($details[1], 1);

        // A: 5-4=1, B: 4-1=3 — join fan-out sẽ cho số khác nếu sai.
        $this->assertSame(1, $this->soldOf($a->id));
        $this->assertSame(3, $this->soldOf($b->id));
    }

    public function test_pending_and_failed_refunds_do_not_reduce_sold()
    {
        $p = Product::factory()->create(['price' => 100000]);
        $order = $this->makeOrder('qr', 'paid', 'completed', [[$p, 5]]);
        $detail = $order->details()->first();

        foreach ([RefundTransaction::STATUS_PENDING, RefundTransaction::STATUS_FAILED] as $status) {
            $refund = RefundTransaction::create([
                'order_id' => $order->id,
                'amount' => 500000,
                'reason' => 'PAYMENT_ERROR',
                'refunded_at' => null,
                'status' => $status,
                'type' => RefundTransaction::TYPE_ORDER,
            ]);
            RefundTransactionDetail::create([
                'refund_transaction_id' => $refund->id,
                'order_detail_id' => $detail->id,
                'quantity_refunded' => 5,
                'amount_refunded' => 500000,
            ]);
        }

        $this->assertSame(5, $this->soldOf($p->id));
    }

    public function test_fully_refunded_product_has_no_net_sold()
    {
        $p = Product::factory()->create(['price' => 100000]);
        $order = $this->makeOrder('qr', 'paid', 'completed', [[$p, 5]]);

        $this->makeCompletedDetailRefund($order->details()->first(), 5);

        $this->assertNull($this->soldOf($p->id));
    }

    public function test_empty_result_when_no_paid_orders()
    {
        $p = Product::factory()->create(['price' => 100000]);
        $this->makeOrder('cod', 'unpaid', 'pending', [[$p, 5]]);

        $this->assertSame([], Product::bestSellers()->get()->all());
    }
}
