<?php

namespace Tests\Feature;

use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyRequest;
use App\Services\RefundService;
use App\Services\ReturnService;
use App\Services\WarrantyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReturnWarrantyInteractionTest extends TestCase
{
    use RefreshDatabase;

    private ReturnService $returnService;
    private WarrantyService $warrantyService;
    private RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->returnService = app(ReturnService::class);
        $this->warrantyService = app(WarrantyService::class);
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

    private function makePaidOrder(User $user, Product $product, int $qty = 5): array
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
            'delivered_at' => now()->subDay(),
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

    private function makeEvidencePaths(int $count = 1): array
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
    // 1. Full return blocks warranty on returned qty
    // ==========================================
    public function test_full_return_blocks_warranty()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 3);
        $evidence = $this->makeEvidencePaths(1);

        // Return all 3
        $return = $this->returnService->request(
            $order->id, $detail->id, 3, 'Lỗi', $user->id, 'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->completeWithoutReturn($return->id, $admin->id);

        // Try warranty → remaining = 3 - 3 - 0 - 0 = 0 → blocked
        try {
            $this->warrantyService->request(
                $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
            );
            $this->fail('Expected QUANTITY_EXCEEDED for warranty after full return.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // 2. Partial return allows warranty on remaining
    // ==========================================
    public function test_partial_return_allows_warranty_on_remaining()
    {
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);
        $evidence = $this->makeEvidencePaths(1);

        // Return 2
        $return = $this->returnService->request(
            $order->id, $detail->id, 2, 'Lỗi', $user->id, 'sealed'
        );
        $this->returnService->approve($return->id, $admin->id);
        $return->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $this->returnService->completeWithoutReturn($return->id, $admin->id);

        // Warranty 3 → remaining = 5 - 2 - 0 - 0 = 3 → ok
        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 3, 'Lỗi', null, $user->id, $evidence
        );
        $this->assertSame(WarrantyRequest::STATUS_REQUESTED, $warranty->status);
    }

    // ==========================================
    // 3. Pending return holds warranty quota
    // ==========================================
    public function test_pending_return_holds_warranty_quota()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);
        $evidence = $this->makeEvidencePaths(1);

        // Pending return: 3
        $return = $this->returnService->request(
            $order->id, $detail->id, 3, 'Lỗi', $user->id, 'sealed'
        );

        // Warranty 3 → remaining = 5 - 0 - 3 - 0 = 2 → blocked
        try {
            $this->warrantyService->request(
                $order->id, $detail->id, 3, 'Lỗi', null, $user->id, $evidence
            );
            $this->fail('Expected QUANTITY_EXCEEDED for warranty with pending return.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // 4. Runner opened → return blocked, warranty allowed
    // ==========================================
    public function test_runner_opened_blocks_return_allows_warranty()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product);
        $evidence = $this->makeEvidencePaths(1);

        // Return with runner opened → blocked
        try {
            $this->returnService->request(
                $order->id, $detail->id, 1, 'Lỗi', $user->id, 'opened'
            );
            $this->fail('Expected INVALID_TRANSITION for opened runner return.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }

        // Warranty with runner opened → allowed (runner opened is OK for warranty)
        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi runner', null, $user->id, $evidence
        );
        $this->assertSame(WarrantyRequest::STATUS_REQUESTED, $warranty->status);
    }

    // ==========================================
    // 5. Pending warranty holds return quota
    // ==========================================
    public function test_pending_warranty_holds_return_quota()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);
        $evidence = $this->makeEvidencePaths(1);

        // Pending warranty: 3
        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 3, 'Lỗi', null, $user->id, $evidence
        );

        // Return 3 → remaining = 5 - 0 - 0 - 3 = 2 → blocked
        try {
            $this->returnService->request(
                $order->id, $detail->id, 3, 'Lỗi', $user->id, 'sealed'
            );
            $this->fail('Expected QUANTITY_EXCEEDED for return with pending warranty.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }
    }

    // ==========================================
    // 6. Warranty after 3-day return deadline
    // ==========================================
    public function test_warranty_allowed_after_return_deadline()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        // Delivered 5 days ago → return deadline passed, but warranty (7 days) still valid
        [$order, $detail] = $this->makePaidOrder($user, $product, 3);

        // Override delivered_at to 5 days ago
        $order->update(['delivered_at' => now()->subDays(5)]);

        $evidence = $this->makeEvidencePaths(1);

        // Return → blocked (after 3 days)
        try {
            $this->returnService->request(
                $order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed'
            );
            $this->fail('Expected INVALID_TRANSITION for return after 3 days.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_TRANSITION, $e->errorCode);
        }

        // Warranty → still valid (within 7 days)
        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $evidence
        );
        $this->assertSame(WarrantyRequest::STATUS_REQUESTED, $warranty->status);
    }

    // ==========================================
    // 7. Both return and warranty pending on same detail
    // ==========================================
    public function test_both_return_and_warranty_pending_on_same_detail()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 10);
        $evidence = $this->makeEvidencePaths(1);

        // Return 3
        $return = $this->returnService->request(
            $order->id, $detail->id, 3, 'Lỗi', $user->id, 'sealed'
        );

        // Warranty 4 → remaining = 10 - 0 - 3 - 0 = 4 → ok
        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 4, 'Lỗi', null, $user->id, $evidence
        );

        // Return 4 more → remaining = 10 - 0 - 3 - 4 = 1 → try 4 → blocked
        try {
            $this->returnService->request(
                $order->id, $detail->id, 4, 'Lỗi khác', $user->id, 'sealed'
            );
            $this->fail('Expected QUANTITY_EXCEEDED for combined quota.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::QUANTITY_EXCEEDED, $e->errorCode);
        }

        // Return 1 more → remaining = 1 → ok
        $return2 = $this->returnService->request(
            $order->id, $detail->id, 1, 'Lỗi khác', $user->id, 'sealed'
        );
        $this->assertSame(1, $return2->quantity);
    }

    // ==========================================
    // 8. Cancelled warranty frees quota
    // ==========================================
    public function test_cancelled_warranty_frees_quota()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        [$order, $detail] = $this->makePaidOrder($user, $product, 5);
        $evidence = $this->makeEvidencePaths(1);

        // Warranty 3
        $warranty = $this->warrantyService->request(
            $order->id, $detail->id, 3, 'Lỗi', null, $user->id, $evidence
        );

        // Cancel
        $this->warrantyService->cancel($warranty->id, $user->id);

        // Return 3 → remaining = 5 - 0 - 0 - 0 = 3 (cancelled warranty freed quota) → ok
        $return = $this->returnService->request(
            $order->id, $detail->id, 3, 'Lỗi', $user->id, 'sealed'
        );
        $this->assertSame(3, $return->quantity);
    }
}
