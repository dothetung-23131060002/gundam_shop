<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeCodOrder(array $overrides = []): Order
    {
        $user = User::factory()->create(['role' => 'user']);

        return Order::create(array_merge([
            'user_id' => $user->id,
            'customer_name' => 'COD Test',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 COD Street',
            'total_amount' => 250000,
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
        ], $overrides));
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // --- Checkout COD creates order correctly ---

    public function test_checkout_cod_creates_unpaid_order()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = \App\Models\Product::factory()->create(['price' => 250000, 'quantity' => 10]);

        $response = $this->actingAs($user)
            ->withSession(['cart' => [$product->id => ['quantity' => 1]]])
            ->post('/checkout', [
                'customer_name' => 'COD Test',
                'customer_phone' => '0123456789',
                'shipping_address' => '123 COD Street',
                'payment_method' => 'cod',
            ]);

        $response->assertRedirect();
        $order = Order::where('payment_method', 'cod')->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('pending', $order->order_status);
    }

    public function test_cod_does_not_create_qr_payment_flow()
    {
        $order = $this->makeCodOrder();

        $this->assertNotEquals('pending_payment', $order->payment_status);
        $this->assertNotEquals('awaiting_confirmation', $order->payment_status);
    }

    public function test_customer_cannot_submit_cod_to_awaiting()
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeCodOrder(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm");

        $response->assertRedirect();
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    // --- Admin flow: pending → confirmed → shipping ---

    public function test_admin_can_confirm_cod_pending_to_confirmed()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'pending']);

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'order_status' => 'confirmed',
        ])->assertRedirect();

        $this->assertSame('confirmed', $order->refresh()->order_status);
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_admin_can_move_cod_confirmed_to_shipping()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'confirmed']);

        $this->actingAs($admin)->patch("/admin/orders/{$order->id}/status", [
            'order_status' => 'shipping',
        ])->assertRedirect();

        $this->assertSame('shipping', $order->refresh()->order_status);
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_cod_shipping_still_unpaid()
    {
        $order = $this->makeCodOrder(['order_status' => 'shipping']);

        $this->assertSame('shipping', $order->order_status);
        $this->assertSame('unpaid', $order->payment_status);
    }

    // --- collectCod: success cases ---

    public function test_collect_cod_from_shipping_succeeds()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'shipping']);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame('completed', $order->refresh()->order_status);
    }

    public function test_collect_cod_from_completed_succeeds()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'completed']);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame('completed', $order->refresh()->order_status);
    }

    // --- collectCod: rejection cases ---

    public function test_collect_cod_rejected_for_non_cod_order()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder([
            'payment_method' => 'qr',
            'payment_status' => 'awaiting_confirmation',
            'order_status' => 'shipping',
        ]);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $response->assertSessionHas('error');
        $this->assertSame('awaiting_confirmation', $order->refresh()->payment_status);
        $this->assertSame('shipping', $order->refresh()->order_status);
    }

    public function test_collect_cod_rejected_when_pending()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'pending']);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $response->assertSessionHas('error');
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_collect_cod_rejected_when_confirmed()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'confirmed']);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $response->assertSessionHas('error');
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_collect_cod_rejected_when_cancelled()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'cancelled']);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $response->assertSessionHas('error');
        $this->assertSame('unpaid', $order->refresh()->payment_status);
        $this->assertSame('cancelled', $order->refresh()->order_status);
    }

    public function test_collect_cod_rejected_when_already_paid()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder([
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $response->assertSessionHas('error');
    }

    public function test_collect_cod_double_submit_is_blocked()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder(['order_status' => 'shipping']);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");
        $response = $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $response->assertSessionHas('error');
        $this->assertSame('paid', $order->refresh()->payment_status);
        $this->assertSame('completed', $order->refresh()->order_status);
    }

    // --- Authorization ---

    public function test_guest_cannot_call_collect_cod()
    {
        $order = $this->makeCodOrder(['order_status' => 'shipping']);

        $this->post("/admin/orders/{$order->id}/collect-cod")->assertRedirect('/login');
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_customer_cannot_call_collect_cod()
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeCodOrder(['order_status' => 'shipping']);

        $this->actingAs($user)->post("/admin/orders/{$order->id}/collect-cod")->assertForbidden();
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    // --- Revenue ---

    public function test_cod_not_collected_not_counted_in_revenue()
    {
        $order = $this->makeCodOrder([
            'order_status' => 'shipping',
            'payment_status' => 'unpaid',
            'total_amount' => 300000,
        ]);

        $revenue = Order::where('payment_status', 'paid')
            ->where('order_status', '!=', 'cancelled')
            ->sum('total_amount');

        $this->assertEquals(0, $revenue);
    }

    public function test_cod_collected_counted_in_revenue()
    {
        $admin = $this->makeAdmin();
        $order = $this->makeCodOrder([
            'order_status' => 'shipping',
            'total_amount' => 300000,
        ]);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/collect-cod");

        $revenue = Order::where('payment_status', 'paid')
            ->where('order_status', '!=', 'cancelled')
            ->sum('total_amount');

        $this->assertEquals(300000, $revenue);
    }

    public function test_cancelled_paid_order_stays_in_gross()
    {
        $order = $this->makeCodOrder([
            'payment_status' => 'paid',
            'order_status' => 'cancelled',
            'total_amount' => 100000,
        ]);

        // Accounting contract: Gross giữ cả paid+cancelled (phần hoàn nằm ở refund layer).
        $revenue = Order::paid()->sum('total_amount');

        $this->assertEquals(100000, $revenue);
    }

    // --- Admin show button visibility ---

    public function test_admin_show_shows_collect_button_only_when_eligible()
    {
        $admin = $this->makeAdmin();

        $shippingCod = $this->makeCodOrder(['order_status' => 'shipping']);
        $pendingCod = $this->makeCodOrder(['order_status' => 'pending']);
        $confirmedCod = $this->makeCodOrder(['order_status' => 'confirmed']);
        $paidCod = $this->makeCodOrder(['payment_status' => 'paid', 'order_status' => 'completed']);
        $legacyCod = $this->makeCodOrder(['order_status' => 'completed']);

        $this->actingAs($admin)->get("/admin/orders/{$shippingCod->id}")
            ->assertOk()
            ->assertSee('XÁC NHẬN ĐÃ THU TIỀN COD', false);

        $this->actingAs($admin)->get("/admin/orders/{$pendingCod->id}")
            ->assertOk()
            ->assertDontSee('XÁC NHẬN ĐÃ THU TIỀN COD', false);

        $this->actingAs($admin)->get("/admin/orders/{$confirmedCod->id}")
            ->assertOk()
            ->assertDontSee('XÁC NHẬN ĐÃ THU TIỀN COD', false);

        $this->actingAs($admin)->get("/admin/orders/{$paidCod->id}")
            ->assertOk()
            ->assertDontSee('XÁC NHẬN ĐÃ THU TIỀN COD', false);

        // Legacy completed+unpaid: button NOT shown, but collectCod endpoint still works
        $this->actingAs($admin)->get("/admin/orders/{$legacyCod->id}")
            ->assertOk()
            ->assertDontSee('XÁC NHẬN ĐÃ THU TIỀN COD', false);
    }
}
