<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Notifications\BatchOpened;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'customer'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeProduct(): Product
    {
        return Product::factory()->create(['quantity' => 100]);
    }

    private function makeOpenBatch(Product $product): Batch
    {
        return Batch::create([
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7),
            'status' => 'open',
        ]);
    }

    // Test 1: add via toggle.
    public function test_user_can_add_product_to_wishlist()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $response = $this->actingAs($user)->postJson(route('wishlist.toggle', $product));

        $response->assertOk()->assertJson(['status' => 'added', 'wishlist_count' => 1]);
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    // Test 2: toggle twice.
    public function test_toggle_twice_adds_then_removes()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($user)->postJson(route('wishlist.toggle', $product))
            ->assertOk()->assertJson(['status' => 'added']);
        $this->actingAs($user)->postJson(route('wishlist.toggle', $product))
            ->assertOk()->assertJson(['status' => 'removed', 'wishlist_count' => 0]);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    // Test 3: duplicate unique constraint.
    public function test_duplicate_wishlist_blocked_by_unique_constraint()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        try {
            Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
            $this->fail('Expected duplicate entry exception.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertEquals('23000', $e->getCode());
        }

        $this->assertEquals(1, Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->count());
    }

    // Test 4: guest gets 401 JSON, not HTML redirect.
    public function test_guest_toggle_returns_401_json()
    {
        $product = $this->makeProduct();

        $response = $this->postJson(route('wishlist.toggle', $product));

        $response->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->assertEquals(0, Wishlist::count());
    }

    // Test 5: ownership on delete.
    public function test_user_cannot_delete_another_users_wishlist()
    {
        $userA = $this->makeUser();
        $userB = $this->makeUser();
        $product = $this->makeProduct();
        $wishlist = Wishlist::create(['user_id' => $userB->id, 'product_id' => $product->id]);

        $this->actingAs($userA)->delete(route('wishlist.destroy', $wishlist))
            ->assertForbidden();
        $this->assertDatabaseHas('wishlists', ['id' => $wishlist->id]);
    }

    public function test_owner_can_delete_own_wishlist()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        $wishlist = Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)->delete(route('wishlist.destroy', $wishlist))
            ->assertRedirect();
        $this->assertDatabaseMissing('wishlists', ['id' => $wishlist->id]);
    }

    // Test 6: cascade on product delete.
    public function test_wishlist_cascades_on_product_delete()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $product->delete();

        $this->assertEquals(0, Wishlist::count());
    }

    public function test_wishlist_cascades_on_user_delete()
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $user->delete();

        $this->assertEquals(0, Wishlist::count());
    }

    // Extras.
    public function test_wishlist_page_pagination_and_open_batch()
    {
        $user = $this->makeUser();
        $withBatch = $this->makeProduct();
        $batch = $this->makeOpenBatch($withBatch);
        $withoutBatch = $this->makeProduct();
        Wishlist::create(['user_id' => $user->id, 'product_id' => $withBatch->id]);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $withoutBatch->id]);

        $response = $this->actingAs($user)->get(route('wishlist.index'));

        $response->assertOk();
        $response->assertSee('Đang mở đợt gom - còn '.($batch->threshold - $batch->reservedSlotCount()).' slot');
        $response->assertSee('Giữ chỗ ngay');
        $response->assertSee('Chưa có đợt gom mới');
        $response->assertSee(route('batches.show', $batch), false);
    }

    public function test_wishlist_page_empty_state()
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('wishlist.index'));

        $response->assertOk();
        $response->assertSee('Danh sách yêu thích đang trống');
        $response->assertSee(route('products.index'));
    }

    public function test_wishlist_page_requires_auth()
    {
        $this->get(route('wishlist.index'))->assertRedirect(route('login.form'));
    }

    public function test_is_wishlisted_by()
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $product = $this->makeProduct();

        $this->assertFalse($product->isWishlistedBy(null));
        $this->assertFalse($product->isWishlistedBy($user));

        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->assertTrue($product->fresh()->isWishlistedBy($user));
        $this->assertFalse($product->fresh()->isWishlistedBy($other));
    }

    public function test_batch_creation_notifies_wishlist_users_only()
    {
        Notification::fake();
        $admin = $this->makeUser('admin');
        $fan = $this->makeUser();
        $stranger = $this->makeUser();
        $product = $this->makeProduct();
        Wishlist::create(['user_id' => $fan->id, 'product_id' => $product->id]);

        $this->actingAs($admin)->post(route('admin.batches.store'), [
            'product_id' => $product->id,
            'threshold' => 5,
            'deposit_amount' => 50000,
            'deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('admin.batches.index'));

        Notification::assertSentTo($fan, BatchOpened::class);
        Notification::assertNotSentTo($stranger, BatchOpened::class);
        Notification::assertNotSentTo($admin, BatchOpened::class);
    }
}
