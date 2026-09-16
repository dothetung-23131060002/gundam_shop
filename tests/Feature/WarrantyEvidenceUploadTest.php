<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarrantyEvidenceUploadTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): array
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['price' => 100000, 'quantity' => 100]);
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Addr',
            'total_amount' => $product->price * 10,
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
            'quantity' => 10,
            'subtotal' => $product->price * 10,
        ]);

        return [$user, $order, $detail];
    }

    public function test_upload_1_image_creates_warranty_and_shows_evidence()
    {
        Storage::fake('public');
        [$user, $order, $detail] = $this->makeOrder();

        $response = $this->actingAs($user)->post(route('warranties.store'), [
            'order_id' => $order->id,
            'order_detail_id' => $detail->id,
            'quantity' => 1,
            'reason' => 'Lỗi runner',
            'description' => 'Mô tả',
            'evidence' => [UploadedFile::fake()->image('evidence.jpg', 100, 100)],
        ]);

        $warranty = WarrantyRequest::first();
        $this->assertNotNull($warranty);
        $response->assertRedirect(route('warranties.show', $warranty));
        $this->assertCount(1, $warranty->evidence);
        foreach ($warranty->evidence as $path) {
            Storage::disk('public')->assertExists($path);
            $this->assertStringStartsWith("warranty-evidence/{$warranty->id}/", $path);
        }

        // Browser-equivalent: detail page renders stored evidence.
        $show = $this->actingAs($user)->get(route('warranties.show', $warranty));
        $show->assertOk();
        foreach ($warranty->evidence as $path) {
            $show->assertSee($path);
        }
    }

    public function test_upload_0_images_rejected()
    {
        Storage::fake('public');
        [$user, $order, $detail] = $this->makeOrder();

        $this->actingAs($user)->post(route('warranties.store'), [
            'order_id' => $order->id,
            'order_detail_id' => $detail->id,
            'quantity' => 1,
            'reason' => 'Lỗi',
        ])->assertSessionHasErrors('evidence');

        $this->assertEquals(0, WarrantyRequest::count());
    }

    public function test_upload_over_5_images_rejected_without_orphan()
    {
        Storage::fake('public');
        [$user, $order, $detail] = $this->makeOrder();
        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->image("e{$i}.jpg", 100, 100);
        }

        $this->actingAs($user)->post(route('warranties.store'), [
            'order_id' => $order->id,
            'order_detail_id' => $detail->id,
            'quantity' => 1,
            'reason' => 'Lỗi',
            'evidence' => $files,
        ])->assertSessionHasErrors('evidence');

        $this->assertEquals(0, WarrantyRequest::count());
    }
}
