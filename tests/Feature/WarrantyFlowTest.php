<?php

namespace Tests\Feature;

use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\WarrantyRequest;
use App\Services\RefundService;
use App\Services\ReturnService;
use App\Services\WarrantyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarrantyFlowTest extends TestCase
{
    use RefreshDatabase;

    private WarrantyService $warrantyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warrantyService = app(WarrantyService::class);
    }

    private function makeUser(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeProduct(float $price = 100000, int $stock = 100): Product
    {
        return Product::factory()->create(['price' => $price, 'quantity' => $stock]);
    }

    private function makePaidOrder(User $user, Product $product, int $qty = 5, ?string $deliveredAt = null): array
    {
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test address',
            'total_amount' => $product->price * $qty,
            'payment_method' => 'qr',
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'delivered_at' => $deliveredAt ?? now()->subDay(),
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

    private function makeEvidencePaths(int $count = 2): array
    {
        Storage::fake('public');
        $paths = [];
        for ($i = 0; $i < $count; $i++) {
            $file = UploadedFile::fake()->image("evidence_{$i}.jpg", 100, 100);
            $path = $file->store("warranty-evidence/test", 'public');
            $paths[] = $path;
        }
        return $paths;
    }

    // ==========================================
    // 1. Customer can create warranty request
    // ==========================================
    public function test_customer_create_warranty_request()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(2);

        $warranty = $this->warrantyService->request(
            $order->id,
            $detail->id,
            2,
            'Runner bị thiếu',
            'Runner bị gãy khi lắp ráp',
            $user->id,
            $evidence
        );

        $this->assertSame(WarrantyRequest::STATUS_REQUESTED, $warranty->status);
        $this->assertSame($order->id, $warranty->order_id);
        $this->assertSame($detail->id, $warranty->order_detail_id);
        $this->assertSame(2, $warranty->quantity);
        $this->assertSame($user->id, $warranty->user_id);
        $this->assertNotNull($warranty->evidence);
    }

    // ==========================================
    // 2. Customer cannot request for another user's order
    // ==========================================
    public function test_customer_cannot_request_another_users_order()
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user1, $product);
        $evidence = $this->makeEvidencePaths(1);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                1,
                'Lỗi',
                null,
                $user2->id,
                $evidence
            );
            $this->fail('Expected INVALID_TRANSITION for wrong user.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 3. Warranty after 7 days → blocked
    // ==========================================
    public function test_warranty_after_7_days_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5, now()->subDays(8)->toDateTimeString());
        $evidence = $this->makeEvidencePaths(1);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                1,
                'Lỗi',
                null,
                $user->id,
                $evidence
            );
            $this->fail('Expected INVALID_TRANSITION for expired warranty.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 4. Warranty with delivered_at NULL → blocked
    // ==========================================
    public function test_warranty_with_null_delivered_at_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5, null);
        $evidence = $this->makeEvidencePaths(1);

        // Override delivered_at to null
        $order->update(['delivered_at' => null]);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                1,
                'Lỗi',
                null,
                $user->id,
                $evidence
            );
            $this->fail('Expected INVALID_TRANSITION for null delivered_at.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 5. Warranty with invalid order status → blocked
    // ==========================================
    public function test_warranty_with_invalid_order_status_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $order->update(['order_status' => 'pending']);
        $evidence = $this->makeEvidencePaths(1);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                1,
                'Lỗi',
                null,
                $user->id,
                $evidence
            );
            $this->fail('Expected INVALID_TRANSITION for wrong order status.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 6. Warranty quantity > ordered → blocked
    // ==========================================
    public function test_warranty_quantity_exceeds_ordered_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 3);
        $evidence = $this->makeEvidencePaths(1);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                5,
                'Lỗi',
                null,
                $user->id,
                $evidence
            );
            $this->fail('Expected QUANTITY_EXCEEDED.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // 7. Warranty without evidence → blocked
    // ==========================================
    public function test_warranty_without_evidence_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                1,
                'Lỗi',
                null,
                $user->id,
                null
            );
            $this->fail('Expected INVALID_REASON for missing evidence.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    // ==========================================
    // 8. Warranty with empty evidence array → blocked
    // ==========================================
    public function test_warranty_with_empty_evidence_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                1,
                'Lỗi',
                null,
                $user->id,
                []
            );
            $this->fail('Expected INVALID_REASON for empty evidence.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    // ==========================================
    // 9. Warranty with > 5 evidence images → blocked
    // ==========================================
    public function test_warranty_with_too_many_evidence_blocked()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(6);

        try {
            $this->warrantyService->request(
                $order->id,
                $detail->id,
                1,
                'Lỗi',
                null,
                $user->id,
                $evidence
            );
            $this->fail('Expected INVALID_REASON for too many evidence.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    // ==========================================
    // 10. Admin approve warranty request
    // ==========================================
    public function test_admin_approve_warranty_request()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );

        $approved = $this->warrantyService->approve($warranty->id, $admin->id);

        $this->assertSame(WarrantyRequest::STATUS_APPROVED, $approved->status);
        $this->assertSame($admin->id, $approved->handled_by);
    }

    // ==========================================
    // 11. Admin reject warranty request
    // ==========================================
    public function test_admin_reject_warranty_request()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );

        $rejected = $this->warrantyService->reject($warranty->id, $admin->id);

        $this->assertSame(WarrantyRequest::STATUS_REJECTED, $rejected->status);
        $this->assertSame(WarrantyRequest::RESOLUTION_REJECTED, $rejected->resolution);
    }

    // ==========================================
    // 12. Admin process warranty request
    // ==========================================
    public function test_admin_process_warranty_request()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );
        $this->warrantyService->approve($warranty->id, $admin->id);

        $processing = $this->warrantyService->processing($warranty->id, $admin->id);

        $this->assertSame(WarrantyRequest::STATUS_PROCESSING, $processing->status);
        $this->assertNotNull($processing->received_at);
    }

    // ==========================================
    // 13. Admin complete warranty with resolution
    // ==========================================
    public function test_admin_complete_warranty_with_resolution()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );
        $this->warrantyService->approve($warranty->id, $admin->id);
        $this->warrantyService->processing($warranty->id, $admin->id);

        $completed = $this->warrantyService->complete($warranty->id, 'part_replaced', $admin->id);

        $this->assertSame(WarrantyRequest::STATUS_COMPLETED, $completed->status);
        $this->assertSame('part_replaced', $completed->resolution);
        $this->assertNotNull($completed->completed_at);
    }

    // ==========================================
    // 14. Customer cancel warranty request
    // ==========================================
    public function test_customer_cancel_warranty_request()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );

        $cancelled = $this->warrantyService->cancel($warranty->id, $user->id);

        $this->assertSame(WarrantyRequest::STATUS_CANCELLED, $cancelled->status);
    }

    // ==========================================
    // 15. Customer cannot cancel completed warranty
    // ==========================================
    public function test_customer_cannot_cancel_completed_warranty()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );
        $this->warrantyService->approve($warranty->id, $admin->id);
        $this->warrantyService->processing($warranty->id, $admin->id);
        $this->warrantyService->complete($warranty->id, 'runner_replaced', $admin->id);

        try {
            $this->warrantyService->cancel($warranty->id, $user->id);
            $this->fail('Expected INVALID_TRANSITION for cancelling completed warranty.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 16. Pending warranty holds quota
    // ==========================================
    public function test_pending_warranty_holds_quota()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);
        $evidence = $this->makeEvidencePaths(1);

        // First warranty: 3
        $first = $this->warrantyService->request(
            $order->id, $detail->id, 3, 'Lỗi', null, $user->id, $evidence
        );

        // Try warranty 3 more → remaining = 5 - 0 - 0 - 3 = 2 → should fail
        try {
            $this->warrantyService->request(
                $order->id, $detail->id, 3, 'Lỗi', null, $user->id, $evidence
            );
            $this->fail('Expected QUANTITY_EXCEEDED for pending quota.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }

        // Try warranty 2 → remaining = 2 → should succeed
        $second = $this->warrantyService->request(
            $order->id, $detail->id, 2, 'Lỗi', null, $user->id, $evidence
        );
        $this->assertSame(2, $second->quantity);
    }

    // ==========================================
    // 17. Full warranty flow
    // ==========================================
    public function test_full_warranty_flow()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 3);
        $evidence = $this->makeEvidencePaths(2);

        // Request
        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 2, 'Runner bị gãy', 'Mô tả', $user->id, $evidence
        );
        $this->assertSame(WarrantyRequest::STATUS_REQUESTED, $warranty->status);

        // Approve
        $warranty = $this->warrantyService->approve($warranty->id, $admin->id);
        $this->assertSame(WarrantyRequest::STATUS_APPROVED, $warranty->status);

        // Processing
        $warranty = $this->warrantyService->processing($warranty->id, $admin->id);
        $this->assertSame(WarrantyRequest::STATUS_PROCESSING, $warranty->status);

        // Complete
        $warranty = $this->warrantyService->complete($warranty->id, 'runner_replaced', $admin->id);
        $this->assertSame(WarrantyRequest::STATUS_COMPLETED, $warranty->status);
        $this->assertSame('runner_replaced', $warranty->resolution);
        $this->assertNotNull($warranty->completed_at);

        // No refund transaction created (warranty ≠ refund)
        $refundCount = RefundTransaction::where('order_id', $order->id)->count();
        $this->assertSame(0, $refundCount);
    }

    // ==========================================
    // 18. Wrong user cancel → blocked
    // ==========================================
    public function test_wrong_user_cancel_warranty_blocked()
    {
        $user1 = $this->makeUser();
        $user2 = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user1, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user1->id, $evidence
        );

        try {
            $this->warrantyService->cancel($warranty->id, $user2->id);
            $this->fail('Expected INVALID_TRANSITION for wrong user cancel.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 19. Invalid resolution → blocked
    // ==========================================
    public function test_invalid_resolution_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );
        $this->warrantyService->approve($warranty->id, $admin->id);
        $this->warrantyService->processing($warranty->id, $admin->id);

        try {
            $this->warrantyService->complete($warranty->id, 'invalid_resolution', $admin->id);
            $this->fail('Expected INVALID_REASON for invalid resolution.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    // ==========================================
    // 20. Processing without approve → blocked
    // ==========================================
    public function test_processing_without_approve_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );

        try {
            $this->warrantyService->processing($warranty->id, $admin->id);
            $this->fail('Expected INVALID_TRANSITION for processing without approve.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 21. Complete without processing → blocked
    // ==========================================
    public function test_complete_without_processing_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );
        $this->warrantyService->approve($warranty->id, $admin->id);

        try {
            $this->warrantyService->complete($warranty->id, 'part_replaced', $admin->id);
            $this->fail('Expected INVALID_TRANSITION for complete without processing.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }

    // ==========================================
    // 22. Reject after approve → blocked
    // ==========================================
    public function test_reject_after_approve_blocked()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );
        $this->warrantyService->approve($warranty->id, $admin->id);
        $this->warrantyService->processing($warranty->id, $admin->id);

        try {
            $this->warrantyService->reject($warranty->id, $admin->id);
            $this->fail('Expected INVALID_TRANSITION for reject after processing.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }
    }
}
