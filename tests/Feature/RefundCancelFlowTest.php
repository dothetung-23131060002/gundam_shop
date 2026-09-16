<?php

namespace Tests\Feature;

use App\Exceptions\RefundException;
use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cancel matrix + chống double refund (deposit pot vs order pot).
 */
class RefundCancelFlowTest extends TestCase
{
    use RefreshDatabase;

    private RefundService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RefundService::class);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeProduct(int $qty = 100, int $price = 100000): Product
    {
        return Product::factory()->create(['quantity' => $qty, 'price' => $price]);
    }

    private function makeBatch(Product $product, string $status = 'success'): Batch
    {
        return Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDay(),
            'status' => $status,
        ]);
    }

    private function makeReservation(User $user, Batch $batch, string $status = 'reserved', float $deposit = 100000, int $qty = 2): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => $qty,
            'deposit_paid' => $deposit,
            'status' => $status,
        ]);
    }

    /**
     * Đơn cart (không batch): có trừ kho, hủy thì hồi kho.
     */
    private function makeCartOrder(User $user, Product $product, string $method = 'cod', string $pay = 'unpaid', string $status = 'pending', int $qty = 5): array
    {
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test',
            'total_amount' => $product->price * $qty,
            'payment_method' => $method,
            'payment_status' => $pay,
            'order_status' => $status,
        ]);

        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => $qty,
            'subtotal' => $product->price * $qty,
        ]);

        return [$order, $detail];
    }

    /**
     * Đơn balance đã paid của reservation converted (total FULL gồm phần cọc).
     */
    private function makeConvertedPaidOrder(User $user, Product $product, Batch $batch, float $deposit = 100000): array
    {
        $reservation = $this->makeReservation($user, $batch, 'converted', $deposit);

        $order = Order::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'reservation_id' => $reservation->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test',
            'total_amount' => 600000,
            'payment_method' => 'balance',
            'payment_status' => Order::PAY_PAID,
            'order_status' => 'pending',
        ]);

        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 300000,
            'quantity' => 2,
            'subtotal' => 600000,
        ]);

        return [$reservation, $order, $detail];
    }

    public function test_cod_unpaid_cancel_creates_no_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        [$order] = $this->makeCartOrder($user, $this->makeProduct(), 'cod', 'unpaid');

        $result = $this->service->cancelAndRefundOrder($order->id);

        $this->assertSame('cancelled', $result['order']->order_status);
        $this->assertNull($result['refund']);
        $this->assertSame(0, RefundTransaction::count());
        $this->assertSame(0, Payment::where('type', 'refund')->count());
    }

    public function test_qr_unpaid_cancel_creates_no_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        [$order] = $this->makeCartOrder($user, $this->makeProduct(), 'qr', Order::PAY_PENDING);

        $result = $this->service->cancelAndRefundOrder($order->id);

        $this->assertSame('cancelled', $result['order']->order_status);
        $this->assertNull($result['refund']);
        $this->assertSame(0, RefundTransaction::count());
    }

    public function test_qr_paid_cancel_creates_pending_refund()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        [$order, $detail] = $this->makeCartOrder($user, $this->makeProduct(), 'qr', Order::PAY_PAID);

        $result = $this->service->cancelAndRefundOrder($order->id, [], RefundService::REASON_ADMIN_CANCEL, $admin->id);

        $this->assertSame('cancelled', $result['order']->order_status);
        $this->assertNotNull($result['refund']);
        $this->assertSame(RefundTransaction::STATUS_PENDING, $result['refund']->status);
        $this->assertEquals(500000, (float) $result['refund']->amount);
        $this->assertSame(RefundService::REASON_ADMIN_CANCEL, $result['refund']->reason);
        // Pending chưa vào totals.
        $this->assertSame(0.0, $this->service->refundTotals()['refund_total']);

        $done = $this->service->complete($result['refund']->id, $admin->id);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $done->status);
        $this->assertEquals(500000, $this->service->refundTotals()['refund_total']);
    }

    public function test_balance_paid_cancel_refunds_collected_amount()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100, 300000);
        [$reservation, $order] = $this->makeConvertedPaidOrder($user, $product, $this->makeBatch($product));

        $result = $this->service->cancelAndRefundOrder($order->id, [], RefundService::REASON_ADMIN_CANCEL, $admin->id);

        // Total FULL 600k (đã gồm phần cọc) — tiền cọc trên reservation không đụng.
        $this->assertEquals(600000, (float) $result['refund']->amount);
        $this->assertEquals(100000, (float) $reservation->fresh()->deposit_paid);

        $this->service->complete($result['refund']->id, $admin->id);

        // FULL refund → đóng pot cọc (QĐ2), giữ status converted.
        $reservation = $reservation->fresh();
        $this->assertEquals(0, (float) $reservation->deposit_paid);
        $this->assertSame('converted', $reservation->status);
    }

    public function test_partial_refund_keeps_deposit_pot_untouched()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100, 300000);
        [$reservation, $order, $detail] = $this->makeConvertedPaidOrder($user, $product, $this->makeBatch($product));

        $result = $this->service->cancelAndRefundOrder(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 1]],
            RefundService::REASON_DAMAGED_TRANSIT,
            $admin->id
        );

        // Cancel luôn flip order → cancelled ngay cả khi partial.
        $this->assertSame('cancelled', $result['order']->order_status);
        $this->service->complete($result['refund']->id, $admin->id);

        // Partial → deposit_paid giữ nguyên.
        $this->assertEquals(100000, (float) $reservation->fresh()->deposit_paid);
        $this->assertEquals(300000, $this->service->refundedAmountForOrder($order->id));
    }

    public function test_cod_paid_cancel_and_refund()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        [$order, $detail] = $this->makeCartOrder($user, $this->makeProduct(), 'cod', Order::PAY_PAID);
        $order->update(['order_status' => 'shipping']);

        $result = $this->service->cancelAndRefundOrder($order->id, [], RefundService::REASON_DELIVERY_FAILED, $admin->id);

        $this->assertSame('cancelled', $result['order']->order_status);
        $this->assertNotNull($result['refund']);

        $done = $this->service->complete($result['refund']->id, $admin->id);
        $this->assertNotNull($done->payment_id);
    }

    public function test_batch_order_cancel_does_not_restore_stock()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100, 300000);
        [$reservation, $order] = $this->makeConvertedPaidOrder($user, $product, $this->makeBatch($product));

        $this->service->cancelAndRefundOrder($order->id);

        $this->assertSame(100, (int) $product->fresh()->quantity);
    }

    public function test_cart_order_cancel_restores_stock()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100);
        [$order] = $this->makeCartOrder($user, $product, 'cod', 'unpaid');

        $this->service->cancelAndRefundOrder($order->id);

        $this->assertSame(105, (int) $product->fresh()->quantity);
    }

    public function test_success_reservation_without_live_order_can_cancel_deposit()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        // Batch success nhưng chưa có order sống, chưa trả balance.
        $reservation = $this->makeReservation($user, $this->makeBatch($this->makeProduct()));

        $refund = $this->service->cancelReservationAndRefundDeposit(
            $reservation->id,
            RefundService::REASON_RELEASE_OVERDUE,
            $admin->id
        );

        // Slot giải phóng ngay (không còn reserved), tiền đi ở complete().
        $this->assertSame('cancelled', $reservation->fresh()->status);
        $this->assertSame(RefundTransaction::STATUS_PENDING, $refund->status);

        $this->service->complete($refund->id, $admin->id);
        $reservation = $reservation->fresh();
        $this->assertEquals(0, (float) $reservation->deposit_paid);
        $this->assertSame('cancelled', $reservation->status);
    }

    public function test_converted_paid_order_blocks_second_deposit_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100, 300000);
        [$reservation] = $this->makeConvertedPaidOrder($user, $product, $this->makeBatch($product));

        try {
            $this->service->createDepositRefund($reservation->id, RefundService::REASON_RELEASE_OVERDUE);
            $this->fail('Expected DUPLICATE_REFUND (R1).');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::DUPLICATE_REFUND, $e->errorCode);
        }
    }

    public function test_live_pending_order_blocks_deposit_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100, 300000);
        $batch = $this->makeBatch($product);
        $reservation = $this->makeReservation($user, $batch);

        // Đơn claim sống (chưa paid) gắn reservation.
        $this->makeCartOrder($user, $product, 'qr', Order::PAY_AWAITING);
        Order::latest()->first()->update(['reservation_id' => $reservation->id]);

        try {
            $this->service->cancelReservationAndRefundDeposit(
                $reservation->id,
                RefundService::REASON_USER_CANCEL_DEPOSIT
            );
            $this->fail('Expected DUPLICATE_REFUND (R2).');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::DUPLICATE_REFUND, $e->errorCode);
        }
    }

    public function test_full_order_refund_blocks_later_deposit_refund()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100, 300000);
        [$reservation, $order] = $this->makeConvertedPaidOrder($user, $product, $this->makeBatch($product));

        $result = $this->service->cancelAndRefundOrder($order->id);
        $this->service->complete($result['refund']->id, $admin->id);

        // Tiền đã về hết qua pot order (kèm deposit_paid=0) → cấm hoàn cọc nữa.
        try {
            $this->service->createDepositRefund($reservation->id, RefundService::REASON_RELEASE_OVERDUE);
            $this->fail('Expected block after full order refund.');
        } catch (RefundException $e) {
            $this->assertContains($e->errorCode, [
                RefundException::DUPLICATE_REFUND,
                RefundException::NOT_REFUNDABLE_STATE,
                RefundException::NOTHING_TO_REFUND,
            ]);
        }

        // Không double: tổng chi = đúng 600k thực thu.
        $this->assertEquals(600000, $this->service->refundTotals()['refund_total']);
    }

    public function test_second_cancel_on_cancelled_order_is_blocked()
    {
        $user = User::factory()->create(['role' => 'user']);
        [$order] = $this->makeCartOrder($user, $this->makeProduct(), 'qr', Order::PAY_PAID);

        $this->service->cancelAndRefundOrder($order->id);

        try {
            $this->service->cancelAndRefundOrder($order->id);
            $this->fail('Expected NOT_REFUNDABLE_STATE.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::NOT_REFUNDABLE_STATE, $e->errorCode);
        }
    }

    public function test_admin_can_cancel_unpaid_cart_order_via_http()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100);
        [$order] = $this->makeCartOrder($user, $product, 'cod', 'unpaid');

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'order_status' => 'cancelled',
        ])->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(105, (int) $product->fresh()->quantity);
        $this->assertSame(0, RefundTransaction::count());
    }

    public function test_admin_cancel_paid_order_via_http_creates_pending_refund()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        [$order] = $this->makeCartOrder($user, $this->makeProduct(), 'qr', Order::PAY_PAID);

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'order_status' => 'cancelled',
        ])->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->order_status);

        $refund = RefundTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($refund);
        $this->assertSame(RefundTransaction::STATUS_PENDING, $refund->status);
        $this->assertSame(RefundService::REASON_ADMIN_CANCEL, $refund->reason);
        $this->assertSame($admin->id, (int) $refund->admin_id);
    }

    public function test_admin_cancel_batch_order_via_http_keeps_stock()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->makeProduct(100, 300000);
        [$reservation, $order] = $this->makeConvertedPaidOrder($user, $product, $this->makeBatch($product));

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'order_status' => 'cancelled',
        ])->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(100, (int) $product->fresh()->quantity);
        $this->assertNotNull(RefundTransaction::where('order_id', $order->id)->first());
    }

    public function test_admin_cannot_cancel_completed_order_via_http()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'user']);
        [$order] = $this->makeCartOrder($user, $this->makeProduct(), 'qr', Order::PAY_PAID, 'completed');

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'order_status' => 'cancelled',
        ])->assertRedirect();

        $this->assertSame('completed', $order->fresh()->order_status);
        $this->assertSame(0, RefundTransaction::count());
    }
}
