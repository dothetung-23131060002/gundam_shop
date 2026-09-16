<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\PaymentStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentConfirmationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('vietqr.account_name', 'TEST USER');
        config()->set('vietqr.template', 'compact2');
        config()->set('vietqr.default', 'mb');
        config()->set('vietqr.methods', [
            'mb' => ['label' => 'MB Bank', 'type' => 'bank', 'bank_id' => 'MB', 'account_no' => '111111'],
            'tcb' => ['label' => 'Techcombank', 'type' => 'bank', 'bank_id' => 'TCB', 'account_no' => '222222'],
            'vpb' => ['label' => 'VPBank', 'type' => 'bank', 'bank_id' => 'VPB', 'account_no' => '333333'],
            'momo' => ['label' => 'MoMo', 'type' => 'wallet', 'account_no' => '0912345678'],
        ]);
    }

    private function makeQrOrder(User $user, string $status = Order::PAY_PENDING): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => 500000,
            'payment_method' => 'qr',
            'payment_status' => $status,
            'order_status' => 'pending',
        ]);
    }

    private function makePayableReservation(User $user): Reservation
    {
        $product = Product::factory()->create(['price' => 500000, 'quantity' => 100]);
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 100000,
            'deadline' => now()->addDays(7),
            'status' => 'success',
        ]);

        return Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => 200000,
            'status' => 'reserved',
        ]);
    }

    public function test_customer_submit_moves_to_awaiting_not_paid()
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user);

        $response = $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm");

        $response->assertRedirect();
        $this->assertSame(Order::PAY_AWAITING, $order->refresh()->payment_status);
        $this->assertDatabaseHas('notifications', [
            'type' => PaymentStatusUpdated::class,
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_customer_cannot_self_mark_paid()
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user);

        // Lần 1 → awaiting, lần 2 vẫn awaiting (idempotent), không bao giờ paid.
        $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm");
        $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm");

        $this->assertSame(Order::PAY_AWAITING, $order->refresh()->payment_status);
        $this->assertSame(0, Order::where('id', $order->id)->where('payment_status', 'paid')->count());
    }

    public function test_stranger_cannot_confirm_other_order()
    {
        $owner = User::factory()->create(['role' => 'user']);
        $stranger = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($owner);

        $this->actingAs($stranger)->post("/payment/qr/{$order->id}/confirm")->assertForbidden();
        $this->assertSame(Order::PAY_PENDING, $order->refresh()->payment_status);
    }

    public function test_admin_confirm_approves_to_paid()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user, Order::PAY_AWAITING);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/confirm-payment");

        $response->assertRedirect();
        $this->assertSame(Order::PAY_PAID, $order->refresh()->payment_status);
    }

    public function test_admin_confirm_rejected_when_not_awaiting()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user, Order::PAY_PENDING);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/confirm-payment");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(Order::PAY_PENDING, $order->refresh()->payment_status);
    }

    public function test_admin_double_approve_is_idempotent()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user, Order::PAY_AWAITING);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/confirm-payment");
        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/confirm-payment");

        $response->assertSessionHas('error');
        $this->assertSame(Order::PAY_PAID, $order->refresh()->payment_status);
    }

    public function test_admin_reject_with_reason()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user, Order::PAY_AWAITING);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/reject-payment", [
            'reason' => 'Không thấy giao dịch',
        ]);

        $response->assertRedirect();
        $this->assertSame(Order::PAY_REJECTED, $order->refresh()->payment_status);

        $notification = $user->notifications()->latest()->first();
        $this->assertSame('Không thấy giao dịch', $notification->data['reason'] ?? null);
    }

    public function test_rejected_customer_can_resubmit()
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user, Order::PAY_REJECTED);

        $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm");

        $this->assertSame(Order::PAY_AWAITING, $order->refresh()->payment_status);
    }

    public function test_balance_submit_waits_for_admin_and_approves_with_convert()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $reservation = $this->makePayableReservation($user);

        $this->actingAs($user)->post("/reservations/{$reservation->id}/process-balance", [
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
        ])->assertRedirect();

        $order = Order::where('reservation_id', $reservation->id)->first();
        $this->assertSame(Order::PAY_AWAITING, $order->payment_status);
        $this->assertSame('reserved', $reservation->refresh()->status);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/confirm-payment");

        $this->assertSame(Order::PAY_PAID, $order->refresh()->payment_status);
        $this->assertSame('converted', $reservation->refresh()->status);
    }

    public function test_checkout_qr_creates_pending_and_cod_stays_unpaid()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 100000, 'quantity' => 10]);

        $qrResponse = $this->actingAs($user)
            ->withSession(['cart' => [$product->id => ['quantity' => 1]]])
            ->post('/checkout', [
                'customer_name' => 'Test User',
                'customer_phone' => '0123456789',
                'shipping_address' => '123 Test Street',
                'payment_method' => 'qr',
            ]);

        $qrResponse->assertRedirect();
        $qrOrder = Order::where('payment_method', 'qr')->latest()->first();
        $this->assertSame(Order::PAY_PENDING, $qrOrder->payment_status);

        $codResponse = $this->actingAs($user)
            ->withSession(['cart' => [$product->id => ['quantity' => 1]]])
            ->post('/checkout', [
                'customer_name' => 'Test User',
                'customer_phone' => '0123456789',
                'shipping_address' => '123 Test Street',
                'payment_method' => 'cod',
            ]);

        $codResponse->assertRedirect();
        $this->assertSame('unpaid', Order::where('payment_method', 'cod')->latest()->first()->payment_status);
    }

    public function test_cod_confirm_is_rejected()
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => 100000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
        ]);

        $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm")->assertRedirect();

        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_paid_order_can_move_to_confirmed()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user, Order::PAY_PAID);

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'order_status' => 'confirmed',
        ])->assertRedirect();

        $this->assertSame('confirmed', $order->refresh()->order_status);
    }

    public function test_qr_page_renders_correctly_per_state()
    {
        $user = User::factory()->create(['role' => 'user']);

        $pending = $this->makeQrOrder($user, Order::PAY_PENDING);
        $this->actingAs($user)->get("/payment/qr/{$pending->id}")
            ->assertOk()
            ->assertSee('TÔI ĐÃ THANH TOÁN', false)
            ->assertSee('Chờ thanh toán');

        $awaiting = $this->makeQrOrder($user, Order::PAY_AWAITING);
        $this->actingAs($user)->get("/payment/qr/{$awaiting->id}")
            ->assertOk()
            ->assertSee('Shop đang kiểm tra giao dịch')
            ->assertDontSee('TÔI ĐÃ THANH TOÁN', false);

        $rejected = $this->makeQrOrder($user, Order::PAY_REJECTED);
        $this->actingAs($user)->get("/payment/qr/{$rejected->id}")
            ->assertOk()
            ->assertSee('Thanh toán chưa được xác nhận')
            ->assertSee('TÔI ĐÃ THANH TOÁN', false);

        $paid = $this->makeQrOrder($user, Order::PAY_PAID);
        $this->actingAs($user)->get("/payment/qr/{$paid->id}")
            ->assertOk()
            ->assertSee('Shop đã xác nhận nhận được tiền')
            ->assertDontSee('TÔI ĐÃ THANH TOÁN', false);
    }

    public function test_admin_show_reveals_actions_only_when_awaiting()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $awaiting = $this->makeQrOrder($user, Order::PAY_AWAITING);
        $pending = $this->makeQrOrder($user, Order::PAY_PENDING);

        $this->actingAs($admin)->get("/admin/orders/{$awaiting->id}")
            ->assertOk()
            ->assertSee('XÁC NHẬN ĐÃ NHẬN TIỀN', false);

        $this->actingAs($admin)->get("/admin/orders/{$pending->id}")
            ->assertOk()
            ->assertDontSee('XÁC NHẬN ĐÃ NHẬN TIỀN', false);
    }

    public function test_admin_index_filters_by_payment_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeQrOrder($user, Order::PAY_AWAITING);
        $this->makeQrOrder($user, Order::PAY_PENDING);

        $this->actingAs($admin)->get('/admin/orders?payment_status=awaiting_confirmation')
            ->assertOk()
            ->assertSee('Cần xác nhận');
    }

    public function test_customer_cannot_hit_admin_endpoints()
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeQrOrder($user, Order::PAY_AWAITING);

        $this->actingAs($user)->post("/admin/orders/{$order->id}/confirm-payment")->assertForbidden();
        $this->actingAs($user)->post("/admin/orders/{$order->id}/reject-payment")->assertForbidden();
        $this->assertSame(Order::PAY_AWAITING, $order->refresh()->payment_status);
    }
}
