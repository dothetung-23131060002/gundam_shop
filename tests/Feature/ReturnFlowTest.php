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
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\RefundService;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private ReturnService $returnService;
    private RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->returnService = app(ReturnService::class);
        $this->refundService = app(RefundService::class);
    }

    private function makeUser(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeProduct(float $price = 100000, int $stock = 100): Product
    {
        return Product::factory()->create(['price' => $price, 'quantity' => $stock]);
    }

    private function makePaidOrder(User $user, Product $product, int $qty = 5, string $method = 'qr', string $payStatus = 'paid'): array
    {
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => RefundService::REASON_MANUFACTURER_DEFECT,
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'total_amount' => $product->price * $qty,
            'payment_method' => $method,
            'payment_status' => $payStatus,
            'order_status' => 'completed',
            'delivered_at' => now(),
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

    // ==========================================
    // 1. Customer request own order
    // ==========================================
    public function test_customer_request_own_order()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            'Sản phẩm bị hư hỏng',
            $user->id,
            'sealed'
        );

        $this->assertSame(ReturnRequest::STATUS_REQUESTED, $return->status);
        $this->assertSame($order->id, $return->order_id);
        $this->assertSame($detail->id, $return->order_detail_id);
        $this->assertSame(2, $return->quantity);
        $this->assertSame($user->id, $return->requested_by);
    }

    // ==========================================
    // 2. Customer cannot request another user's order
    // ==========================================
    public function test_customer_cannot_request_another_users_order()
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user1, $product);

        try {
            $this->returnService->request(
                $order->id,
                $detail->id,
                2,
                RefundService::REASON_MANUFACTURER_DEFECT,
                $user2->id
            );
            $this->fail('Expected INVALID_TRANSITION for wrong user.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 3. order_detail không thuộc order → reject
    // ==========================================
    public function test_order_detail_not_belong_to_order_rejected()
    {
        $user = $this->makeUser();
        $product1 = $this->makeProduct();
        $product2 = $this->makeProduct();
        [$order1, $detail1] = $this->makePaidOrder($user, $product1);
        [$order2, $detail2] = $this->makePaidOrder($user, $product2);

        try {
            $this->returnService->request(
                $order1->id,
                $detail2->id,
                2,
                RefundService::REASON_MANUFACTURER_DEFECT,
                $user->id
            );
            $this->fail('Expected DETAIL_MISMATCH.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::DETAIL_MISMATCH, $e->errorCode);
        }
    }

    // ==========================================
    // 4. quantity > ordered → reject
    // ==========================================
    public function test_quantity_exceeds_ordered_rejected()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 3);

        try {
            $this->returnService->request(
                $order->id,
                $detail->id,
                5,
                RefundService::REASON_MANUFACTURER_DEFECT,
                $user->id
            );
            $this->fail('Expected QUANTITY_EXCEEDED.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // 5. quantity > remaining refundable → reject
    // ==========================================
    public function test_quantity_exceeds_remaining_refundable_rejected()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        // Already refunded 3 via RefundService
        $refund = $this->refundService->createOrderRefund(
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 3]],
            RefundService::REASON_MANUFACTURER_DEFECT
        );
        $this->refundService->complete($refund->id, $admin->id);

        // Remaining = 5 - 3 = 2, try 3 → should fail
        try {
            $this->returnService->request(
                $order->id,
                $detail->id,
                3,
                RefundService::REASON_MANUFACTURER_DEFECT,
                $user->id
            );
            $this->fail('Expected QUANTITY_EXCEEDED.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // 6. Pending return giữ quota
    // ==========================================
    public function test_pending_return_holds_quota()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 10);

        // First return: 7 pending
        $first = $this->returnService->request(
            $order->id,
            $detail->id,
            7,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        // Second return: try 5 → remaining = 10 - 0 - 7 = 3 → should fail
        try {
            $this->returnService->request(
                $order->id,
                $detail->id,
                5,
                RefundService::REASON_WRONG_ITEM,
                $user->id
            );
            $this->fail('Expected QUANTITY_EXCEEDED for pending quota.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }

        // Second return: try 3 → remaining = 3 → should succeed
        $second = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_WRONG_ITEM,
            $user->id,
            'sealed'
        );

        $this->assertSame(3, $second->quantity);
    }

    // ==========================================
    // 7. approve không tự refund nếu cần return
    // ==========================================
    public function test_approve_does_not_auto_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $approved = $this->returnService->approve($return->id, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_APPROVED, $approved->status);
        $this->assertNull($approved->refund_transaction_id);

        // No refund transaction created
        $this->assertSame(0, RefundTransaction::where('order_id', $order->id)->count());
    }

    // ==========================================
    // 8. approve + no-return → refund đúng detail
    // ==========================================
    public function test_approve_no_return_creates_correct_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $completed = $this->returnService->completeWithoutReturn($return->id, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertNotNull($completed->refund_transaction_id);

        $refund = RefundTransaction::find($completed->refund_transaction_id);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
        $this->assertEquals(300000, (float) $refund->amount);

        // Check detail
        $refundDetail = $refund->details->first();
        $this->assertSame($detail->id, $refundDetail->order_detail_id);
        $this->assertSame(3, $refundDetail->quantity_refunded);
    }

    // ==========================================
    // 9. receive
    // ==========================================
    public function test_receive_marks_received()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $received = $this->returnService->receive($return->id, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_RECEIVED, $received->status);
        $this->assertNotNull($received->received_at);
    }

    // ==========================================
    // 10. inspect resellable → restock đúng quantity
    // ==========================================
    public function test_inspect_resellable_restocks_correct_quantity()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 50);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_RESELLABLE, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);

        $initialStock = $product->fresh()->quantity;
        $completed = $this->returnService->complete($return->id, 3, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertEquals($initialStock + 3, $product->fresh()->quantity);
    }

    // ==========================================
    // 11. inspect defective → không restock
    // ==========================================
    public function test_inspect_defective_does_not_restock()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 50);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_DEFECTIVE, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);

        $initialStock = $product->fresh()->quantity;
        $completed = $this->returnService->complete($return->id, 0, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertEquals($initialStock, $product->fresh()->quantity);
    }

    // ==========================================
    // 12. restocked twice → blocked
    // ==========================================
    public function test_double_restock_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 50);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_RESELLABLE, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->complete($return->id, 3, $admin->id);

        // Try to complete again → should fail (already completed)
        try {
            $this->returnService->complete($return->id, 3, $admin->id);
            $this->fail('Expected INVALID_TRANSITION for double completion.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 13. partial return → soldOf giảm đúng
    // ==========================================
    public function test_partial_return_reduces_sold_correctly()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product, 10);

        // Partial return 3
        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->completeWithoutReturn($return->id, $admin->id);

        // Check refunded quantity for detail
        $refundedQty = $this->refundService->refundedQuantityForDetail($detail->id);
        $this->assertSame(3, $refundedQty);

        // Check best sellers scope
        $bestSeller = Product::bestSellers()->get()->firstWhere('id', $product->id);
        $this->assertSame(7, (int) $bestSeller->total_sold);
    }

    // ==========================================
    // 14. multiple returns cùng detail trong cap
    // ==========================================
    public function test_multiple_returns_within_cap()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 10);

        // Return 3
        $first = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($first->id, $admin->id);
        $first->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->completeWithoutReturn($first->id, $admin->id);

        // Return 2
        $second = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_WRONG_ITEM,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($second->id, $admin->id);
        $second->update(['refund_reason' => RefundService::REASON_WRONG_ITEM]);
        $this->returnService->completeWithoutReturn($second->id, $admin->id);

        // Return 4 → remaining = 10 - 3 - 2 = 5, try 4 → ok
        $third = $this->returnService->request(
            $order->id,
            $detail->id,
            4,
            RefundService::REASON_DAMAGED_TRANSIT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($third->id, $admin->id);
        $third->update(['refund_reason' => RefundService::REASON_DAMAGED_TRANSIT]);
        $this->returnService->completeWithoutReturn($third->id, $admin->id);

        // Total refunded = 9, remaining = 1
        $totalRefunded = $this->refundService->refundedQuantityForDetail($detail->id);
        $this->assertSame(9, $totalRefunded);

        // Try return 2 → should fail
        try {
            $this->returnService->request(
                $order->id,
                $detail->id,
                2,
                RefundService::REASON_LOST_IN_TRANSIT,
                $user->id,
                'sealed'
            );
            $this->fail('Expected QUANTITY_EXCEEDED.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // 15. refund pending không giảm Net
    // ==========================================
    public function test_refund_pending_does_not_reduce_net()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);

        // Only approved, no refund created yet
        $refundTotal = $this->refundService->refundTotals()['refund_total'];
        $this->assertSame(0.0, $refundTotal);
    }

    // ==========================================
    // 16. refund completed giảm Net
    // ==========================================
    public function test_refund_completed_reduces_net()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->completeWithoutReturn($return->id, $admin->id);

        $refundTotal = $this->refundService->refundTotals()['refund_total'];
        $this->assertEquals(300000, $refundTotal);
    }

    // ==========================================
    // 17. rejected/cancelled không ảnh hưởng revenue
    // ==========================================
    public function test_rejected_cancelled_no_revenue_impact()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        // Request then reject
        $return1 = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->reject($return1->id, $admin->id);

        // Request then cancel
        $return2 = $this->returnService->request(
            $order->id,
            $detail->id,
            1,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->cancel($return2->id, $user->id);

        // No revenue impact
        $refundTotal = $this->refundService->refundTotals()['refund_total'];
        $this->assertSame(0.0, $refundTotal);
        $this->assertSame(0, $this->refundService->refundedQuantityForDetail($detail->id));
    }

    // ==========================================
    // 18. Service không enforce admin role — chỉ controller enforce
    // ==========================================
    public function test_service_does_not_enforce_admin_role()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        // Service layer không phân biệt admin/user — chỉ kiểm tra status transition.
        // approve từ requested → approved là hợp lệ nên thành công bất kể caller là ai.
        $approved = $this->returnService->approve($return->id, $user->id);
        $this->assertSame(ReturnRequest::STATUS_APPROVED, $approved->status);
        $this->assertSame($user->id, (int) $approved->handled_by);

        // Reject từ approved → rejected cũng hợp lệ.
        $rejected = $this->returnService->reject($approved->id, $user->id);
        $this->assertSame(ReturnRequest::STATUS_REJECTED, $rejected->status);

        // Double approve bị chặn (requested → approved → approved không hợp lệ).
        $return2 = $this->returnService->request(
            $order->id,
            $detail->id,
            1,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return2->id, $user->id);
        try {
            $this->returnService->approve($return2->id, $user->id);
            $this->fail('Expected INVALID_TRANSITION for double approve.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 19. completed order vẫn giữ order_status
    // ==========================================
    public function test_completed_order_keeps_order_status()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $order->update(['order_status' => 'completed']);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->completeWithoutReturn($return->id, $admin->id);

        // Order status should remain completed
        $this->assertSame('completed', $order->fresh()->order_status);
    }

    // ==========================================
    // 20. duplicate completion không double refund
    // ==========================================
    public function test_duplicate_completion_no_double_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->completeWithoutReturn($return->id, $admin->id);

        $refundAmount = $this->refundService->refundedAmountForOrder($order->id);
        $this->assertEquals(300000, $refundAmount);

        // Try complete again → should fail
        try {
            $this->returnService->completeWithoutReturn($return->id, $admin->id);
            $this->fail('Expected INVALID_TRANSITION for duplicate completion.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }

        // Amount should not double
        $this->assertEquals(300000, $this->refundService->refundedAmountForOrder($order->id));
    }

    // ==========================================
    // 21. wrong item exact detail
    // ==========================================
    public function test_wrong_item_exact_detail()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_WRONG_ITEM,
            $user->id,
            'sealed'
        );
        // Set refund_reason to a valid RefundService reason
        $return->update(['refund_reason' => RefundService::REASON_WRONG_ITEM]);
        $this->returnService->approve($return->id, $admin->id);
        $completed = $this->returnService->completeWithoutReturn($return->id, $admin->id);

        $refund = RefundTransaction::find($completed->refund_transaction_id);
        $this->assertSame(RefundService::REASON_WRONG_ITEM, $refund->reason);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
    }

    // ==========================================
    // 22. full regression - complete flow
    // ==========================================
    public function test_full_regression_return_flow()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 50);
        [$order, $detail] = $this->makePaidOrder($user, $product, 10);

        // Customer creates return request
        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            4,
            'Sản phẩm bị hư',
            $user->id,
            'sealed'
        );
        $this->assertSame(ReturnRequest::STATUS_REQUESTED, $return->status);

        // Admin approve
        $this->returnService->approve($return->id, $admin->id);
        $return = $return->fresh();
        $this->assertSame(ReturnRequest::STATUS_APPROVED, $return->status);
        $this->assertNull($return->refund_transaction_id);

        // Admin receive goods
        $this->returnService->receive($return->id, $admin->id);
        $return = $return->fresh();
        $this->assertSame(ReturnRequest::STATUS_RECEIVED, $return->status);
        $this->assertNotNull($return->received_at);

        // Admin inspect
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_RESELLABLE, $admin->id);
        $return = $return->fresh();
        $this->assertSame(ReturnRequest::INSPECTION_RESELLABLE, $return->inspection);

        // Admin complete with restock
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $initialStock = $product->fresh()->quantity;
        $completed = $this->returnService->complete($return->id, 4, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertNotNull($completed->refund_transaction_id);
        $this->assertSame(4, $completed->restocked_qty);

        // Refund
        $refund = RefundTransaction::find($completed->refund_transaction_id);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
        $this->assertEquals(400000, (float) $refund->amount);

        // Stock incremented
        $this->assertEquals($initialStock + 4, $product->fresh()->quantity);

        // Revenue impact
        $refundTotal = $this->refundService->refundTotals()['refund_total'];
        $this->assertEquals(400000, $refundTotal);

        // Best sellers
        $bestSeller = Product::bestSellers()->get()->firstWhere('id', $product->id);
        $this->assertSame(6, (int) $bestSeller->total_sold);

        // Order status unchanged
        $this->assertSame('completed', $order->fresh()->order_status);
    }

    // ==========================================
    // Bonus: approve then complete without return flow
    // ==========================================
    public function test_approve_complete_without_return_flow()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 50);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            'Không cần trả hàng',
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $completed = $this->returnService->completeWithoutReturn($return->id, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertNotNull($completed->refund_transaction_id);

        $refund = RefundTransaction::find($completed->refund_transaction_id);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
        $this->assertEquals(300000, (float) $refund->amount);

        // No stock change
        $this->assertEquals(50, $product->fresh()->quantity);
    }

    // ==========================================
    // Bonus: inspect defective + dispose
    // ==========================================
    public function test_inspect_dispose_does_not_restock()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 50);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_DISPOSE, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);

        $initialStock = $product->fresh()->quantity;
        $completed = $this->returnService->complete($return->id, 0, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertEquals($initialStock, $product->fresh()->quantity);
    }

    // ==========================================
    // Bonus: restock_qty > quantity → blocked
    // ==========================================
    public function test_restock_qty_exceeds_quantity_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 50);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_RESELLABLE, $admin->id);

        try {
            $this->returnService->complete($return->id, 3, $admin->id); // 3 > 2
            $this->fail('Expected QUANTITY_EXCEEDED for restock_qty > quantity.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // Bonus: receive without approve → blocked
    // ==========================================
    public function test_receive_without_approve_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );

        try {
            $this->returnService->receive($return->id, $admin->id);
            $this->fail('Expected INVALID_TRANSITION.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // Bonus: inspect without receive → blocked
    // ==========================================
    public function test_inspect_without_receive_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);

        try {
            $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_RESELLABLE, $admin->id);
            $this->fail('Expected INVALID_TRANSITION.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // Bonus: complete without inspect → blocked
    // ==========================================
    public function test_complete_without_inspect_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);

        try {
            $this->returnService->complete($return->id, 0, $admin->id);
            $this->fail('Expected INVALID_TRANSITION.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // Bonus: quantity = 0 → blocked
    // ==========================================
    public function test_quantity_zero_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        try {
            $this->returnService->request(
                $order->id,
                $detail->id,
                0,
                RefundService::REASON_MANUFACTURER_DEFECT,
                $user->id,
                'sealed'
            );
            $this->fail('Expected DETAIL_MISMATCH for quantity 0.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::DETAIL_MISMATCH, $e->errorCode);
        }
    }

    // ==========================================
    // HARDENING: refund_reason validation
    // ==========================================

    public function test_missing_refund_reason_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);

        // refund_reason is null → should block
        try {
            $this->returnService->completeWithoutReturn($return->id, $admin->id);
            $this->fail('Expected INVALID_REASON for missing refund_reason.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    public function test_invalid_refund_reason_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => 'NOT_A_REAL_REASON']);

        try {
            $this->returnService->completeWithoutReturn($return->id, $admin->id);
            $this->fail('Expected INVALID_REASON for invalid refund_reason.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    public function test_valid_refund_reason_works()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_DAMAGED_TRANSIT]);

        $completed = $this->returnService->completeWithoutReturn($return->id, $admin->id);
        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);

        $refund = RefundTransaction::find($completed->refund_transaction_id);
        $this->assertSame(RefundService::REASON_DAMAGED_TRANSIT, $refund->reason);
    }

    // ==========================================
    // HARDENING: atomicity — refund + restock same transaction
    // ==========================================

    public function test_resellable_atomic_restock_happens_with_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 10);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            3,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_RESELLABLE, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);

        $initialStock = $product->fresh()->quantity;
        $completed = $this->returnService->complete($return->id, 3, $admin->id);

        // Both refund and restock happened atomically
        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertSame(3, $completed->restocked_qty);
        $this->assertEquals($initialStock + 3, $product->fresh()->quantity);

        $refund = RefundTransaction::find($completed->refund_transaction_id);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
    }

    public function test_defective_no_restock_but_refund_created()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 10);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_DEFECTIVE, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);

        $initialStock = $product->fresh()->quantity;
        $completed = $this->returnService->complete($return->id, 0, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_COMPLETED, $completed->status);
        $this->assertEquals($initialStock, $product->fresh()->quantity);
        $this->assertNotNull($completed->refund_transaction_id);

        $refund = RefundTransaction::find($completed->refund_transaction_id);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
    }

    // ==========================================
    // HARDENING: cancellation/rejected no refund
    // ==========================================

    public function test_customer_cancelled_no_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->cancel($return->id, $user->id);

        $this->assertSame(ReturnRequest::STATUS_CANCELLED, $return->fresh()->status);
        $this->assertNull($return->fresh()->refund_transaction_id);

        // No refund records created
        $refundCount = RefundTransaction::where('order_id', $order->id)->count();
        $this->assertSame(0, $refundCount);
    }

    public function test_rejected_no_refund()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000);
        [$order, $detail] = $this->makePaidOrder($user, $product);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->reject($return->id, $admin->id);

        $this->assertSame(ReturnRequest::STATUS_REJECTED, $return->fresh()->status);
        $this->assertNull($return->fresh()->refund_transaction_id);

        $refundCount = RefundTransaction::where('order_id', $order->id)->count();
        $this->assertSame(0, $refundCount);
    }

    // ==========================================
    // HARDENING: refund_reason in received → complete path
    // ==========================================

    public function test_complete_with_inspect_missing_refund_reason_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(100000, 10);
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);

        $return = $this->returnService->request(
            $order->id,
            $detail->id,
            2,
            RefundService::REASON_MANUFACTURER_DEFECT,
            $user->id,
            'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $this->returnService->receive($return->id, $admin->id);
        $this->returnService->inspect($return->id, ReturnRequest::INSPECTION_RESELLABLE, $admin->id);

        // refund_reason still null → should block
        try {
            $this->returnService->complete($return->id, 2, $admin->id);
            $this->fail('Expected INVALID_REASON for missing refund_reason in complete path.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }
}
