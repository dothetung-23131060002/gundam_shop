<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BatchProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_endpoint_returns_live_counts()
    {
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 3,
            'deposit_paid' => 150000,
            'status' => 'reserved',
        ]);

        $response = $this->getJson("/batches/{$batch->id}/progress");

        $response->assertOk()
            ->assertJson([
                'batch_id' => $batch->id,
                'status' => 'open',
                'reserved' => 3,
                'threshold' => 10,
                'is_full' => false,
                'is_open' => true,
            ])
            ->assertJsonPath('percent', 30);
    }

    public function test_progress_reflects_closed_batch()
    {
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'success',
        ]);

        $response = $this->getJson("/batches/{$batch->id}/progress");

        $response->assertOk()->assertJson([
            'status' => 'success',
            'is_open' => false,
        ]);
    }

    public function test_batch_index_does_not_n_plus_one()
    {
        $product = Product::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $batch = Batch::create([
                'product_id' => $product->id,
                'threshold' => 10,
                'deposit_amount' => 50000,
                'deadline' => now()->addDays(3),
                'status' => 'open',
            ]);
            $user = User::factory()->create(['role' => 'user']);
            Reservation::create([
                'user_id' => $user->id,
                'batch_id' => $batch->id,
                'quantity' => $i + 1,
                'deposit_paid' => 50000 * ($i + 1),
                'status' => 'reserved',
            ]);
        }

        DB::enableQueryLog();
        $response = $this->get('/batches');
        $queryCount = count(DB::getQueryLog());

        $response->assertOk();
        // 1 trang: batches + withSum + product/category/brand + count + session...
        // Quan trọng: không tăng theo số dòng (5 batch mà >12 query = N+1).
        $this->assertLessThan(12, $queryCount, "Too many queries ({$queryCount}) — N+1?");
        $response->assertSee('5/10 slot');
    }

    public function test_withsum_value_matches_live_count()
    {
        $product = Product::factory()->create();
        $batch = Batch::create([
            'product_id' => $product->id,
            'threshold' => 10,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(3),
            'status' => 'open',
        ]);

        $user = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 4,
            'deposit_paid' => 200000,
            'status' => 'reserved',
        ]);
        // Reservation đã refund không được tính
        $other = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $other->id,
            'batch_id' => $batch->id,
            'quantity' => 9,
            'deposit_paid' => 0,
            'status' => 'refunded',
        ]);

        $loaded = Batch::withSum(['reservations as reserved_slots' => function ($q) {
            $q->where('status', 'reserved');
        }], 'quantity')->find($batch->id);

        $this->assertEquals(4, $loaded->reservedSlotCount());
        $this->assertEquals(40.0, $loaded->progressPercent());
    }
}
