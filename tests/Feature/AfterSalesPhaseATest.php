<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
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

class AfterSalesPhaseATest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeOrder(int $qty = 5): array
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

    private function makeEvidence(int $count = 1): array
    {
        Storage::fake('public');
        $paths = [];
        for ($i = 0; $i < $count; $i++) {
            $paths[] = UploadedFile::fake()->image("ev{$i}.jpg", 100, 100)->store('warranty-evidence/test', 'public');
        }

        return $paths;
    }

    // A3: warranty admin invalid actions must redirect with error, never 500.
    public function test_warranty_admin_double_approve_no_500()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $w = app(WarrantyService::class)->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $this->makeEvidence(1)
        );

        $this->actingAs($admin)->post(route('admin.warranties.approve', $w))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('approved', $w->fresh()->status);

        // Second approve: invalid transition → back with error, NOT 500.
        $r = $this->actingAs($admin)->post(route('admin.warranties.approve', $w));
        $r->assertRedirect()->assertSessionHas('error');
        $this->assertNotEquals(500, $r->status());
        $this->assertEquals('approved', $w->fresh()->status);
    }

    public function test_warranty_admin_complete_from_wrong_state_no_500()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $w = app(WarrantyService::class)->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id, $this->makeEvidence(1)
        );

        $r = $this->actingAs($admin)->post(route('admin.warranties.complete', $w), [
            'resolution' => 'repaired',
        ]);
        $r->assertRedirect()->assertSessionHas('error');
        $this->assertNotEquals(500, $r->status());
        $this->assertEquals('requested', $w->fresh()->status);
    }

    // A1: return complete requires refund_reason via HTTP; creates refund.
    public function test_return_complete_via_http_with_refund_reason()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 2, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);
        $svc->receive($ret->id, $admin->id);
        $svc->inspect($ret->id, 'resellable', $admin->id);

        $this->actingAs($admin)->post(route('admin.returns.complete', $ret), [
            'restocked_qty' => 2,
            'refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT,
        ])->assertRedirect()->assertSessionHas('success');

        $ret = $ret->fresh();
        $this->assertEquals('completed', $ret->status);
        $this->assertEquals(RefundService::REASON_MANUFACTURER_DEFECT, $ret->refund_reason);
        $this->assertNotNull($ret->refund_transaction_id);
        $this->assertEquals(2, $ret->restocked_qty);
        $this->assertEquals(102, $product->fresh()->quantity); // 100 + 2 restock
    }

    public function test_return_complete_via_http_without_refund_reason_rejected()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);
        $svc->receive($ret->id, $admin->id);
        $svc->inspect($ret->id, 'defective', $admin->id);

        $this->actingAs($admin)->post(route('admin.returns.complete', $ret), [
            'restocked_qty' => 0,
        ])->assertSessionHasErrors('refund_reason');

        $this->assertEquals('received', $ret->fresh()->status);
        $this->assertNull($ret->fresh()->refund_transaction_id);
    }

    public function test_return_complete_no_return_via_http_with_refund_reason()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);
        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);

        $this->actingAs($admin)->post(route('admin.returns.complete-no-return', $ret), [
            'refund_reason' => RefundService::REASON_WRONG_ITEM,
        ])->assertRedirect()->assertSessionHas('success');

        $ret = $ret->fresh();
        $this->assertEquals('completed', $ret->status);
        $this->assertEquals(RefundService::REASON_WRONG_ITEM, $ret->refund_reason);
        $this->assertNotNull($ret->refund_transaction_id);
        $this->assertEquals(100, $product->fresh()->quantity); // no restock
    }

    // A4: rejected flow consistent (warranty complete with rejected resolution).
    public function test_warranty_rejected_flows()
    {
        [$user, $product, $order, $detail] = $this->makeOrder(10);
        $admin = $this->makeAdmin();
        $svc = app(WarrantyService::class);

        // Reject from approved.
        $w1 = $svc->request($order->id, $detail->id, 1, 'Lỗi', null, $user->id, $this->makeEvidence(1));
        $this->actingAs($admin)->post(route('admin.warranties.approve', $w1))->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.warranties.reject', $w1))->assertSessionHas('success');
        $w1 = $w1->fresh();
        $this->assertEquals('rejected', $w1->status);
        $this->assertEquals('rejected', $w1->resolution);

        // Complete with rejected resolution is forbidden (F3 semantics).
        $w2 = $svc->request($order->id, $detail->id, 1, 'Lỗi', null, $user->id, $this->makeEvidence(1));
        $svc->approve($w2->id, $admin->id);
        $svc->processing($w2->id, $admin->id);
        try {
            $svc->complete($w2->id, 'rejected', $admin->id);
            $this->fail('Expected INVALID_REASON for complete(rejected).');
        } catch (\App\Exceptions\RefundException $e) {
            $this->assertStringContainsString('reject', $e->getMessage());
        }
        $w2 = $w2->fresh();
        $this->assertEquals('processing', $w2->status);
        $this->assertNull($w2->resolution);

        // HTTP layer rejects it via validation, not 500.
        $r = $this->actingAs($admin)->post(route('admin.warranties.complete', $w2), [
            'resolution' => 'rejected',
        ]);
        $r->assertSessionHasErrors('resolution');
        $this->assertEquals('processing', $w2->fresh()->status);
    }

    // A5: detail pages expose received/completed fields, labels, refund status/date.
    public function test_customer_detail_pages_expose_phase_a_fields()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();

        $rsvc = app(ReturnService::class);
        $ret = $rsvc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $rsvc->approve($ret->id, $admin->id);
        $rsvc->receive($ret->id, $admin->id);
        $rsvc->inspect($ret->id, 'resellable', $admin->id);
        $rsvc->complete($ret->id, 1, $admin->id, RefundService::REASON_MANUFACTURER_DEFECT);

        $show = $this->actingAs($user)->get(route('returns.show', $ret));
        $show->assertOk();
        $show->assertSee('Ngày nhận hàng');
        $show->assertSee('Có thể bán lại (Resellable)');
        $show->assertSee('Trạng thái hoàn tiền');
        $show->assertSee('Ngày hoàn tiền');
        $show->assertSee(RefundService::REASON_MANUFACTURER_DEFECT);

        $wsvc = app(WarrantyService::class);
        $w = $wsvc->request($order->id, $detail->id, 1, 'Lỗi', null, $user->id, $this->makeEvidence(1));
        $wsvc->approve($w->id, $admin->id);
        $wsvc->processing($w->id, $admin->id);
        $wsvc->complete($w->id, 'repaired', $admin->id);

        $wshow = $this->actingAs($user)->get(route('warranties.show', $w));
        $wshow->assertOk();
        $wshow->assertSee('Ngày nhận xử lý');
        $wshow->assertSee('Ngày hoàn thành');
    }

    // A6: warranty create only lists details with remaining quantity.
    public function test_warranty_create_filters_fully_consumed_details()
    {
        [$user, $product, $order, $detail] = $this->makeOrder(2);
        app(WarrantyService::class)->request(
            $order->id, $detail->id, 2, 'Lỗi', null, $user->id, $this->makeEvidence(1)
        );

        $r = $this->actingAs($user)->get(route('warranties.create', ['order_id' => $order->id]));
        $r->assertOk();
        $r->assertDontSee($product->name);
        $r->assertSee('Không có sản phẩm nào còn đủ số lượng bảo hành');
    }
}
