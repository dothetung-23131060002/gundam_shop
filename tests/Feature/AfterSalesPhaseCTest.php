<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\RefundService;
use App\Services\ReturnService;
use App\Services\WarrantyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AfterSalesPhaseCTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeOrder(int $qty = 10): array
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

    private function returnEvents(User $user): array
    {
        return $user->notifications->pluck('data.event')->all();
    }

    public function test_return_notifications_for_all_core_events()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);

        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $this->assertEquals(['requested'], $this->returnEvents($user->fresh()));

        $svc->approve($ret->id, $admin->id);
        $svc->receive($ret->id, $admin->id);
        $ret->update(['refund_reason' => RefundService::REASON_MANUFACTURER_DEFECT]);
        $svc->inspect($ret->id, 'defective', $admin->id);
        $svc->complete($ret->id, 0, $admin->id);

        $events = $this->returnEvents($user->fresh());
        foreach (['requested', 'approved', 'received', 'completed'] as $expected) {
            $this->assertContains($expected, $events);
        }

        $completed = $user->notifications->firstWhere('data.event', 'completed');
        $this->assertStringContainsString("#{$ret->id}", $completed->data['message']);
        $this->assertStringContainsString('100.000đ', $completed->data['message']);
        $this->assertEquals('/returns/'.$ret->id, $completed->data['link']);
        $this->assertEquals('return', $completed->data['type']);

        // Database channel only — no mail sent.
        $this->assertEquals(['database'], (new \App\Notifications\ReturnRequestStatusUpdated($ret->id, 'approved'))->via($user));
    }

    public function test_return_rejected_notification()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);

        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $svc->reject($ret->id, $admin->id);

        $events = $this->returnEvents($user->fresh());
        $this->assertContains('requested', $events);
        $this->assertContains('rejected', $events);
    }

    public function test_warranty_notifications_for_all_core_events()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(WarrantyService::class);

        $w = $svc->request($order->id, $detail->id, 1, 'Lỗi', null, $user->id, $this->makeEvidence(1));
        $svc->approve($w->id, $admin->id);
        $svc->processing($w->id, $admin->id);
        $svc->complete($w->id, 'repaired', $admin->id);

        $events = $user->fresh()->notifications->pluck('data.event')->all();
        foreach (['requested', 'approved', 'processing', 'completed'] as $expected) {
            $this->assertContains($expected, $events);
        }

        $completed = $user->notifications->firstWhere('data.event', 'completed');
        $this->assertStringContainsString("#{$w->id}", $completed->data['message']);
        $this->assertStringContainsString('Sửa chữa', $completed->data['message']);
        $this->assertEquals('/warranties/'.$w->id, $completed->data['link']);
        $this->assertEquals('warranty', $completed->data['type']);

        $this->assertEquals(['database'], (new \App\Notifications\WarrantyRequestStatusUpdated($w->id, 'approved'))->via($user));
    }

    public function test_warranty_rejected_notification()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(WarrantyService::class);

        $w = $svc->request($order->id, $detail->id, 1, 'Lỗi', null, $user->id, $this->makeEvidence(1));
        $svc->reject($w->id, $admin->id);

        $events = $user->fresh()->notifications->pluck('data.event')->all();
        $this->assertContains('requested', $events);
        $this->assertContains('rejected', $events);
    }

    public function test_customer_sees_unread_and_marks_read()
    {
        [$user, $product, $order, $detail] = $this->makeOrder();
        $admin = $this->makeAdmin();
        $svc = app(ReturnService::class);

        $ret = $svc->request($order->id, $detail->id, 1, 'Lỗi', $user->id, 'sealed');
        $svc->approve($ret->id, $admin->id);

        // Unread badge infrastructure picks them up.
        $this->assertEquals(2, $user->unreadNotifications()->count());

        // Notifications page lists message + deep link.
        $page = $this->actingAs($user)->get(route('notifications.index'));
        $page->assertOk();
        $page->assertSee("Yêu cầu trả hàng #{$ret->id} đã được duyệt");
        $page->assertSee('/returns/'.$ret->id);

        // Mark all as read clears unread state.
        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertEquals(0, $user->fresh()->unreadNotifications()->count());
    }
}
