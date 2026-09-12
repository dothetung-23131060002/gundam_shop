# **LAB 04** 

## **GIỎ HÀNG – SESSION – VALIDATION** 

## **XÂY DỰNG CHỨC NĂNG GIỎ HÀNG CHO WEBSITE TMĐT** 

## **1. MỤC TIÊU** 

- Xây dựng chức năng thêm sản phẩm vào giỏ hàng. 

- Lưu thông tin giỏ hàng bằng Session của Laravel. 

- Hiển thị danh sách sản phẩm trong giỏ hàng. 

- Tăng, giảm và xóa sản phẩm khỏi giỏ hàng. 

- Tính thành tiền và tổng tiền. • Kiểm tra dữ liệu trước khi thêm sản phẩm vào giỏ. • Chuẩn bị luồng chuyển từ Giỏ hàng sang Checkout ở Lab 05. Sau Lab 04, sinh viên hiểu được mối liên hệ giữa Product → Cart → Checkout trong một website thương mại điện tử. 

Sau Lab 04, sinh viên hiểu được mối liên hệ giữa Product → Cart → Checkout trong một website thương mại điện tử. 

## **2. CẤU TRÚC NỘI DUNG LAB** 

LAB 03 Trang chủ + Hình ảnh + Sản phẩm ↓ LAB 04 Giỏ hàng + Session + Validation ↓ LAB 05 Checkout + Đơn hàng + Thanh toán QR ↓ LAB 06 Authentication + Authorization + Admin ↓ LAB 07 Search + Filter + Review + Hoàn thiện ↓ LAB 08 Tự xây dựng website TMĐT theo đề tài riêng 

## **3. LUỒNG HOẠT ĐỘNG CỦA GIỎ HÀNG** 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 1** 

Trang sản phẩm ↓ [Thêm vào giỏ] ↓ Session Cart ↓ Trang giỏ hàng ↓ Tăng / Giảm số lượng ↓ Xóa sản phẩm ↓ Tính tổng tiền ↓ [Tiến hành thanh toán] ↓ Lab 05 – Checkout 

Trong Lab này chưa xử lý thanh toán thực tế. Phần Checkout và QR Payment được triển khai ở Lab 05. 

## **4. CẤU TRÚC THƯ MỤC / FILE** 

project/ ├── app/ │ └── Http/ │ └── Controllers/ │ └── CartController.php ├── resources/ │ └── views/ │ └── cart/ │ └── index.blade.php └── routes/ └── web.php 

#### 

Sinh viên tạo mới CartController và thư mục resources/views/cart nếu chưa có. 

## **5. TẠO CART CONTROLLER** 

php artisan make:controller CartController 

<?php namespace App\Http\Controllers; use App\Models\Product; use Illuminate\Http\Request; class CartController extends Controller { public function index() { $cart = session()->get('cart', []); 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 2** 

return view('cart.index', compact('cart')); } } 

## **6. SESSION TRONG LARAVEL** 

Session cho phép lưu dữ liệu của người dùng trong quá trình họ sử dụng website. Trong Lab này, Session được sử dụng để lưu giỏ hàng. 

$cart = session()->get('cart', []); 

session()->put('cart', $cart); 

## **7. CẤU TRÚC DỮ LIỆU GIỎ HÀNG** 

$cart = [ 1 => [ 'name' => 'Táo', 'price' => 50000, 'quantity' => 2, 'image' => 'tao.jpg' ], 5 => [ 'name' => 'Cam', 'price' => 40000, 'quantity' => 3, 'image' => 'cam.jpg' ] ]; 

#### 

Key của mảng có thể sử dụng product_id để tìm và cập nhật sản phẩm trong giỏ hàng. 

## **8. ROUTE CHO GIỎ HÀNG** 

use App\Http\Controllers\CartController; 

Route::get('/cart', [CartController::class, 'index']) ->name('cart.index'); 

Route::post('/cart/add/{product}', [CartController::class, 'add']) ->name('cart.add'); 

Route::patch('/cart/update/{product}', [CartController::class, 'update']) ->name('cart.update'); 

Route::delete('/cart/remove/{product}', [CartController::class, 'remove']) ->name('cart.remove'); 

Route::delete('/cart/clear', [CartController::class, 'clear']) ->name('cart.clear'); 

## **9. THÊM SẢN PHẨM VÀO GIỎ** 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 3** 

public function add(Request $request, Product $product) { $request->validate([ 'quantity' => 'required|integer|min:1', ]); $cart = session()->get('cart', []); if (isset($cart[$product->id])) { $cart[$product->id]['quantity'] += $request->quantity; } else { $cart[$product->id] = [ 'name' => $product->name, 'price' => $product->price, 'quantity' => $request->quantity, 'image' => $product->image, ]; } session()->put('cart', $cart); return redirect()->route('cart.index') ->with('success', 'Đã thêm sản phẩm vào giỏ hàng.'); } **10. NÚT THÊM VÀO GIỎ** <form action="{{ route('cart.add', $product) }}" method="POST"> @csrf <input type="number" name="quantity" value="1" min="1"> 

<form action="{{ route('cart.add', $product) }}" method="POST"> @csrf <input type="number" name="quantity" value="1" min="1"> <button type="submit"> Thêm vào giỏ </button> </form> 

Có thể đặt form trong trang chi tiết sản phẩm hoặc card sản phẩm. 

## **11. HIỂN THỊ GIỎ HÀNG** 

@foreach($cart as $id => $item) <div class="cart-item"> <h3>{{ $item['name'] }}</h3> <p> Đơn giá: {{ number_format($item['price']) }} VNĐ </p> <p> Số lượng: 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 4** 

{{ $item['quantity'] }} </p> <p> Thành tiền: {{ number_format( $item['price'] * $item['quantity'] ) }} VNĐ </p> </div> @endforeach 

## **12. TÍNH TỔNG TIỀN** 

$total = 0; foreach ($cart as $item) { $total += $item['price'] * $item['quantity']; } 

### Truyền $total sang Blade để hiển thị tổng tiền. 

**13. CẬP NHẬT SỐ LƯỢNG** public function update(Request $request, Product $product) { $request->validate([ 'quantity' => 'required|integer|min:1', ]); $cart = session()->get('cart', []); if (isset($cart[$product->id])) { $cart[$product->id]['quantity'] = $request->quantity; } session()->put('cart', $cart); return redirect()->route('cart.index'); } 

## **14. FORM CẬP NHẬT** 

<form action="{{ route('cart.update', $id) }}" method="POST"> @csrf @method('PATCH') <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1"> <button type="submit">Cập nhật</button> </form> 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 5** 

## **15. XÓA MỘT SẢN PHẨM** 

public function remove(Product $product) { $cart = session()->get('cart', []); 

unset($cart[$product->id]); 

session()->put('cart', $cart); 

return redirect()->route('cart.index'); } 

<form action="{{ route('cart.remove', $id) }}" method="POST"> @csrf @method('DELETE') <button type="submit">Xóa</button> </form> 

## **16. XÓA TOÀN BỘ GIỎ HÀNG** 

public function clear() { session()->forget('cart'); return redirect()->route('cart.index'); } <form action="{{ route('cart.clear') }}" method="POST"> @csrf @method('DELETE') <button type="submit"> Xóa toàn bộ giỏ hàng </button> </form> 

## **17. TRANG CART INDEX** 

resources/views/cart/index.blade.php 

@extends('layouts.app') @section('content') <h1>Giỏ hàng</h1> @if(empty($cart)) <p>Giỏ hàng đang trống.</p> @else @foreach($cart as $id => $item) <div> <h3>{{ $item['name'] }}</h3> 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 6** 

<p> Đơn giá: {{ number_format($item['price']) }} VNĐ </p> <p> Số lượng: {{ $item['quantity'] }} </p> <p> Thành tiền: {{ number_format( $item['price'] * $item['quantity'] ) }} VNĐ </p> </div> @endforeach @endif @endsection 

## **18. HIỂN THỊ THÔNG BÁO** 

@if(session('success')) <div class="alert alert-success"> {{ session('success') }} </div> @endif @if($errors->any()) <div class="alert alert-danger"> @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach </div> @endif 

## **19. VALIDATION CHO GIỎ HÀNG** 

- Số lượng phải là số nguyên. 

- Số lượng phải lớn hơn hoặc bằng 1. 

- Không cho phép quantity rỗng. 

- Không cho phép thêm sản phẩm không tồn tại. 

- Không cho phép cập nhật sản phẩm không có trong giỏ. 

- Giỏ hàng rỗng phải được xử lý riêng. 

## **20. GIAO DIỆN GIỎ HÀNG MỤC TIÊU** 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 7** 

┌─────────────────────────────────────────────────┐ │ GIỎ HÀNG │ ├─────────────────────────────────────────────────┤ │ Sản phẩm SL Đơn giá Thành tiền │ │ │ │ Táo 2 50.000 100.000 │ │ Cam 3 40.000 120.000 │ │ │ │ Tổng: 220.000 VNĐ │ │ │ │ [Tiếp tục mua hàng] [Tiến hành thanh toán] │ └─────────────────────────────────────────────────┘ 

Giao diện thực tế có thể thiết kế khác mẫu trên. Điều quan trọng là đầy đủ thông tin và chức năng. 

## **21. CHUYỂN SANG CHECKOUT** 

<a href="{{ route('checkout.index') }}" class="btn btn-primary"> Tiến hành thanh toán </a> 

Route checkout sẽ được xây dựng ở Lab 05. Nếu chưa có route, có thể để nút ở dạng giao diện để kiểm tra. 

## **22. ÁP DỤNG CHO ĐỀ TÀI KHÁC** 

Logic giỏ hàng không phụ thuộc vào Fruit Variety Shop. Sinh viên có thể áp dụng cho mọi đề tài có nghiệp vụ mua hàng. 

Website bán quần áo Product → Cart → Checkout 

Website bán máy tính Product → Cart → Checkout 

Website bán sách Book → Cart → Checkout Website bán mỹ phẩm Product → Cart → Checkout 

Thông tin sản phẩm có thể thay đổi theo đề tài, còn luồng Cart có thể giữ nguyên. 

## **23. CÁC TRƯỜNG HỢP MỞ RỘNG** 

- Giới hạn số lượng mua không vượt quá tồn kho. 

- Hiển thị số lượng sản phẩm trong Cart trên Header. 

- Tự động cập nhật tổng tiền. 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 8** 

- Thêm phí vận chuyển. 

- Thêm mã giảm giá. 

- Tính tổng tiền sau giảm giá. 

- Lưu giỏ hàng cho người dùng đăng nhập. 

- Xử lý sản phẩm hết hàng. 

## **24. KIỂM TRA KẾT QUẢ** 

- Truy cập được trang Giỏ hàng. 

- Thêm được sản phẩm từ trang sản phẩm. 

- Giỏ hàng được lưu trong Session. 

- Tăng/giảm số lượng hoạt động. 

- Xóa một sản phẩm hoạt động. 

- Xóa toàn bộ giỏ hàng hoạt động. 

- Tính đúng thành tiền và tổng tiền. 

- Validation hoạt động. 

- Hiển thị thông báo phù hợp. 

- Có nút chuyển sang Checkout. 

## **25. CẤU TRÚC PROJECT SAU LAB 04** 

Laravel Project │ ├── app/ │ └── Http/ │ └── Controllers/ │ ├── ProductController.php │ └── CartController.php │ ├── resources/ │ └── views/ │ ├── products/ │ └── cart/ │ └── index.blade.php │ └── routes/ └── web.php 

Sau Lab 04, hệ thống đã có luồng Product → Cart. Lab 05 sẽ tiếp tục từ Cart để xây dựng Checkout, Order và thanh toán QR. 

**Phát triển hệ thống thương mại điện tử • LAB 04** 

**© BÙI TÁ HẬU  •  Trang 9** 

