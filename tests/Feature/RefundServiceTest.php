<?php

namespace Tests\Feature;

use App\Exceptions\RefundException;
use App\Models\Batch;
use App\Models\BatchIncident;
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

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    private RefundService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RefundService::class);
    }

    private function makeUser(string $role = 'user'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeBatch(Product $product, string $status = 'open'): Batch
    {
        return Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDay(),
            'status' => $status,
        ]);
    }

    private function makeReservation(User $user, Batch $batch, int $quantity = 2, float $deposit = 100000, string $status = 'reserved'): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => $quantity,
            'deposit_paid' => $deposit,
            'status' => $status,
        ]);
    }

    private function makePaidOrder(User $user, Product $product, string $method = 'qr', string $payStatus = 'paid'): array
    {
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'total_amount' => 500000,
            'payment_method' => $method,
            'payment_status' => $payStatus,
            'order_status' => 'pending',
        ]);

        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => 5,
            'subtotal' => 500000,
        ]);

        return [$order, $detail];
    }

    public function test_create_deposit_refund_creates_pending()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($user, $this->makeBatch(Product::factory()->create()));

        $refund = $this->service->createDepositRefund(
            $reservation->id,
            RefundService::REASON_USER_CANCEL_DEPOSIT,
            $admin->id
        );

        $this->assertSame(RefundTransaction::STATUS_PENDING, $refund->status);
        $this->assertNull($refund->refunded_at);
        $this->assertNull($refund->payment_id);
        $this->assertSame(RefundTransaction::TYPE_DEPOSIT, $refund->type);
        $this->assertEquals(100000, (float) $refund->amount);
        $this->assertSame(0, Payment::where('type', 'refund')->count());
        // Tạo pending chưa đụng tiền cọc.
        $this->assertEquals(100000, (float) $reservation->fresh()->deposit_paid);
    }

    public function test_complete_deposit_refund_moves_money()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($user, $this->makeBatch(Product::factory()->create()));

        $refund = $this->service->createDepositRefund(
            $reservation->id,
            RefundService::REASON_BATCH_FAILED
        );
        $done = $this->service->complete($refund->id, $admin->id);

        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $done->status);
        $this->assertNotNull($done->refunded_at);
        $this->assertNotNull($done->payment_id);
        $this->assertSame($admin->id, $done->admin_id);

        $payment = Payment::find($done->payment_id);
        $this->assertNotNull($payment);
        $this->assertSame('refund', $payment->type);
        $this->assertEquals(100000, (float) $payment->amount);
        $this->assertSame($user->id, $payment->user_id);

        $reservation = $reservation->fresh();
        $this->assertEquals(0, (float) $reservation->deposit_paid);
        $this->assertSame('refunded', $reservation->status);
    }

    public function test_duplicate_deposit_refund_is_blocked()
    {
        $user = $this->makeUser();
        $reservation = $this->makeReservation($user, $this->makeBatch(Product::factory()->create()));

        $this->service->createDepositRefund($reservation->id, RefundService::REASON_USER_CANCEL_DEPOSIT);

        try {
            $this->service->createDepositRefund($reservation->id, RefundService::REASON_BATCH_FAILED);
            $this->fail('Expected DUPLICATE_REFUND.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::DUPLICATE_REFUND, $e->errorCode);
        }
    }

    public function test_double_complete_is_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $reservation = $this->makeReservation($user, $this->makeBatch(Product::factory()->create()));

        $refund = $this->service->createDepositRefund($reservation->id, RefundService::REASON_BATCH_FAILED);
        $this->service->complete($refund->id, $admin->id);

        try {
            $this->service->complete($refund->id, $admin->id);
            $this->fail('Expected INVALID_TRANSITION.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    public function test_invalid_deposit_reason_rejected()
    {
        $user = $this->makeUser();
        $reservation = $this->makeReservation($user, $this->makeBatch(Product::factory()->create()));

        try {
            $this->service->createDepositRefund($reservation->id, RefundService::REASON_WRONG_ITEM);
            $this->fail('Expected INVALID_REASON.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    public function test_create_order_refund_partial_with_details()
    {
        $user = $this->makeUser();
        [$order, $detail] = $this->makePaidOrder($user, Product::factory()->create());

        $refund = $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 2]],
            RefundService::REASON_DAMAGED_TRANSIT
        );

        $this->assertSame(RefundTransaction::STATUS_PENDING, $refund->status);
        $this->assertEquals(200000, (float) $refund->amount);
        $this->assertCount(1, $refund->details);
        // Header = SUM(details).
        $this->assertEquals(
            (float) $refund->details->sum('amount_refunded'),
            (float) $refund->amount
        );
    }

    public function test_quantity_guard_blocks_over_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        [$order, $detail] = $this->makePaidOrder($user, Product::factory()->create());

        // OrderDetail quantity = 5, refund #1 = 2 → còn 3.
        $first = $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 2]],
            RefundService::REASON_DAMAGED_TRANSIT
        );
        $this->service->complete($first->id, $admin->id);

        // Refund #2 xin 4 → phải bị chặn.
        try {
            $this->service->createOrderRefund(
                $order->id,
                [['order_detail_id' => $detail->id, 'quantity' => 4]],
                RefundService::REASON_DAMAGED_TRANSIT
            );
            $this->fail('Expected QUANTITY_EXCEEDED.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }

        $this->assertSame(2, $this->service->refundedQuantityForDetail($detail->id));
    }

    public function test_second_complete_exceeding_cap_is_blocked_on_recheck()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        [$order, $detail] = $this->makePaidOrder($user, Product::factory()->create());

        // Hai pending full chồng nhau đều qua được lúc tạo (pending không tính cap).
        $first = $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 5]],
            RefundService::REASON_LOST_IN_TRANSIT
        );
        $second = $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 5]],
            RefundService::REASON_LOST_IN_TRANSIT
        );

        $this->service->complete($first->id, $admin->id);

        try {
            $this->service->complete($second->id, $admin->id);
            $this->fail('Expected cap/quantity violation on recheck.');
        } catch (RefundException $e) {
            $this->assertContains($e->errorCode, [
                RefundException::QUANTITY_EXCEEDED,
                RefundException::AMOUNT_EXCEEDED,
            ]);
        }
    }

    public function test_cod_unpaid_cannot_refund()
    {
        $user = $this->makeUser();
        [$order, $detail] = $this->makePaidOrder($user, Product::factory()->create(), 'cod', 'unpaid');

        try {
            $this->service->createOrderRefund(
                $order->id,
                [['order_detail_id' => $detail->id, 'quantity' => 1]],
                RefundService::REASON_DELIVERY_FAILED
            );
            $this->fail('Expected UNPAID_COD.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::UNPAID_COD, $e->errorCode);
        }
    }

    public function test_cod_paid_can_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        [$order, $detail] = $this->makePaidOrder($user, Product::factory()->create(), 'cod', 'paid');
        $order->update(['order_status' => 'completed']);

        $refund = $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 5]],
            RefundService::REASON_DELIVERY_FAILED
        );
        $done = $this->service->complete($refund->id, $admin->id);

        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $done->status);
        $this->assertEquals(500000, (float) $done->amount);
        $this->assertNotNull($done->payment_id);
    }

    public function test_fail_marks_failed_without_payment()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        [$order, $detail] = $this->makePaidOrder($user, Product::factory()->create());

        $refund = $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 1]],
            RefundService::REASON_PAYMENT_ERROR
        );
        $failed = $this->service->fail($refund->id, $admin->id);

        $this->assertSame(RefundTransaction::STATUS_FAILED, $failed->status);
        $this->assertNull($failed->payment_id);
        $this->assertSame(0, Payment::where('type', 'refund')->count());
        // Failed không tính vào totals.
        $this->assertSame(0.0, $this->service->refundedAmountForOrder($order->id));
        $this->assertSame(0, $this->service->refundedQuantityForDetail($detail->id));
    }

    public function test_forfeiture_convention()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $batch = $this->makeBatch(Product::factory()->create(), 'success');
        $reservation = $this->makeReservation($user, $batch);

        $record = $this->service->recordForfeiture($reservation->id, $admin->id);

        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $record->status);
        $this->assertSame(RefundTransaction::TYPE_DEPOSIT, $record->type);
        $this->assertSame(RefundTransaction::REASON_FORFEITED, $record->reason);
        $this->assertNull($record->payment_id);
        $this->assertNotNull($record->refunded_at);
        $this->assertSame(0, Payment::where('type', 'refund')->count());

        // INTERIM: đóng cọc + đóng trạng thái để giải phóng slot.
        $reservation = $reservation->fresh();
        $this->assertEquals(0, (float) $reservation->deposit_paid);
        $this->assertSame('cancelled', $reservation->status);

        // Không vào Refund Total, có báo cáo Forfeiture riêng.
        $totals = $this->service->refundTotals();
        $this->assertSame(0.0, $totals['refund_total']);
        $this->assertEquals(100000, $totals['forfeiture_total']);

        // Forfeiture rồi thì không được hoàn cọc nữa.
        try {
            $this->service->createDepositRefund($reservation->id, RefundService::REASON_RELEASE_OVERDUE);
            $this->fail('Expected DUPLICATE_REFUND after forfeiture.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::DUPLICATE_REFUND, $e->errorCode);
        }
    }

    public function test_totals_exclude_pending()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        [$order, $detail] = $this->makePaidOrder($user, Product::factory()->create());

        $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 2]],
            RefundService::REASON_WRONG_ITEM
        );

        $totals = $this->service->refundTotals();
        $this->assertSame(0.0, $totals['refund_total']);
        $this->assertSame(0.0, $this->service->refundedAmountForOrder($order->id));

        // Complete 1 refund 200k → totals lên đúng 200k.
        $refund = RefundTransaction::first();
        $this->service->complete($refund->id, $admin->id);

        $totals = $this->service->refundTotals();
        $this->assertEquals(200000, $totals['refund_total']);
        $this->assertEquals(200000, $this->service->refundedAmountForOrder($order->id));
    }

    public function test_refunded_quantity_for_product()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = Product::factory()->create();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $refund = $this->service->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 2]],
            RefundService::REASON_MANUFACTURER_DEFECT
        );
        $this->service->complete($refund->id, $admin->id);

        $this->assertSame(2, $this->service->refundedQuantityForProduct($product->id));
    }

    public function test_batch_incident_model_minimal()
    {
        $admin = $this->makeUser('admin');
        $batch = $this->makeBatch(Product::factory()->create());

        $incident = BatchIncident::create([
            'batch_id' => $batch->id,
            'type' => 'supplier_shortage',
            'description' => 'NCC thiếu hàng',
            'admin_id' => $admin->id,
        ]);

        $this->assertSame($batch->id, $incident->batch->id);
        $this->assertSame($admin->id, $incident->admin->id);
        $this->assertContains($incident->type, BatchIncident::TYPES);
    }
}
