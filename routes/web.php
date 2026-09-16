<?php

use App\Http\Controllers\Admin\AdminActionLogController;
use App\Http\Controllers\Admin\BatchController as AdminBatchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WarrantyRequestController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::resource('products', ProductController::class)->only(['index', 'show']);

Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
Route::get('/batches/{batch}/progress', [BatchController::class, 'progress'])->name('batches.progress');
Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/my-reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::get('/reservations/{reservation}/pay-balance', [ReservationController::class, 'payBalance'])->name('reservations.pay-balance');
    Route::post('/reservations/{reservation}/process-balance', [ReservationController::class, 'processBalancePayment'])->name('reservations.process-balance');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

    Route::get('/payment/qr/{order}', [PaymentController::class, 'qr'])->name('payment.qr');
    Route::post('/payment/qr/{order}/confirm', [PaymentController::class, 'confirm'])->name('payment.qr.confirm');

    Route::get('/my-orders', [OrderController::class, 'myOrders'])->name('orders.mine');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/my-returns', [ReturnRequestController::class, 'index'])->name('returns.mine');
    Route::get('/returns/create', [ReturnRequestController::class, 'create'])->name('returns.create');
    Route::post('/returns', [ReturnRequestController::class, 'store'])->name('returns.store');
    Route::get('/returns/{returnRequest}', [ReturnRequestController::class, 'show'])->name('returns.show');
    Route::post('/returns/{returnRequest}/cancel', [ReturnRequestController::class, 'cancel'])->name('returns.cancel');

    Route::get('/my-warranties', [WarrantyRequestController::class, 'index'])->name('warranties.mine');
    Route::get('/warranties/create', [WarrantyRequestController::class, 'create'])->name('warranties.create');
    Route::post('/warranties', [WarrantyRequestController::class, 'store'])->name('warranties.store');
    Route::get('/warranties/{warrantyRequest}', [WarrantyRequestController::class, 'show'])->name('warranties.show');
    Route::post('/warranties/{warrantyRequest}/cancel', [WarrantyRequestController::class, 'cancel'])->name('warranties.cancel');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{wishlist}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register.form');
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login.form');
});

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', AdminProductController::class);
    Route::get('/batches/export', [AdminBatchController::class, 'export'])->name('batches.export');
    Route::resource('batches', AdminBatchController::class);
    Route::patch('/batches/{batch}/force-success', [AdminBatchController::class, 'forceSuccess'])->name('batches.force-success');
    Route::patch('/batches/{batch}/close-early', [AdminBatchController::class, 'closeEarly'])->name('batches.close-early');
    Route::patch('/batches/{batch}/force-fail', [AdminBatchController::class, 'forceFail'])->name('batches.force-fail');
    Route::get('/reservations', [App\Http\Controllers\Admin\ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [App\Http\Controllers\Admin\ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/reservations/{reservation}/collect', [App\Http\Controllers\Admin\ReservationController::class, 'collectForm'])->name('reservations.collect-form');
    Route::post('/reservations/{reservation}/collect', [App\Http\Controllers\Admin\ReservationController::class, 'collectBalance'])->name('reservations.collect');
    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/{refund}', [RefundController::class, 'show'])->name('refunds.show');
    Route::get('/returns', [App\Http\Controllers\Admin\ReturnRequestController::class, 'index'])->name('returns.index');
    Route::get('/returns/{returnRequest}', [App\Http\Controllers\Admin\ReturnRequestController::class, 'show'])->name('returns.show');
    Route::post('/returns/{returnRequest}/approve', [App\Http\Controllers\Admin\ReturnRequestController::class, 'approve'])->name('returns.approve');
    Route::post('/returns/{returnRequest}/reject', [App\Http\Controllers\Admin\ReturnRequestController::class, 'reject'])->name('returns.reject');
    Route::post('/returns/{returnRequest}/receive', [App\Http\Controllers\Admin\ReturnRequestController::class, 'receive'])->name('returns.receive');
    Route::post('/returns/{returnRequest}/inspect', [App\Http\Controllers\Admin\ReturnRequestController::class, 'inspect'])->name('returns.inspect');
    Route::post('/returns/{returnRequest}/complete-no-return', [App\Http\Controllers\Admin\ReturnRequestController::class, 'completeWithoutReturn'])->name('returns.complete-no-return');
    Route::post('/returns/{returnRequest}/complete', [App\Http\Controllers\Admin\ReturnRequestController::class, 'complete'])->name('returns.complete');
    Route::get('/warranties', [App\Http\Controllers\Admin\WarrantyRequestController::class, 'index'])->name('warranties.index');
    Route::get('/warranties/{warrantyRequest}', [App\Http\Controllers\Admin\WarrantyRequestController::class, 'show'])->name('warranties.show');
    Route::post('/warranties/{warrantyRequest}/approve', [App\Http\Controllers\Admin\WarrantyRequestController::class, 'approve'])->name('warranties.approve');
    Route::post('/warranties/{warrantyRequest}/reject', [App\Http\Controllers\Admin\WarrantyRequestController::class, 'reject'])->name('warranties.reject');
    Route::post('/warranties/{warrantyRequest}/processing', [App\Http\Controllers\Admin\WarrantyRequestController::class, 'processing'])->name('warranties.processing');
    Route::post('/warranties/{warrantyRequest}/complete', [App\Http\Controllers\Admin\WarrantyRequestController::class, 'complete'])->name('warranties.complete');
    Route::get('/batch-incidents', [App\Http\Controllers\Admin\BatchIncidentController::class, 'index'])->name('batch-incidents.index');
    Route::get('/batch-incidents/create', [App\Http\Controllers\Admin\BatchIncidentController::class, 'create'])->name('batch-incidents.create');
    Route::post('/batch-incidents', [App\Http\Controllers\Admin\BatchIncidentController::class, 'store'])->name('batch-incidents.store');
    Route::get('/batch-incidents/{batchIncident}', [App\Http\Controllers\Admin\BatchIncidentController::class, 'show'])->name('batch-incidents.show');
    Route::post('/batch-incidents/{batchIncident}/resolve', [App\Http\Controllers\Admin\BatchIncidentController::class, 'resolve'])->name('batch-incidents.resolve');
    Route::get('/action-logs', [AdminActionLogController::class, 'index'])->name('action-logs.index');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/confirm-payment', [AdminOrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
    Route::post('/orders/{order}/reject-payment', [AdminOrderController::class, 'rejectPayment'])->name('orders.reject-payment');
    Route::post('/orders/{order}/collect-cod', [AdminOrderController::class, 'collectCod'])->name('orders.collect-cod');

    Route::resource('categories', App\Http\Controllers\Admin\CategoryController::class);
    Route::resource('users', UserController::class)->only(['index', 'show']);
    Route::resource('reviews', App\Http\Controllers\Admin\ReviewController::class)->only(['index', 'destroy']);
});
