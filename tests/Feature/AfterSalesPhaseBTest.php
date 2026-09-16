<?php

namespace Tests\Feature;

use App\Exceptions\RefundException;
use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\RefundService;
use App\Services\ReturnService;
use App\Services\WarrantyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AfterSalesPhaseBTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeOrder(int $qty = 5, ?int $batchId = null): array
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['price' => 100000, 'quantity' => 100]);
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Addr',
            'total_amount' => $product->price * $qty,
            'payment_method' => 'qr',
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'delivered_at' => now()->subDay(),
            'batch_id' => $batchId,
        ]);
        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => $qty,
            'subtotal' => $product->price * $qty,
        ]);

        return [$user, $product, $order, $detail];
    }

    private function makeBatch(Product $product): Batch
    {
        return Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->subDay(),
            'status' => 'success',
        ]);
    }

    // B1: return double submit via HTTP creates only one request.
    public function test_return_double_submit_creates_single_request()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $payload = [
            'order_id' => $order->id,
            'order_detail_id' => $detail->id,
            'quantity' => 1,
            'reason' => 'Lỗi double click',
            'runner_condition' => 'sealed',
        ];

        $first = $this->actingAs($user)->post(route('returns.store'), $payload);
        $first->assertRedirect();
        $second = $this->actingAs($user)->post(route('returns.store'), $payload);
        $second->assertRedirect();

        $this->assertEquals(1, ReturnRequest::count());
        $existing = ReturnRequest::first();
        $second->assertRedirect(route('returns.show', $existing));
    }

    // B1: warranty double submit via HTTP creates only one request.
    public function test_warranty_double_submit_creates_single_request()
    {
        Storage::fake('public');
        [$user, $product, $order, $detail] = $this->makeOrder(10);

        $post = function () use ($user, $order, $detail) {
            return $this->actingAs($user)->post(route('warranties.store'), [
                'order_id' => $order->id,
                'order_detail_id' => $detail->id,
                'quantity' => 1,
                'reason' => 'Lỗi double click',
                'evidence' => [UploadedFile::fake()->image('ev.jpg', 100, 100)],
            ]);
        };

        $post()->assertRedirect();
        $second = $post();
        $second->assertRedirect();

        $this->assertEquals(1, \App\Models\WarrantyRequest::count());
    }

    // B1 negative control: different quantities are distinct requests, not deduped.
    public function test_return_different_quantities_not_deduped()
    {
        [$user, $product, $order, $detail] = $this->makeOrder(10);
        $base = [
            'order_id' => $order->id,
            'order_detail_id' => $detail->id,
            'reason' => 'Lỗi',
            'runner_condition' => 'sealed',
        ];

        $this->actingAs($user)->post(route('returns.store'), $base + ['quantity' => 1])->assertRedirect();
        $this->actingAs($user)->post(route('returns.store'), $base + ['quantity' => 2])->assertRedirect();

        $this->assertEquals(2, ReturnRequest::count());
    }

    // B2: inspect twice blocked.
    public function test_inspect_twice_blocked()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);
        $svc->receive($ret->id, $admin->id);
        $svc->inspect($ret->id, 'resellable', $admin->id);

        try {
            $svc->inspect($ret->id, 'defective', $admin->id);
            $this->fail('Expected INVALID_TRANSITION for second inspect.');
        } catch (RefundException $e) {
            $this->assertStringContainsString('đã được inspect', $e->getMessage());
        }

        // HTTP layer surfaces back-with-error, not 500.
        $r = $this->actingAs($admin)->post(route('admin.returns.inspect', $ret), [
            'inspection' => 'defective',
        ]);
        $r->assertRedirect()->assertSessionHas('error');
        $this->assertNotEquals(500, $r->status());
        $this->assertEquals('resellable', $ret->fresh()->inspection);
    }

    // B3+B4: batch-order return does NOT restock (stock never deducted).
    public function test_batch_order_return_does_not_restock()
    {
        [$user, $product, $order, $detail] = $this->makeOrder(5);
        $batch = $this->makeBatch($product);
        $order->update(['batch_id' => $batch->id]);

        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 2, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);
        $svc->receive($ret->id, $admin->id);
        $svc->inspect($ret->id, 'resellable', $admin->id);
        $svc->complete($ret->id, 2, $admin->id, RefundService::REASON_MANUFACTURER_DEFECT);

        $ret = $ret->fresh();
        $this->assertEquals('completed', $ret->status);
        $this->assertNotNull($ret->refund_transaction_id); // refund still happens
        $this->assertEquals(100, $product->fresh()->quantity); // no restock
    }

    // B3 control: cart order resellable still restocks.
    public function test_cart_order_resellable_still_restocks()
    {
        [$user, $product, $order, $detail] = $this->makeOrder(5);
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 2, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);
        $svc->receive($ret->id, $admin->id);
        $svc->inspect($ret->id, 'resellable', $admin->id);
        $svc->complete($ret->id, 2, $admin->id, RefundService::REASON_MANUFACTURER_DEFECT);

        $this->assertEquals(102, $product->fresh()->quantity);
    }

    // B5: admin refund index handles order refunds + pending, totals monetary only.
    public function test_admin_refund_index_handles_order_and_pending_refunds()
    {
        [$user, $product, $order, $detail] = $this->makeOrder(5);
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);
        $svc->receive($ret->id, $admin->id);
        $svc->inspect($ret->id, 'defective', $admin->id);
        $svc->complete($ret->id, 0, $admin->id, RefundService::REASON_WRONG_ITEM);

        // A pending refund with no reservation (simulates in-flight order refund).
        $pending = RefundTransaction::create([
            'order_id' => $order->id,
            'amount' => 50000,
            'reason' => RefundService::REASON_WRONG_ITEM,
            'status' => RefundTransaction::STATUS_PENDING,
            'type' => RefundTransaction::TYPE_ORDER,
            'admin_id' => $admin->id,
        ]);

        $r = $this->actingAs($admin)->get(route('admin.refunds.index'));
        $r->assertOk();
        $r->assertSee($user->name); // order customer, no reservation
        $r->assertSee('pending');
        $r->assertSee('completed');

        // Total counts only completed monetary refunds (100k), not the pending 50k.
        $r->assertSee('+100.000đ');
        $r->assertDontSee('+150.000đ');
    }

    // B6: admin refund show renders order + pending refunds without crash.
    public function test_admin_refund_show_renders_order_and_pending_refunds()
    {
        [$user, $product, $order, $detail] = $this->makeOrder(5);
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);
        $ret->update(['refund_reason' => RefundService::REASON_WRONG_ITEM]);
        $svc->completeWithoutReturn($ret->id, $admin->id);

        $completed = $ret->fresh()->refundTransaction;
        $r1 = $this->actingAs($admin)->get(route('admin.refunds.show', $completed));
        $r1->assertOk();
        $r1->assertSee('Đã hoàn');
        $r1->assertSee('Đơn hàng liên quan');
        $r1->assertSee("#{$order->id}");

        $pending = RefundTransaction::create([
            'order_id' => $order->id,
            'amount' => 50000,
            'reason' => RefundService::REASON_WRONG_ITEM,
            'status' => RefundTransaction::STATUS_PENDING,
            'type' => RefundTransaction::TYPE_ORDER,
            'admin_id' => $admin->id,
        ]);
        $r2 = $this->actingAs($admin)->get(route('admin.refunds.show', $pending));
        $r2->assertOk();
        $r2->assertSee('Chờ hoàn');
    }
}
