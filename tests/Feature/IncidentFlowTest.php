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
use App\Services\BatchIncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class IncidentFlowTest extends TestCase
{
    use RefreshDatabase;

    private BatchIncidentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->service = app(BatchIncidentService::class);
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

    private function makeReservation(User $user, Batch $batch, string $status = 'reserved', float $deposit = 100000): Reservation
    {
        return Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => $deposit,
            'status' => $status,
        ]);
    }

    private function makeOrder(User $user, Product $product, ?Batch $batch, ?Reservation $reservation, string $method, string $pay, string $status, int $price, int $qty): array
    {
        $order = Order::create([
            'user_id' => $user->id,
            'batch_id' => $batch?->id,
            'reservation_id' => $reservation?->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test',
            'total_amount' => $price * $qty,
            'payment_method' => $method,
            'payment_status' => $pay,
            'order_status' => $status,
        ]);

        $detail = OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $price * $qty,
        ]);

        return [$order, $detail];
    }

    public function test_record_rejects_invalid_type()
    {
        $batch = $this->makeBatch(Product::factory()->create());

        try {
            $this->service->record($batch->id, 'alien_invasion', null, null);
            $this->fail('Expected INVALID_REASON.');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::INVALID_REASON, $e->errorCode);
        }
    }

    public function test_shortage_open_batch_reserved_refunded_once_no_duplicate()
    {
        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeBatch(Product::factory()->create(), 'open');
        $reservation = $this->makeReservation($user, $batch);

        $incident = $this->service->record($batch->id, 'supplier_shortage', 'NCC báo thiếu', null);
        $summary = $this->service->resolveBatchShortage($incident->id);

        $this->assertSame('failed', $batch->fresh()->status);
        $this->assertSame('refunded', $reservation->fresh()->status);
        // Ownership: reserved do markFailed xử lý — đúng 1 refund, không double.
        $this->assertSame(1, RefundTransaction::where('reservation_id', $reservation->id)->count());
        $this->assertSame([], $summary['skipped']);

        // Chạy lại: không sinh tiền mới.
        $again = $this->service->resolveBatchShortage($incident->id);
        $this->assertSame(1, RefundTransaction::where('reservation_id', $reservation->id)->count());
        $this->assertSame([], $again['refunded']);
    }

    public function test_shortage_converted_paid_gets_full_refund()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);
        $batch = $this->makeBatch($product, 'success');
        $reservation = $this->makeReservation($user, $batch, 'converted', 100000);
        [$order] = $this->makeOrder($user, $product, $batch, $reservation, 'balance', Order::PAY_PAID, 'pending', 300000, 2);

        $incident = $this->service->record($batch->id, 'supplier_shortage', null, $admin->id);
        $summary = $this->service->resolveBatchShortage($incident->id, $admin->id);

        $this->assertContains($order->id, $summary['refunded']);

        $refund = RefundTransaction::where('order_id', $order->id)->first();
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
        $this->assertEquals(600000, (float) $refund->amount);
        $this->assertSame('SUPPLIER_SHORTAGE', $refund->reason);
        $this->assertNotNull($refund->payment_id);
    }

    public function test_shortage_converted_unpaid_cancelled_no_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);
        $batch = $this->makeBatch($product, 'success');
        $reservation = $this->makeReservation($user, $batch, 'converted', 100000);
        [$order] = $this->makeOrder($user, $product, $batch, $reservation, 'balance', Order::PAY_AWAITING, 'pending', 300000, 2);

        $incident = $this->service->record($batch->id, 'supplier_shortage', null, null);
        $summary = $this->service->resolveBatchShortage($incident->id);

        $this->assertContains($order->id, $summary['cancelled']);
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(0, RefundTransaction::where('order_id', $order->id)->count());
    }

    public function test_shortage_completed_paid_keeps_status_with_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);
        $batch = $this->makeBatch($product, 'success');
        $reservation = $this->makeReservation($user, $batch, 'converted', 100000);
        [$order] = $this->makeOrder($user, $product, $batch, $reservation, 'balance', Order::PAY_PAID, 'completed', 300000, 2);

        $incident = $this->service->record($batch->id, 'supplier_shortage', null, null);
        $this->service->resolveBatchShortage($incident->id);

        $this->assertSame('completed', $order->fresh()->order_status);
        $refund = RefundTransaction::where('order_id', $order->id)->first();
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
        $this->assertEquals(600000, (float) $refund->amount);
    }

    public function test_lost_in_transit_partial_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000, 'quantity' => 100]);
        $batch = $this->makeBatch($product, 'success');
        [$order, $detail] = $this->makeOrder($user, $product, $batch, null, 'qr', Order::PAY_PAID, 'shipping', 100000, 5);

        $incident = $this->service->record($batch->id, 'lost_in_transit', 'Mất 2/5 kiện', null);
        $refund = $this->service->resolveOrderRefund(
            $incident->id,
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 2]],
            null
        );

        $this->assertEquals(200000, (float) $refund->amount);
        $this->assertSame('LOST_IN_TRANSIT', $refund->reason);
        $this->assertSame('shipping', $order->fresh()->order_status);
        $this->assertSame(100, (int) $product->fresh()->quantity);
    }

    public function test_damaged_partial_refund_no_stock_restore()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000, 'quantity' => 50]);
        $batch = $this->makeBatch($product, 'success');
        [$order, $detail] = $this->makeOrder($user, $product, $batch, null, 'qr', Order::PAY_PAID, 'completed', 100000, 4);

        $incident = $this->service->record($batch->id, 'damaged', 'Vỡ 1', null);
        $refund = $this->service->resolveOrderRefund(
            $incident->id,
            $order->id,
            [['order_detail_id' => $detail->id, 'quantity' => 1]],
            null
        );

        $this->assertEquals(100000, (float) $refund->amount);
        $this->assertSame('completed', $order->fresh()->order_status);
        $this->assertSame(50, (int) $product->fresh()->quantity);
    }

    public function test_wrong_item_refunds_only_wrong_detail()
    {
        $user = User::factory()->create(['role' => 'user']);
        $a = Product::factory()->create(['price' => 100000]);
        $b = Product::factory()->create(['price' => 200000]);
        $batch = $this->makeBatch($a, 'success');

        $order = Order::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test',
            'total_amount' => 500000,
            'payment_method' => 'qr',
            'payment_status' => Order::PAY_PAID,
            'order_status' => 'completed',
        ]);
        $detailA = OrderDetail::create(['order_id' => $order->id, 'product_id' => $a->id, 'product_name' => $a->name, 'price' => 100000, 'quantity' => 1, 'subtotal' => 100000]);
        OrderDetail::create(['order_id' => $order->id, 'product_id' => $b->id, 'product_name' => $b->name, 'price' => 200000, 'quantity' => 2, 'subtotal' => 400000]);

        $incident = $this->service->record($batch->id, 'wrong_item', 'Giao nhầm A', null);
        $refund = $this->service->resolveOrderRefund(
            $incident->id,
            $order->id,
            [['order_detail_id' => $detailA->id, 'quantity' => 1]],
            null
        );

        $this->assertEquals(100000, (float) $refund->amount);
        $this->assertCount(1, $refund->details);
        $this->assertSame($detailA->id, (int) $refund->details->first()->order_detail_id);
    }

    public function test_quality_defect_completed_order()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        $batch = $this->makeBatch($product, 'success');
        [$order, $detail] = $this->makeOrder($user, $product, $batch, null, 'qr', Order::PAY_PAID, 'completed', 100000, 3);

        $incident = $this->service->record($batch->id, 'quality_defect', 'Lỗi sơn', null);
        $refund = $this->service->resolveOrderRefund($incident->id, $order->id, [], null);

        $this->assertEquals(300000, (float) $refund->amount);
        $this->assertSame('MANUFACTURER_DEFECT', $refund->reason);
        $this->assertSame('completed', $order->fresh()->order_status);
    }

    public function test_delivery_failed_unpaid_cancels_without_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        $batch = $this->makeBatch($product, 'success');
        [$order] = $this->makeOrder($user, $product, $batch, null, 'cod', 'unpaid', 'shipping', 100000, 2);

        $incident = $this->service->record($batch->id, 'delivery_failed', 'Khách từ chối', null);
        $result = $this->service->resolveOrderRefund($incident->id, $order->id, [], null);

        $this->assertNull($result);
        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame(0, RefundTransaction::count());
    }

    public function test_delivery_failed_paid_shipping_cancels_with_refund()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        $batch = $this->makeBatch($product, 'success');
        [$order] = $this->makeOrder($user, $product, $batch, null, 'qr', Order::PAY_PAID, 'shipping', 100000, 2);

        $incident = $this->service->record($batch->id, 'delivery_failed', 'Lạc hàng', null);
        $refund = $this->service->resolveOrderRefund($incident->id, $order->id, [], null);

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertEquals(200000, (float) $refund->amount);
        $this->assertSame('DELIVERY_FAILED', $refund->reason);
    }

    public function test_same_type_different_orders_are_independent_cases()
    {
        $batch = $this->makeBatch(Product::factory()->create(), 'success');

        $i1 = $this->service->record($batch->id, 'damaged', 'Case 1', null);
        $i2 = $this->service->record($batch->id, 'damaged', 'Case 2', null);

        $this->assertNotSame($i1->id, $i2->id);
        $this->assertSame(2, BatchIncident::where('batch_id', $batch->id)->where('type', 'damaged')->count());
    }

    public function test_incident_routes_require_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);
        $batch = $this->makeBatch(Product::factory()->create());

        $this->get('/admin/batch-incidents')->assertRedirect('/login');
        $this->actingAs($customer)->get('/admin/batch-incidents')->assertForbidden();
        $this->actingAs($customer)->post('/admin/batch-incidents', [
            'batch_id' => $batch->id,
            'type' => 'damaged',
        ])->assertForbidden();

        $this->actingAs($admin)->get('/admin/batch-incidents')->assertOk();
        $this->actingAs($admin)->post('/admin/batch-incidents', [
            'batch_id' => $batch->id,
            'type' => 'damaged',
            'description' => 'Vỡ góc hộp',
        ])->assertRedirect();

        $this->assertDatabaseHas('batch_incidents', [
            'batch_id' => $batch->id,
            'type' => 'damaged',
        ]);
        $this->assertDatabaseHas('admin_action_logs', ['action' => 'report_incident']);
    }

    public function test_admin_can_resolve_incident_via_http()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        $batch = $this->makeBatch($product, 'success');
        [$order] = $this->makeOrder($user, $product, $batch, null, 'qr', Order::PAY_PAID, 'completed', 100000, 2);

        $incident = $this->service->record($batch->id, 'damaged', null, $admin->id);

        $this->actingAs($admin)->get("/admin/batch-incidents/{$incident->id}")->assertOk();
        $this->actingAs($admin)->post("/admin/batch-incidents/{$incident->id}/resolve", [
            'order_id' => $order->id,
        ])->assertRedirect();

        $refund = RefundTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($refund);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $refund->status);
        $this->assertEquals(200000, (float) $refund->amount);
    }
}
