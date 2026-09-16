<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use App\Services\VietQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    private function setFullConfig(): void
    {
        config()->set('vietqr.account_name', 'DO THE TUNG');
        config()->set('vietqr.template', 'compact2');
        config()->set('vietqr.default', 'mb');
        config()->set('vietqr.max_add_info', 25);
        config()->set('vietqr.methods', [
            'mb' => ['label' => 'MB Bank', 'type' => 'bank', 'bank_id' => 'MB', 'account_no' => '111111'],
            'tcb' => ['label' => 'Techcombank', 'type' => 'bank', 'bank_id' => 'TCB', 'account_no' => '222222'],
            'vpb' => ['label' => 'VPBank', 'type' => 'bank', 'bank_id' => 'VPB', 'account_no' => '333333'],
            'momo' => ['label' => 'MoMo', 'type' => 'wallet', 'account_no' => '0912345678'],
        ]);
    }

    private function setEmptyConfig(): void
    {
        config()->set('vietqr.methods', [
            'mb' => ['label' => 'MB Bank', 'type' => 'bank', 'bank_id' => '', 'account_no' => ''],
            'tcb' => ['label' => 'Techcombank', 'type' => 'bank', 'bank_id' => '', 'account_no' => ''],
            'vpb' => ['label' => 'VPBank', 'type' => 'bank', 'bank_id' => '', 'account_no' => ''],
            'momo' => ['label' => 'MoMo', 'type' => 'wallet', 'account_no' => ''],
        ]);
    }

    private function makeOrder(User $user, int $total = 500000): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
            'total_amount' => $total,
            'payment_method' => 'qr',
            'payment_status' => 'unpaid',
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

    // ---- addInfo ----

    public function test_order_addinfo_id_first_and_normalized()
    {
        $addInfo = VietQrService::orderAddInfo(12, 'Gundam thanh toán');

        $this->assertSame('DH12 GUNDAM THANH TOAN', $addInfo);
    }

    public function test_addinfo_truncate_keeps_id_intact()
    {
        $addInfo = VietQrService::orderAddInfo(12, 'Mo ta rat dai vuot qua gioi han ky tu cho phep');

        $this->assertLessThanOrEqual(25, mb_strlen($addInfo));
        $this->assertStringStartsWith('DH12', $addInfo);
    }

    public function test_deposit_and_balance_addinfo_formats()
    {
        $this->assertSame('R15 COC B3', VietQrService::depositAddInfo(15, 3));
        $this->assertSame('R15 TT', VietQrService::balanceAddInfo(15));
    }

    public function test_amount_zero_is_invalid()
    {
        $this->assertFalse(VietQrService::isValidAmount(0));
        $this->assertFalse(VietQrService::isValidAmount(-100));

        $options = VietQrService::buildOptions(0, 'DH1');
        $this->assertFalse($options['amount_valid']);
    }

    // ---- method validity ----

    public function test_incomplete_method_is_excluded_from_tabs()
    {
        $this->setFullConfig();
        config()->set('vietqr.methods.tcb.bank_id', '');

        $valid = VietQrService::validMethods();

        $this->assertArrayHasKey('mb', $valid);
        $this->assertArrayNotHasKey('tcb', $valid);
        $this->assertArrayHasKey('momo', $valid);
    }

    public function test_empty_config_has_no_valid_methods()
    {
        $this->setEmptyConfig();

        $this->assertSame([], VietQrService::validMethods());
        $this->assertFalse(VietQrService::hasValidMethods());
    }

    public function test_bank_url_uses_compact2_amount_and_encoded_addinfo()
    {
        $this->setFullConfig();

        $url = VietQrService::buildBankUrl('MB', '111111', 'compact2', 500000, 'DH12');

        $this->assertStringContainsString('img.vietqr.io/image/MB-111111-compact2.png', $url);
        $this->assertStringContainsString('amount=500000', $url);
        $this->assertStringContainsString('addInfo=DH12', $url);
        $this->assertStringContainsString('accountName='.rawurlencode('DO THE TUNG'), $url);
    }

    // ---- views: 4 tabs, MB default, MoMo text-only ----

    public function test_payment_qr_page_shows_4_tabs_with_mb_default()
    {
        $this->setFullConfig();
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get("/payment/qr/{$order->id}");

        $response->assertOk();
        $response->assertSee('data-method-tab="mb"', false);
        $response->assertSee('data-method-tab="tcb"', false);
        $response->assertSee('data-method-tab="vpb"', false);
        $response->assertSee('data-method-tab="momo"', false);
        $response->assertSee('role="tablist"', false);
        // MB is the default active tab.
        $response->assertSee('data-method-tab="mb"', false);
        // 3 bank QR images + 3 fallback links share the URL; MoMo has none.
        $this->assertSame(6, substr_count($response->getContent(), 'img.vietqr.io'));
        // MoMo info is text-only.
        $response->assertSee('0912345678');
        $response->assertSee('DH'.$order->id);
    }

    public function test_momo_panel_contains_no_qr_image()
    {
        $this->setFullConfig();
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get("/payment/qr/{$order->id}");
        $content = $response->getContent();

        $momoPos = strpos($content, 'data-method-panel="momo"');
        $this->assertNotFalse($momoPos);
        $momoPanel = substr($content, $momoPos, 2000);
        $this->assertStringNotContainsString('<img', $momoPanel);
        $this->assertStringContainsString('MoMo', $momoPanel);
    }

    public function test_missing_method_tab_is_not_rendered()
    {
        $this->setFullConfig();
        config()->set('vietqr.methods.tcb.bank_id', '');
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get("/payment/qr/{$order->id}");

        $response->assertOk();
        $response->assertDontSee('data-method-tab="tcb"', false);
        $this->assertSame(4, substr_count($response->getContent(), 'img.vietqr.io'));
    }

    public function test_empty_config_shows_warning_and_disables_confirm()
    {
        $this->setEmptyConfig();
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->get("/payment/qr/{$order->id}");

        $response->assertOk();
        $response->assertSee('Chưa cấu hình phương thức thanh toán');
        $response->assertSee('disabled', false);
        $response->assertDontSee('img.vietqr.io');
    }

    public function test_pay_balance_view_disables_submit_when_empty_config()
    {
        $this->setEmptyConfig();
        $user = User::factory()->create(['role' => 'user']);
        $reservation = $this->makePayableReservation($user);

        $response = $this->actingAs($user)->get("/reservations/{$reservation->id}/pay-balance");

        $response->assertOk();
        $response->assertSee('Chưa cấu hình phương thức thanh toán');
        $response->assertSee('disabled', false);
    }

    public function test_reservation_show_includes_deposit_qr_info()
    {
        $this->setFullConfig();
        $user = User::factory()->create(['role' => 'user']);
        $reservation = $this->makePayableReservation($user);

        $response = $this->actingAs($user)->get("/reservations/{$reservation->id}");

        $response->assertOk();
        $response->assertSee('R'.$reservation->id.' COC B'.$reservation->batch_id);
    }

    // ---- backend guards ----

    public function test_confirm_rejected_when_no_methods()
    {
        $this->setEmptyConfig();
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_confirm_moves_to_awaiting_not_paid()
    {
        $this->setFullConfig();
        $user = User::factory()->create(['role' => 'user']);
        $order = $this->makeOrder($user);

        $response = $this->actingAs($user)->post("/payment/qr/{$order->id}/confirm");

        $response->assertRedirect();
        $this->assertSame('awaiting_confirmation', $order->refresh()->payment_status);
    }

    public function test_process_balance_rejected_when_no_methods()
    {
        $this->setEmptyConfig();
        $user = User::factory()->create(['role' => 'user']);
        $reservation = $this->makePayableReservation($user);

        $response = $this->actingAs($user)->post("/reservations/{$reservation->id}/process-balance", [
            'customer_name' => 'Test User',
            'customer_phone' => '0123456789',
            'shipping_address' => '123 Test Street',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('reserved', $reservation->refresh()->status);
        $this->assertSame(0, Order::where('user_id', $user->id)->count());
    }
}
