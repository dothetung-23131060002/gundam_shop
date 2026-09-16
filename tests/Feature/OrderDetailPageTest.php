<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\WarrantyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Order $order;
    protected OrderDetail $detail;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer']);
        $this->product = Product::factory()->create(['name' => 'RX-78-2 Gundam', 'price' => 100000]);
        $this->order = Order::create([
            'user_id' => $this->user->id,
            'customer_name' => $this->user->name,
            'customer_phone' => '0901234567',
            'shipping_address' => '123 Test St',
            'total_amount' => 500000,
            'payment_method' => 'qr',
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'delivered_at' => now()->subDays(1),
        ]);
        $this->detail = OrderDetail::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'price' => 100000,
            'quantity' => 5,
            'subtotal' => 500000,
        ]);
    }

    public function test_order_detail_page_loads(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertStatus(200);
        $response->assertViewIs('orders.show');
    }

    public function test_return_button_visible_when_eligible(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('Đổi / Trả hàng');
        $response->assertSee(route('returns.create', ['order_id' => $this->order->id]));
    }

    public function test_warranty_button_visible_when_eligible(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('Yêu cầu bảo hành');
        $response->assertSee(route('warranties.create', ['order_id' => $this->order->id]));
    }

    public function test_deadline_displayed_when_delivered_at_set(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $returnDeadline = $this->order->delivered_at->copy()->addDays(3);
        $warrantyDeadline = $this->order->delivered_at->copy()->addDays(7);

        $response->assertSee('Hạn đổi trả');
        $response->assertSee($returnDeadline->format('d/m/Y'));
        $response->assertSee('Hạn bảo hành');
        $response->assertSee($warrantyDeadline->format('d/m/Y'));
    }

    public function test_delivered_at_null_shows_warning(): void
    {
        $this->order->update(['delivered_at' => null]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('chưa có thời điểm giao hàng');
        $response->assertSee('Chưa có ngày giao hàng');
    }

    public function test_return_expired_shows_disabled(): void
    {
        $this->order->update(['delivered_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('Đã hết hạn đổi trả');
        $response->assertDontSee('Đổi / Trả hàng');
    }

    public function test_warranty_expired_shows_disabled(): void
    {
        $this->order->update(['delivered_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('Đã hết hạn bảo hành');
        $response->assertDontSee('Yêu cầu bảo hành');
    }

    public function test_active_return_shows_link_instead_of_button(): void
    {
        ReturnRequest::create([
            'order_id' => $this->order->id,
            'order_detail_id' => $this->detail->id,
            'requested_by' => $this->user->id,
            'status' => 'requested',
            'quantity' => 2,
            'reason' => 'Product defect',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('Xem yêu cầu trả hàng');
        $response->assertDontSee('Đổi / Trả hàng');
    }

    public function test_active_warranty_shows_link_instead_of_button(): void
    {
        WarrantyRequest::create([
            'order_id' => $this->order->id,
            'order_detail_id' => $this->detail->id,
            'user_id' => $this->user->id,
            'status' => 'requested',
            'quantity' => 2,
            'reason' => 'Runner broken',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('Xem yêu cầu bảo hành');
        $response->assertDontSee('Yêu cầu bảo hành');
    }

    public function test_remaining_qty_not_shown_when_no_activity(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertDontSee('Còn có thể yêu cầu');
    }

    public function test_remaining_qty_shows_when_pending_requests_exist(): void
    {
        ReturnRequest::create([
            'order_id' => $this->order->id,
            'order_detail_id' => $this->detail->id,
            'requested_by' => $this->user->id,
            'status' => 'requested',
            'quantity' => 2,
            'reason' => 'Product defect',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertSee('Còn có thể yêu cầu: 3');
        $response->assertSee('Đang xử lý: 2');
    }

    public function test_not_completed_order_hides_buttons(): void
    {
        $this->order->update(['order_status' => 'shipping']);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertDontSee('Đổi / Trả hàng');
        $response->assertDontSee('Yêu cầu bảo hành');
    }

    public function test_other_user_cannot_see_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($otherUser)
            ->get(route('orders.show', $this->order));

        $response->assertStatus(403);
    }

    public function test_admin_can_see_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('orders.show', $this->order));

        $response->assertStatus(200);
    }

    public function test_n_plus_one_query_check(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $detail = OrderDetail::create([
                'order_id' => $this->order->id,
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'price' => 100000,
                'quantity' => 3,
                'subtotal' => 300000,
            ]);
            ReturnRequest::create([
                'order_id' => $this->order->id,
                'order_detail_id' => $detail->id,
                'requested_by' => $this->user->id,
                'status' => 'requested',
                'quantity' => 1,
                'reason' => 'Defect',
            ]);
            WarrantyRequest::create([
                'order_id' => $this->order->id,
                'order_detail_id' => $detail->id,
                'user_id' => $this->user->id,
                'status' => 'requested',
                'quantity' => 1,
                'reason' => 'Broken',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $this->order));

        $response->assertStatus(200);
        $response->assertSee('Còn có thể yêu cầu');
        $response->assertSee('Đang xử lý');
    }
}
