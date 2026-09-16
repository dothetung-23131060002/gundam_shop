<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BatchIncidentService;
use App\Services\BatchService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Cross-flow hardening (H1–H5): money + quantity + state đồng thời.
 */
class HardeningFlowTest extends TestCase
{
    use RefreshDatabase;

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

    private function makeOrder(User $user, Product $product, string $method, string $pay, string $status, int $qty, ?Batch $batch = null, ?Reservation $reservation = null): array
    {
        $order = Order::create([
            'user_id' => $user->id,
            'batch_id' => $batch?->id,
            'reservation_id' => $reservation?->id,
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

    private function soldOf(int $productId): ?int
    {
        $row = Product::bestSellers()->get()->firstWhere('id', $productId);

        return $row ? (int) $row->total_sold : null;
    }

    public function test_paid_cancelled_refund_updates_bestseller()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        [$order, $detail] = $this->makeOrder($user, $product, 'qr', Order::PAY_PAID, 'pending', 5);

        $service = app(RefundService::class);
        $result = $service->cancelAndRefundOrder($order->id);
        $service->complete($result['refund']->id, $admin->id);

        $this->assertSame('cancelled', $order->fresh()->order_status);
        // Gross giữ 500k, refund 500k ⇒ net 0; sold về 0 (không còn dòng net).
        $this->assertEquals(500000, (float) Order::paid()->sum('total_amount'));
        $this->assertEquals(500000, (float) RefundTransaction::monetary()->sum('amount'));
        $this->assertNull($this->soldOf($product->id));
    }

    public function test_cod_collect_updates_bestseller()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        [$order] = $this->makeOrder($user, $product, 'cod', 'unpaid', 'shipping', 3);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(3, $this->soldOf($product->id));
    }

    public function test_cod_incident_refund_updates_revenue()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        [$order, $detail] = $this->makeOrder($user, $product, 'cod', Order::PAY_PAID, 'completed', 5, $this->makeBatch($product, 'success'));

        $incidents = app(BatchIncidentService::class);
        $incident = $incidents->record($order->batch_id, 'damaged', null, $admin->id);
        $refund = $incidents->resolveOrderRefund($incident->id, $order->id, [
            ['order_detail_id' => $detail->id, 'quantity' => 2],
        ], $admin->id);

        $this->assertEquals(200000, (float) $refund->amount);
        $this->assertEquals(500000, (float) Order::paid()->sum('total_amount'));
        $this->assertEquals(200000, (float) RefundTransaction::monetary()->sum('amount'));
        $this->assertSame(3, $this->soldOf($product->id));
    }

    public function test_batch_fail_converted_paid_refund_updates_revenue()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);
        $batch = $this->makeBatch($product, 'success');
        $reservation = Reservation::create([
            'user_id' => $user->id, 'batch_id' => $batch->id,
            'quantity' => 2, 'deposit_paid' => 100000, 'status' => 'converted',
        ]);
        [$order] = $this->makeOrder($user, $product, 'balance', Order::PAY_PAID, 'pending', 2, $batch, $reservation);

        $incidents = app(BatchIncidentService::class);
        $incident = $incidents->record($batch->id, 'supplier_shortage', null, $admin->id);
        $incidents->resolveBatchShortage($incident->id, $admin->id);

        $this->assertEquals(600000, (float) Order::paid()->sum('total_amount'));
        $this->assertEquals(600000, (float) RefundTransaction::monetary()->sum('amount'));
    }

    public function test_batch_fail_converted_unpaid_has_no_revenue()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);
        $batch = $this->makeBatch($product, 'open');
        $reservation = Reservation::create([
            'user_id' => $user->id, 'batch_id' => $batch->id,
            'quantity' => 2, 'deposit_paid' => 100000, 'status' => 'converted',
        ]);
        [$order] = $this->makeOrder($user, $product, 'balance', Order::PAY_AWAITING, 'pending', 2, $batch, $reservation);

        app(BatchService::class)->markFailed($batch);

        $this->assertSame('converted', $reservation->fresh()->status);
        $this->assertEquals(0, (float) Order::paid()->sum('total_amount'));
        $this->assertSame(0, RefundTransaction::count());
    }

    public function test_shortage_reserved_and_converted_same_batch_money_correct()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u1 = User::factory()->create(['role' => 'user']);
        $u2 = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);
        $batch = $this->makeBatch($product, 'open');

        $reserved = Reservation::create([
            'user_id' => $u1->id, 'batch_id' => $batch->id,
            'quantity' => 2, 'deposit_paid' => 100000, 'status' => 'reserved',
        ]);
        $converted = Reservation::create([
            'user_id' => $u2->id, 'batch_id' => $batch->id,
            'quantity' => 2, 'deposit_paid' => 100000, 'status' => 'converted',
        ]);
        $this->makeOrder($u2, $product, 'balance', Order::PAY_PAID, 'pending', 2, $batch, $converted);

        $incidents = app(BatchIncidentService::class);
        $incident = $incidents->record($batch->id, 'supplier_shortage', null, $admin->id);
        $summary = $incidents->resolveBatchShortage($incident->id, $admin->id);

        // Reserved: đúng 1 refund (ownership markFailed). Converted: full 600k.
        $this->assertSame(1, RefundTransaction::where('reservation_id', $reserved->id)->count());
        $this->assertEquals(700000, (float) RefundTransaction::monetary()->sum('amount'));
        $this->assertSame([], $summary['skipped']);
    }

    public function test_wrong_item_partial_updates_bestseller_exact_line()
    {
        $user = User::factory()->create(['role' => 'user']);
        $a = Product::factory()->create(['price' => 100000]);
        $b = Product::factory()->create(['price' => 200000]);
        $batch = $this->makeBatch($a, 'success');

        $order = Order::create([
            'user_id' => $user->id, 'batch_id' => $batch->id,
            'customer_name' => 'Test', 'customer_phone' => '0900000000',
            'shipping_address' => 'Test', 'total_amount' => 500000,
            'payment_method' => 'qr', 'payment_status' => Order::PAY_PAID,
            'order_status' => 'completed',
        ]);
        $detailA = OrderDetail::create(['order_id' => $order->id, 'product_id' => $a->id, 'product_name' => $a->name, 'price' => 100000, 'quantity' => 1, 'subtotal' => 100000]);
        OrderDetail::create(['order_id' => $order->id, 'product_id' => $b->id, 'product_name' => $b->name, 'price' => 200000, 'quantity' => 2, 'subtotal' => 400000]);

        $incidents = app(BatchIncidentService::class);
        $incident = $incidents->record($batch->id, 'wrong_item', null, null);
        $incidents->resolveOrderRefund($incident->id, $order->id, [
            ['order_detail_id' => $detailA->id, 'quantity' => 1],
        ], null);

        $this->assertNull($this->soldOf($a->id));
        $this->assertSame(2, $this->soldOf($b->id));
    }

    public function test_deposit_plus_balance_gross_counts_once()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);
        $batch = $this->makeBatch($product, 'success');
        $reservation = Reservation::create([
            'user_id' => $user->id, 'batch_id' => $batch->id,
            'quantity' => 2, 'deposit_paid' => 200000, 'status' => 'converted',
        ]);
        // Balance order total FULL 600k (đã gồm phần cọc).
        $this->makeOrder($user, $product, 'balance', Order::PAY_PAID, 'pending', 2, $batch, $reservation);

        // Thực thu 600k — gross đúng 600k, KHÔNG 800k (deposit không vào gross).
        $this->assertEquals(600000, (float) Order::paid()->sum('total_amount'));
    }

    public function test_forfeiture_repeat_keeps_totals_stable()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        $batch = $this->makeBatch($product, 'success');
        $reservation = Reservation::create([
            'user_id' => $user->id, 'batch_id' => $batch->id,
            'quantity' => 2, 'deposit_paid' => 100000, 'status' => 'reserved',
        ]);
        \Illuminate\Support\Facades\DB::table('reservations')->where('id', $reservation->id)
            ->update(['updated_at' => now()->subDays(30)->toDateTimeString()]);

        Artisan::call('reservations:forfeit-overdue');
        Artisan::call('reservations:forfeit-overdue');

        $this->assertSame(1, RefundTransaction::where('reason', RefundTransaction::REASON_FORFEITED)->count());
        $this->assertEquals(100000, (float) RefundTransaction::forfeitures()->sum('amount'));
        $this->assertEquals(0, (float) RefundTransaction::monetary()->sum('amount'));
    }

    public function test_approve_after_cancel_is_blocked()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        [$order] = $this->makeOrder($user, $product, 'qr', Order::PAY_AWAITING, 'pending', 1);

        // Cancel trước (unpaid → cancel 0đ).
        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", ['order_status' => 'cancelled']);
        $this->assertSame('cancelled', $order->fresh()->order_status);

        // Approve sau cancel → từ chối, không mutation.
        $this->actingAs($admin)->post("/admin/orders/{$order->id}/confirm-payment")
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame(Order::PAY_AWAITING, $order->fresh()->payment_status);

        // Reject sau cancel → từ chối, không mutation.
        $this->actingAs($admin)->post("/admin/orders/{$order->id}/reject-payment", ['reason' => 'x'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame(Order::PAY_AWAITING, $order->fresh()->payment_status);
    }

    public function test_illegal_backward_transitions_blocked()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        [$completed] = $this->makeOrder($user, $product, 'qr', Order::PAY_PAID, 'completed', 1);
        [$cancelled] = $this->makeOrder($user, $product, 'qr', Order::PAY_PAID, 'cancelled', 1);

        $this->actingAs($admin)->patch("/admin/orders/{$completed->id}/status", ['order_status' => 'pending'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame('completed', $completed->fresh()->order_status);

        $this->actingAs($admin)->patch("/admin/orders/{$cancelled->id}/status", ['order_status' => 'confirmed'])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame('cancelled', $cancelled->fresh()->order_status);

        // Transition hợp lệ vẫn đi được.
        [$pending] = $this->makeOrder($user, $product, 'qr', Order::PAY_PAID, 'pending', 1);
        $this->actingAs($admin)->patch("/admin/orders/{$pending->id}/status", ['order_status' => 'shipping'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('shipping', $pending->fresh()->order_status);
    }

    public function test_customer_confirm_after_paid_keeps_paid()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000]);
        // awaiting → admin approve → paid → customer confirm lại.
        [$order] = $this->makeOrder($user, $product, 'qr', Order::PAY_AWAITING, 'pending', 1);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/confirm-payment");
        $this->assertSame(Order::PAY_PAID, $order->fresh()->payment_status);

        // Best-effort sequential (không tuyên bố chứng minh concurrency tuyệt đối).
        $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm")
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame(Order::PAY_PAID, $order->fresh()->payment_status);
    }
}
