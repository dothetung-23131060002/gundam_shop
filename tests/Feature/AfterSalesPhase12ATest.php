<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\Reservation;
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

class AfterSalesPhase12ATest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeOrder(int $qty = 5, array $overrides = []): array
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['price' => 100000, 'quantity' => 100]);
        $order = Order::create(array_merge([
            'user_id' => $user->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Addr',
            'total_amount' => $product->price * $qty,
            'payment_method' => 'qr',
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'delivered_at' => now()->subDay(),
        ], $overrides));
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

    // F2: logged-in users hitting guest routes land on existing homepage.
    public function test_home_constant_points_to_existing_route()
    {
        $this->assertEquals('/', \App\Providers\RouteServiceProvider::HOME);

        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user)->get('/login')->assertRedirect('/');
        $this->actingAs($user)->get('/register')->assertRedirect('/');
    }

    // F3: resolution allow-list excludes rejected.
    public function test_warranty_resolutions_exclude_rejected()
    {
        $this->assertNotContains('rejected', WarrantyRequest::RESOLUTIONS);
        $this->assertContains('repaired', WarrantyRequest::RESOLUTIONS);
    }

    // F4: cart stock guards.
    public function test_cart_add_beyond_stock_rejected()
    {
        $product = Product::factory()->create(['price' => 50000, 'quantity' => 3]);

        $r = $this->post(route('cart.add', $product), ['quantity' => 4]);
        $r->assertSessionHas('error');
        $this->assertEmpty(session('cart', []));
    }

    public function test_cart_update_beyond_stock_rejected()
    {
        $product = Product::factory()->create(['price' => 50000, 'quantity' => 3]);
        $this->post(route('cart.add', $product), ['quantity' => 2])->assertSessionHas('success');

        $r = $this->patch(route('cart.update', $product), ['quantity' => 4]);
        $r->assertSessionHas('error');
        $this->assertEquals(2, session('cart')[$product->id]['quantity']);
    }

    public function test_cart_add_within_stock_success()
    {
        $product = Product::factory()->create(['price' => 50000, 'quantity' => 3]);
        $this->post(route('cart.add', $product), ['quantity' => 3])->assertSessionHas('success');
        $this->assertEquals(3, session('cart')[$product->id]['quantity']);
    }

    // F5: forfeiture labeled, excluded from monetary total.
    public function test_forfeiture_labeled_and_excluded_from_total()
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id, 'threshold' => 5,
            'deposit_amount' => 50000, 'deadline' => now()->subDay(), 'status' => 'failed',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id, 'batch_id' => $batch->id,
            'quantity' => 2, 'deposit_paid' => 0, 'status' => 'refunded',
        ]);
        RefundTransaction::create([
            'reservation_id' => $reservation->id, 'amount' => 100000,
            'reason' => RefundService::REASON_BATCH_FAILED,
            'status' => RefundTransaction::STATUS_COMPLETED,
            'refunded_at' => now(), 'type' => RefundTransaction::TYPE_DEPOSIT,
        ]);
        RefundTransaction::create([
            'reservation_id' => $reservation->id, 'amount' => 200000,
            'reason' => RefundTransaction::REASON_FORFEITED,
            'status' => RefundTransaction::STATUS_COMPLETED,
            'refunded_at' => now(), 'type' => RefundTransaction::TYPE_DEPOSIT,
        ]);

        $r = $this->actingAs($admin)->get(route('admin.refunds.index'));
        $r->assertOk();
        $r->assertSee('Tịch thu cọc');
        $r->assertSee('+100.000đ');
        $r->assertDontSee('+300.000đ');
    }

    // F8: return create GET guards + preselect.
    public function test_return_create_get_guards()
    {
        // Shipping order blocked.
        [$u1, $p1, $o1, $d1] = $this->makeOrder(5, ['order_status' => 'shipping']);
        $this->actingAs($u1)->get(route('returns.create', ['order_id' => $o1->id]))
            ->assertRedirect(route('returns.mine'));

        // Expired (delivered 10 days ago) blocked.
        [$u2, $p2, $o2, $d2] = $this->makeOrder(5, ['delivered_at' => now()->subDays(10)]);
        $this->actingAs($u2)->get(route('returns.create', ['order_id' => $o2->id]))
            ->assertRedirect(route('returns.mine'));

        // Null delivered_at blocked.
        [$u3, $p3, $o3, $d3] = $this->makeOrder(5, ['delivered_at' => null]);
        $this->actingAs($u3)->get(route('returns.create', ['order_id' => $o3->id]))
            ->assertRedirect(route('returns.mine'));

        // Unpaid blocked.
        [$u4, $p4, $o4, $d4] = $this->makeOrder(5, ['payment_status' => 'unpaid']);
        $this->actingAs($u4)->get(route('returns.create', ['order_id' => $o4->id]))
            ->assertRedirect(route('returns.mine'));
    }

    public function test_return_create_preselects_detail()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $r = $this->actingAs($user)->get(route('returns.create', [
            'order_id' => $order->id, 'order_detail_id' => $detail->id,
        ]));
        $r->assertOk();
        $r->assertSee('value="'.$detail->id.'" data-max', false);
    }

    // F9: admin warranty invalid resolution surfaces validation errors.
    public function test_admin_warranty_invalid_resolution_errors()
    {
        Storage::fake('public');
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $w = app(WarrantyService::class)->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id,
            [UploadedFile::fake()->image('e.jpg', 100, 100)->store('warranty-evidence/test', 'public')]
        );
        app(WarrantyService::class)->approve($w->id, $admin->id);
        app(WarrantyService::class)->processing($w->id, $admin->id);

        $this->actingAs($admin)->post(route('admin.warranties.complete', $w), [
            'resolution' => 'bogus',
        ])->assertSessionHasErrors('resolution');
        $this->assertEquals('processing', $w->fresh()->status);
    }

    // F10: admins notified on customer create with admin deep-link.
    public function test_admin_notified_on_return_and_warranty_create()
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        [$user, $product, $order, $detail] = $this->makeOrder(10);

        $ret = app(ReturnService::class)->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $adminNotif = $admin->fresh()->notifications->firstWhere('data.return_id', $ret->id);
        $this->assertNotNull($adminNotif);
        $this->assertEquals('/admin/returns/'.$ret->id, $adminNotif->data['admin_link']);

        $w = app(WarrantyService::class)->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id,
            [UploadedFile::fake()->image('e.jpg', 100, 100)->store('warranty-evidence/test', 'public')]
        );
        $adminNotif2 = $admin->fresh()->notifications->firstWhere('data.warranty_id', $w->id);
        $this->assertNotNull($adminNotif2);
        $this->assertEquals('/admin/warranties/'.$w->id, $adminNotif2->data['admin_link']);

        // Admin notifications page deep-links to admin show.
        $page = $this->actingAs($admin)->get(route('notifications.index'));
        $page->assertOk();
        $page->assertSee('/admin/returns/'.$ret->id);
        $page->assertSee('/admin/warranties/'.$w->id);
    }

    // F11: uppercase extension accepted; deadlines rendered.
    public function test_warranty_uppercase_extension_accepted()
    {
        Storage::fake('public');
        [$user, $product, $order, $detail] = $this->makeOrder(10);

        $r = $this->actingAs($user)->post(route('warranties.store'), [
            'order_id' => $order->id,
            'order_detail_id' => $detail->id,
            'quantity' => 1,
            'reason' => 'Lỗi',
            'evidence' => [UploadedFile::fake()->image('PHOTO.JPG', 100, 100)],
        ]);
        $r->assertRedirect();
        $this->assertEquals(1, WarrantyRequest::count());
    }

    public function test_deadlines_rendered_on_detail_pages()
    {
        Storage::fake('public');
        [$user, $product, $order, $detail] = $this->makeOrder(10);
        $admin = $this->makeAdmin();

        $ret = app(ReturnService::class)->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $this->actingAs($user)->get(route('returns.show', $ret))
            ->assertOk()->assertSee('Hạn đổi trả');

        $w = app(WarrantyService::class)->request(
            $order->id, $detail->id, 1, 'Lỗi', null, $user->id,
            [UploadedFile::fake()->image('e.jpg', 100, 100)->store('warranty-evidence/test', 'public')]
        );
        $this->actingAs($user)->get(route('warranties.show', $w))
            ->assertOk()->assertSee('Hạn bảo hành');
    }
}
