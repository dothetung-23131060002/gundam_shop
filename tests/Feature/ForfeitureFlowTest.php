<?php

namespace Tests\Feature;

use App\Exceptions\RefundException;
use App\Models\Batch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RefundTransaction;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\DepositForfeited;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForfeitureFlowTest extends TestCase
{
    use RefreshDatabase;

    private RefundService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RefundService::class);
    }

    private function makeSuccessBatch(Product $product): Batch
    {
        return Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDay(),
            'status' => 'success',
        ]);
    }

    private function makeOverdueReservation(User $user, Batch $batch, float $deposit = 100000, string $status = 'reserved'): Reservation
    {
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => $deposit,
            'status' => $status,
        ]);

        // Giả lập "không động đậy" quá grace period (proxy deadline).
        DB::table('reservations')->where('id', $reservation->id)->update([
            'updated_at' => now()->subDays(30)->toDateTimeString(),
        ]);

        return $reservation->fresh();
    }

    public function test_overdue_reservation_is_forfeited_with_correct_record()
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        $reservation = $this->makeOverdueReservation($user, $batch);

        Artisan::call('reservations:forfeit-overdue');

        $record = RefundTransaction::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($record);
        $this->assertSame(RefundTransaction::REASON_FORFEITED, $record->reason);
        $this->assertSame(RefundTransaction::TYPE_DEPOSIT, $record->type);
        $this->assertSame(RefundTransaction::STATUS_COMPLETED, $record->status);
        $this->assertNull($record->payment_id);
        $this->assertNotNull($record->refunded_at);
        $this->assertEquals(100000, (float) $record->amount);

        // Không tạo Payment.
        $this->assertSame(0, Payment::where('type', 'refund')->count());

        // Đóng cọc + đóng trạng thái (interim).
        $reservation = $reservation->fresh();
        $this->assertEquals(0, (float) $reservation->deposit_paid);
        $this->assertSame('cancelled', $reservation->status);

        // Slot + heldDeposit được giải phóng.
        $this->assertSame(0, $batch->fresh()->reservedSlotCount());
        $this->assertSame(0.0, (float) Reservation::where('status', 'reserved')->sum('deposit_paid'));

        Notification::assertSentTo($user, DepositForfeited::class);
    }

    public function test_forfeiture_excluded_from_monetary_totals_and_net()
    {
        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        $reservation = $this->makeOverdueReservation($user, $batch);

        Artisan::call('reservations:forfeit-overdue');

        $totals = $this->service->refundTotals();
        $this->assertSame(0.0, $totals['refund_total']);
        $this->assertEquals(100000, $totals['forfeiture_total']);

        // Net Revenue = Gross - Monetary Refunds: forfeiture không trừ.
        $gross = (float) Order::where('payment_status', Order::PAY_PAID)->sum('total_amount');
        $net = $gross - $totals['refund_total'];
        $this->assertSame(0.0, $gross);
        $this->assertSame(0.0, $net);
    }

    public function test_scheduler_second_run_does_not_double_forfeit()
    {
        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        $reservation = $this->makeOverdueReservation($user, $batch);

        Artisan::call('reservations:forfeit-overdue');
        Artisan::call('reservations:forfeit-overdue');

        $this->assertSame(1, RefundTransaction::where('reservation_id', $reservation->id)->count());

        // Gọi service trực tiếp lần 2 cũng bị chặn.
        try {
            $this->service->recordForfeiture($reservation->id);
            $this->fail('Expected block on second forfeiture.');
        } catch (RefundException $e) {
            $this->assertContains($e->errorCode, [
                RefundException::DUPLICATE_REFUND,
                RefundException::NOT_REFUNDABLE_STATE,
                RefundException::NOTHING_TO_REFUND,
            ]);
        }
    }

    public function test_converted_paid_reservation_is_skipped()
    {
        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        $reservation = $this->makeOverdueReservation($user, $batch);
        $reservation->update(['status' => 'converted']);

        $exit = Artisan::call('reservations:forfeit-overdue');

        $this->assertSame(0, $exit);
        $this->assertSame(0, RefundTransaction::count());
        $this->assertEquals(100000, (float) $reservation->fresh()->deposit_paid);
    }

    public function test_cancelled_reservation_is_skipped()
    {
        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        $reservation = $this->makeOverdueReservation($user, $batch, 100000, 'cancelled');

        Artisan::call('reservations:forfeit-overdue');

        $this->assertSame(0, RefundTransaction::count());
    }

    public function test_reservation_with_live_order_is_skipped_safely()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();
        $batch = $this->makeSuccessBatch($product);
        $reservation = $this->makeOverdueReservation($user, $batch);

        // Đơn claim sống gắn reservation.
        $order = Order::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'reservation_id' => $reservation->id,
            'customer_name' => 'Test',
            'customer_phone' => '0900000000',
            'shipping_address' => 'Test',
            'total_amount' => 600000,
            'payment_method' => 'balance',
            'payment_status' => Order::PAY_AWAITING,
            'order_status' => 'pending',
        ]);

        $exit = Artisan::call('reservations:forfeit-overdue');

        $this->assertSame(0, $exit);
        $this->assertSame(0, RefundTransaction::count());
        $this->assertEquals(100000, (float) $reservation->fresh()->deposit_paid);
        $this->assertSame('reserved', $reservation->fresh()->status);

        // Service trực tiếp cũng từ chối.
        try {
            $this->service->recordForfeiture($reservation->id);
            $this->fail('Expected DUPLICATE_REFUND (live order).');
        } catch (RefundException $e) {
            $this->assertSame(RefundException::DUPLICATE_REFUND, $e->errorCode);
        }
    }

    public function test_recent_reservation_is_not_overdue()
    {
        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        // updated_at = now → chưa quá grace 7 ngày.
        Reservation::create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'quantity' => 2,
            'deposit_paid' => 100000,
            'status' => 'reserved',
        ]);

        Artisan::call('reservations:forfeit-overdue');

        $this->assertSame(0, RefundTransaction::count());
    }

    public function test_dry_run_does_not_mutate_db()
    {
        Notification::fake();

        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        $reservation = $this->makeOverdueReservation($user, $batch);

        Artisan::call('reservations:forfeit-overdue', ['--dry-run' => true]);

        $this->assertSame(0, RefundTransaction::count());
        $this->assertSame(0, Payment::count());
        $reservation = $reservation->fresh();
        $this->assertEquals(100000, (float) $reservation->deposit_paid);
        $this->assertSame('reserved', $reservation->status);
        Notification::assertNothingSent();
    }

    public function test_one_failure_does_not_stop_others()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();
        $batch = $this->makeSuccessBatch($product);

        // Eligible.
        $good = $this->makeOverdueReservation($user, $batch);
        // Không đủ điều kiện (đã converted) — service sẽ từ chối nếu gọi.
        $bad = $this->makeOverdueReservation($user, $batch);
        $bad->update(['status' => 'converted']);

        // Eligible thứ hai để chứng minh lô vẫn chạy tiếp sau skip.
        $good2 = $this->makeOverdueReservation(User::factory()->create(['role' => 'user']), $batch);

        $exit = Artisan::call('reservations:forfeit-overdue');

        $this->assertSame(0, $exit);
        $this->assertSame(1, RefundTransaction::where('reservation_id', $good->id)->count());
        $this->assertSame(1, RefundTransaction::where('reservation_id', $good2->id)->count());
        $this->assertSame(0, RefundTransaction::where('reservation_id', $bad->id)->count());
    }

    public function test_overdue_proxy_helper_single_source()
    {
        $user = User::factory()->create(['role' => 'user']);
        $batch = $this->makeSuccessBatch(Product::factory()->create());
        $old = $this->makeOverdueReservation($user, $batch);

        $this->assertTrue($this->service->isOverdueForForfeiture($old));
        // Grace override lớn → chưa quá hạn.
        $this->assertFalse($this->service->isOverdueForForfeiture($old, 60));
        $this->assertSame(7, $this->service->forfeitureGraceDays());
        $this->assertSame(30, $this->service->forfeitureGraceDays(30));
    }
}
