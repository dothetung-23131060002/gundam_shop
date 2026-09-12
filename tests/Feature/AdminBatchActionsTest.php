<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminBatchActionsTest extends TestCase
{
    use RefreshDatabase;

    private function makeBatch(int $threshold = 2): Batch
    {
        $product = Product::factory()->create();

        return Batch::create([
            'product_id' => $product->id,
            'threshold' => $threshold,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);
    }

    private function fillSlots(Batch $batch, int $slots): void
    {
        for ($i = 0; $i < $slots; $i++) {
            $user = User::factory()->create(['role' => 'user']);
            Reservation::create([
                'user_id' => $user->id,
                'batch_id' => $batch->id,
                'quantity' => 1,
                'deposit_paid' => 50000,
                'status' => 'reserved',
            ]);
        }
    }

    public function test_admin_can_close_early_when_full()
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch(2);
        $this->fillSlots($batch, 2);

        $response = $this->actingAs($admin)
            ->patch("/admin/batches/{$batch->id}/close-early");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('success', $batch->fresh()->status);
    }

    public function test_close_early_blocked_when_not_full()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch(5);
        $this->fillSlots($batch, 2);

        $response = $this->actingAs($admin)
            ->patch("/admin/batches/{$batch->id}/close-early");

        $response->assertSessionHas('error');
        $this->assertEquals('open', $batch->fresh()->status);
    }

    public function test_force_success_works_without_threshold_for_demo()
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch(5);
        $this->fillSlots($batch, 1);

        $response = $this->actingAs($admin)
            ->patch("/admin/batches/{$batch->id}/force-success");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('success', $batch->fresh()->status);
    }

    public function test_guest_cannot_access_admin_batch_routes()
    {
        $batch = $this->makeBatch();

        $this->get('/admin/batches')->assertRedirect('/login');
        $this->patch("/admin/batches/{$batch->id}/close-early")->assertRedirect('/login');
    }

    public function test_customer_cannot_access_admin_batch_routes()
    {
        $customer = User::factory()->create(['role' => 'user']);
        $batch = $this->makeBatch();

        $this->actingAs($customer)->get('/admin/batches')->assertForbidden();
        $this->actingAs($customer)->patch("/admin/batches/{$batch->id}/close-early")->assertForbidden();
        $this->actingAs($customer)->patch("/admin/batches/{$batch->id}/force-success")->assertForbidden();
    }

    public function test_admin_can_delete_pristine_open_batch()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();

        $response = $this->actingAs($admin)->delete("/admin/batches/{$batch->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('batches', ['id' => $batch->id]);
    }

    public function test_delete_blocked_when_only_refunded_history_remains()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $user = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
            'deposit_paid' => 0,
            'status' => 'refunded',
        ]);

        $response = $this->actingAs($admin)->delete("/admin/batches/{$batch->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }

    public function test_delete_blocked_when_batch_not_open()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $batch->update(['status' => 'success']);

        $response = $this->actingAs($admin)->delete("/admin/batches/{$batch->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }
}
