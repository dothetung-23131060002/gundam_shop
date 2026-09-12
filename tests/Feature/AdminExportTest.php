<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_export_has_bom_header_rows_and_summary()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create(['price' => 300000]);

        foreach (['paid', 'unpaid'] as $status) {
            $order = Order::create([
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => '0123456789',
                'shipping_address' => '123 Street',
                'total_amount' => 300000,
                'payment_method' => 'cod',
                'payment_status' => $status,
                'order_status' => 'pending',
            ]);
            OrderDetail::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => 300000,
                'quantity' => 1,
                'subtotal' => 300000,
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/orders/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertEquals("\xEF\xBB\xBF", substr($content, 0, 3), 'Thiếu BOM UTF-8');
        $this->assertStringContainsString('Mã đơn', $content);
        $this->assertStringContainsString('Tổng số đơn', $content);
        $this->assertStringContainsString('Doanh thu đã thu (paid)', $content);
    }

    public function test_batches_export_respects_filters()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $openProduct = Product::factory()->create(['name' => 'Open Batch Product']);
        $doneProduct = Product::factory()->create(['name' => 'Done Batch Product']);

        Batch::create([
            'product_id' => $openProduct->id, 'threshold' => 5,
            'deposit_amount' => 50000, 'deadline' => now()->addDays(3), 'status' => 'open',
        ]);
        Batch::create([
            'product_id' => $doneProduct->id, 'threshold' => 5,
            'deposit_amount' => 50000, 'deadline' => now()->subDay(), 'status' => 'success',
        ]);

        $response = $this->actingAs($admin)->get('/admin/batches/export?status=success');

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Done Batch Product', $content);
        $this->assertStringNotContainsString('Open Batch Product', $content);
        $this->assertStringContainsString('Tổng cọc đang giữ', $content);
    }

    public function test_guest_and_customer_cannot_export()
    {
        $customer = User::factory()->create(['role' => 'user']);

        $this->get('/admin/orders/export')->assertRedirect('/login');
        $this->get('/admin/batches/export')->assertRedirect('/login');

        $this->actingAs($customer)->get('/admin/orders/export')->assertForbidden();
        $this->actingAs($customer)->get('/admin/batches/export')->assertForbidden();
    }
}
