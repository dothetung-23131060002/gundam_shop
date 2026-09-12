# LAB 05

## CHECKOUT – ĐƠN HÀNG – THANH TOÁN QR

## HOÀN THIỆN LUỒNG MUA HÀNG TRONG WEBSITE TMĐT

1. MỤC TIÊU

• Xây dựng trang Checkout từ dữ liệu giỏ hàng của Lab 04. • Thu thập thông tin khách hàng và địa chỉ nhận hàng. • Thiết kế bảng Orders và Order Details. • Tạo đơn hàng và chi tiết đơn hàng bằng Eloquent.

Sau Lab 05, sinh viên đã xây dựng được nghiệp vụ mua hàng cơ bản của © BÙI TÁ HẬU

• Validation dữ liệu Checkout.

• Xử lý trạng thái đơn hàng và phương thức thanh toán.

• Hiển thị mã QR mô phỏng thanh toán.

• Hoàn thiện luồng Product → Cart → Checkout → Order → Payment.

một website thương mại điện tử.

## 2. LUỒNG NGHIỆP VỤ

Sản phẩm ↓ Giỏ hàng – Lab 04 ↓ Checkout ↓ Nhập thông tin khách hàng ↓ Kiểm tra đơn hàng ↓ Tạo Order ↓ Tạo Order Details ↓ Chọn phương thức thanh toán ↓

│ COD │


│ Thanh toán QR │

↓

Xác nhận đơn hàng

↓

Trang kết quả

## 3. CẤU TRÚC THƯ MỤC / FILE

project/

├── app/

│ ├── Http/

│ │ └── Controllers/

│ │ └── CheckoutController.php

│ └── Models/

│ ├── Order.php

│ └── OrderDetail.php

├── database/

│ └── migrations/

│ ├── xxxx_create_orders_table.php

│ └── xxxx_create_order_details_table.php

├── resources/

│ └── views/

Bảng orders lưu thông tin chung của một đơn hàng: khách hàng, địa chỉ, © BÙI TÁ HẬU

│ └── checkout/

│ ├── index.blade.php

│ ├── payment.blade.php

│ └── success.blade.php

└── routes/

└── web.php

## 4. THIẾT KẾ BẢNG ORDERS

tổng tiền, phương thức thanh toán và trạng thái.

orders ------------------------------------------------ id customer_name customer_phone customer_email shipping_address total_amount payment_method payment_status order_status created_at updated_at

## 5. THIẾT KẾ BẢNG ORDER_DETAILS

Một Order có thể có nhiều Order Details. Mỗi dòng chi tiết lưu một sản phẩm và số lượng được mua tại thời điểm đặt hàng.


```
order_details
------------------------------------------------
id
order_id
product_id
product_name
price
quantity
subtotal
created_at
updated_at
```

Lưu product_name và price tại thời điểm đặt hàng giúp dữ liệu lịch sử đơn hàng không bị thay đổi theo giá sản phẩm hiện tại.

## 6. TẠO MIGRATION ORDERS

```
php artisan make:migration create_orders_table
Schema::create('orders', function (Blueprint $table) {
$table->id();
$table->string('customer_name');
$table->string('customer_phone', 20);
7. TẠO MIGRATION ORDER DETAILS © BÙI TÁ HẬU
$table->string('customer_email')->nullable();
$table->text('shipping_address');
$table->decimal('total_amount', 15, 2);
$table->string('payment_method');
$table->string('payment_status')->default('unpaid');
$table->string('order_status')->default('pending');
$table->timestamps();
});
php artisan make:migration create_order_details_table
Schema::create('order_details', function (Blueprint $table) {
$table->id();
$table->foreignId('order_id')
->constrained('orders')
->cascadeOnDelete();
$table->foreignId('product_id')
->constrained('products');
$table->string('product_name');
$table->decimal('price', 15, 2);
$table->unsignedInteger('quantity');
$table->decimal('subtotal', 15, 2);
$table->timestamps();
});
```

## 8. CHẠY MIGRATION


php artisan migrate

Sau khi chạy thành công, kiểm tra database để bảo đảm hai bảng orders và order_details đã được tạo.

## 9. TẠO MODEL

php artisan make:model Order php artisan make:model OrderDetail

## 10. MODEL ORDER

```
class Order extends Model
{
protected $fillable = [
'customer_name',
'customer_phone',
'customer_email',
'shipping_address',
'total_amount',
'payment_method',
'payment_status',
'order_status',
11. MODEL ORDER DETAIL © BÙI TÁ HẬU
];
public function details()
{
return $this->hasMany(OrderDetail::class);
}
}
class OrderDetail extends Model
{
protected $fillable = [
'order_id',
'product_id',
'product_name',
'price',
'quantity',
'subtotal',
];
public function order()
{
return $this->belongsTo(Order::class);
}
public function product()
{
return $this->belongsTo(Product::class);
}
}
```


## 12. TẠO CHECKOUT CONTROLLER

```
php artisan make:controller CheckoutController
class CheckoutController extends Controller
{
public function index()
{
$cart = session()->get('cart', []);
if (empty($cart)) {
return redirect()
->route('cart.index')
->with('error', 'Giỏ hàng đang trống.');
}
$total = 0;
foreach ($cart as $item) {
$total += $item['price'] * $item['quantity'];
}
return view('checkout.index', compact(
'cart',
© BÙI TÁ HẬU
'total'
));
}
}
```

## 13. ROUTE CHECKOUT

```
use App\Http\Controllers\CheckoutController;
Route::get('/checkout',
[CheckoutController::class, 'index'])
->name('checkout.index');
Route::post('/checkout',
[CheckoutController::class, 'store'])
->name('checkout.store');
Route::get('/checkout/payment/{order}',
[CheckoutController::class, 'payment'])
->name('checkout.payment');
Route::get('/checkout/success/{order}',
[CheckoutController::class, 'success'])
->name('checkout.success');
```

## 14. TRANG CHECKOUT

```
resources/views/checkout/index.blade.php
@extends('layouts.app')
@section('content')
```


```
<h1>Thông tin thanh toán</h1>
<form action="{{ route('checkout.store') }}"
method="POST">
@csrf
<label>Họ và tên</label>
<input type="text"
name="customer_name">
<label>Số điện thoại</label>
<input type="text"
name="customer_phone">
<label>Email</label>
<input type="email"
name="customer_email">
<label>Địa chỉ nhận hàng</label>
<textarea name="shipping_address"></textarea>
<h3>
© BÙI TÁ HẬU
Tổng tiền:
{{ number_format($total) }} VNĐ
</h3>
<button type="submit">
Đặt hàng
</button>
</form>
@endsection
```

## 15. VALIDATION CHECKOUT

```
\$request->validate([
'customer_name' => 'required|string|max:255',
'customer_phone' => 'required|string|max:20',
'customer_email' => 'nullable|email',
'shipping_address' => 'required|string',
'payment_method' => 'required|in:cod,qr',
]);
```

- Họ tên không được để trống.

- Số điện thoại không được để trống.

- Email phải đúng định dạng nếu có nhập.

- Địa chỉ nhận hàng bắt buộc phải có.

- Phương thức thanh toán chỉ nhận các giá trị được hệ thống cho phép.


## 16. THÊM PHƯƠNG THỨC THANH TOÁN

```
<label>Phương thức thanh toán</label>
<select name="payment_method">
<option value="cod">
Thanh toán khi nhận hàng
</option>
<option value="qr">
Thanh toán bằng QR
</option>
</select>
```

## 17. TẠO ĐƠN HÀNG

```
public function store(Request \$request)
{
$request->validate([
'customer_name' => 'required|string|max:255',
'customer_phone' => 'required|string|max:20',
'customer_email' => 'nullable|email',
'shipping_address' => 'required|string',
'payment_method' => 'required|in:cod,qr',
© BÙI TÁ HẬU
]);
$cart = session()->get('cart', []);
if (empty($cart)) {
return redirect()
->route('cart.index')
->with('error', 'Giỏ hàng đang trống.');
}
$total = 0;
foreach ($cart as $item) {
$total += $item['price'] * $item['quantity'];
}
$order = Order::create([
'customer_name' => $request->customer_name,
'customer_phone' => $request->customer_phone,
'customer_email' => $request->customer_email,
'shipping_address' => $request->shipping_address,
'total_amount' => $total,
'payment_method' => $request->payment_method,
'payment_status' => 'unpaid',
'order_status' => 'pending',
]);
foreach ($cart as $productId => $item) {
$order->details()->create([
'product_id' => $productId,
'product_name' => $item['name'],
'price' => $item['price'],
'quantity' => $item['quantity'],
```


```
'subtotal' =>
$item['price'] * $item['quantity'],
]);
}
session()->forget('cart');
if ($order->payment_method === 'qr') {
return redirect()
->route('checkout.payment', $order);
}
return redirect()
->route('checkout.success', $order);
}
```

## 18. QUAN HỆ ORDER → ORDER DETAILS

```
Order
│
├── OrderDetail
├── OrderDetail
└── OrderDetail
© BÙI TÁ HẬU
Một đơn hàng có nhiều sản phẩm.
```

Quan hệ Eloquent sử dụng hasMany() ở Order và belongsTo() ở

OrderDetail.

## 19. TRẠNG THÁI ĐƠN HÀNG

```
order_status
pending
↓
confirmed
↓
shipping
↓
completed
↘
cancelled
```

Các trạng thái có thể được mở rộng tùy nghiệp vụ của đề tài.

## 20. TRẠNG THÁI THANH TOÁN

```
payment_status
unpaid
↓
paid
```


```
hoặc
unpaid
↓
failed
```

## 21. THANH TOÁN COD

Nếu người mua chọn COD, đơn hàng được tạo với payment_status = unpaid. Đơn hàng có thể chuyển sang paid khi hệ thống xác nhận đã thu tiền.

```
if (\$order->payment_method === 'cod') {
return redirect()
->route('checkout.success', $order);
}
```

## 22. THANH TOÁN QR – MÔ PHỎNG

Trong Lab này, QR được dùng để mô phỏng giao diện và luồng thanh toán. Sinh viên chưa cần kết nối trực tiếp với API ngân hàng hoặc cổng

thanh toán thật.

```
© BÙI TÁ HẬU
resources/views/checkout/payment.blade.php
@extends('layouts.app')
@section('content')
<h1>Thanh toán QR</h1>
<p>
Mã đơn hàng:
#{{ $order->id }}
</p>
<p>
Số tiền:
{{ number_format($order->total_amount) }} VNĐ
</p>
<div class="qr-payment">
<img
src="{{ asset('images/qr-payment.png') }}"
alt="QR thanh toán"
>
</div>
<p>
Vui lòng quét mã QR để thanh toán.
</p>
<a href="{{ route(
```


```
'checkout.success',
$order
) }}">
Tôi đã thanh toán
</a>
@endsection
```

## 23. VỊ TRÍ FILE QR

```
public/
└── images/
└── qr-payment.png
```

Sinh viên tạo thư mục public/images nếu project chưa có. Có thể sử dụng QR minh họa trong Lab hoặc tạo QR theo thông tin của đề tài.

## 24. TRANG ĐẶT HÀNG THÀNH CÔNG

```
resources/views/checkout/success.blade.php
@extends('layouts.app')
© BÙI TÁ HẬU
@section('content')
<h1>Đặt hàng thành công!</h1>
<p>
Mã đơn hàng:
#{{ $order->id }}
</p>
<p>
Khách hàng:
{{ $order->customer_name }}
</p>
<p>
Tổng tiền:
{{ number_format($order->total_amount) }} VNĐ
</p>
<p>
Phương thức:
{{ $order->payment_method }}
</p>
<a href="{{ url('/') }}">
Tiếp tục mua hàng
</a>
@endsection
```

## 25. HIỂN THỊ DANH SÁCH SẢN PHẨM TRONG ĐƠN


```
@foreach(\$order->details as \$detail)
<div>
<strong>
{{ $detail->product_name }}
</strong>
<p>
{{ $detail->quantity }} x
{{ number_format($detail->price) }}
VNĐ
</p>
<p>
Thành tiền:
{{ number_format($detail->subtotal) }}
VNĐ
</p>
</div>
@endforeach
```

## 26. XỬ LÝ LỖI QUAN TRỌNG

- Chỉ xóa Cart sau khi Order và Order Details được tạo thành công. © BÙI TÁ HẬU • Không cho phép tự ý thay đổi tổng tiền từ form phía trình duyệt.

- Không cho phép Checkout khi giỏ hàng rỗng.

- Không cho phép tạo đơn nếu dữ liệu khách hàng không hợp lệ.

- Kiểm tra lại sản phẩm và số lượng trước khi tạo Order.

## 27. LUỒNG DỮ LIỆU HOÀN CHỈNH

```
SESSION CART
│
│ checkout
CHECKOUT FORM
│
│ validate
ORDERS
│
▼ ▼
ORDER_DETAILS PAYMENT
│
┌──────┴──────┐
▼ ▼
COD QR
│ │
└──────┬──────┘
```


ORDER SUCCESS

## 28. ÁP DỤNG CHO ĐỀ TÀI KHÁC

Bán quần áo Customer → Cart → Checkout → Order → QR

Bán máy tính Customer → Cart → Checkout → Order → QR

Bán sách Customer → Cart → Checkout → Order → QR

Bán mỹ phẩm Customer → Cart → Checkout → Order → QR

Sinh viên giữ nguyên tư duy nghiệp vụ nhưng thay đổi tên sản phẩm, thuộc tính, giao diện và các quy tắc riêng của đề tài.

## 29. MỞ RỘNG NGHIỆP VỤ

• Tự động tạo mã đơn hàng dễ đọc.

• Cho phép khách xem lịch sử đơn hàng. © BÙI TÁ HẬU

• Thêm phí vận chuyển.

• Thêm mã giảm giá.

• Kiểm tra tồn kho trước khi tạo Order.

• Gửi email xác nhận đơn hàng.

• Cho phép Admin cập nhật trạng thái đơn. • Tích hợp cổng thanh toán thật ở mức nâng cao.

## 30. KIỂM TRA KẾT QUẢ

• Từ Cart có thể đi tới Checkout. • Form Checkout hiển thị đúng tổng tiền. • Validation hoạt động. • Tạo được Order. • Tạo được nhiều Order Details cho một Order. • Đơn hàng lưu đúng phương thức thanh toán.

• COD đi tới trang thành công.


- QR hiển thị đúng mã thanh toán mô phỏng.

- Cart được xóa sau khi tạo đơn.

- Trang thành công hiển thị đúng thông tin đơn hàng.

## 31. CẤU TRÚC PROJECT SAU LAB 05

Laravel Project

│

├── app/

│ ├── Http/Controllers/

│ │ ├── ProductController.php

│ │ ├── CartController.php

│ │ └── CheckoutController.php

│ │

│ └── Models/

│ ├── Product.php

│ ├── Order.php

│ └── OrderDetail.php

│

├── database/migrations/

│ ├── ...create_products_table.php

│ ├── ...create_orders_table.php

│ └── ...create_order_details_table.php

│

├── public/images/

│ └── qr-payment.png

│

├── resources/views/

│ ├── products/

│ ├── cart/

│ └── checkout/

│ ├── index.blade.php

│ ├── payment.blade.php

│ └── success.blade.php

│

└── routes/

└── web.php

© BÙI TÁ HẬU

## 32. KẾT NỐI SANG LAB 06

Sau Lab 05, hệ thống đã có nghiệp vụ mua hàng cơ bản. Lab 06 sẽ tập trung vào tài khoản người dùng, đăng nhập, phân quyền và khu vực quản trị để quản lý hệ thống.
