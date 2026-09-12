# **LAB 07** 

**GIAO DIỆN WEBSITE – TÌM KIẾM – LỌC – PHÂN TRANG – ĐÁNH GIÁ** 

### **XÂY DỰNG BỐ CỤC VÀ HOÀN THIỆN TRẢI NGHIỆM MUA SẮM** 

## **1. MỤC TIÊU** 

- Xây dựng Layout dùng chung cho toàn bộ website. 

- Chia giao diện thành Header – Content – Footer. 

- Thiết kế giao diện sản phẩm bằng Blade và CSS. 

- Tạo Product Card có hình ảnh, tên, giá, số lượng và nút thêm vào giỏ. • Xây dựng tìm kiếm sản phẩm. • Lọc theo danh mục và khoảng giá. • Sắp xếp sản phẩm. • Phân trang dữ liệu sản phẩm bằng Laravel Pagination. 

- Xây dựng đánh giá sản phẩm gắn với tài khoản người dùng. 

- Thiết kế giao diện có thể tái sử dụng cho các đề tài TMĐT khác. 

Lab 07 không chỉ tập trung vào một website cụ thể. Sinh viên học cách xây dựng cấu trúc giao diện và chức năng có thể tái sử dụng cho website bán quần áo, máy tính, sách, mỹ phẩm, đồ gia dụng... 

## **2. VỊ TRÍ CỦA LAB 07** 

LAB 06 Tài khoản + Đăng nhập + Phân quyền + Admin ↓ LAB 07 Layout + CSS + Sản phẩm + Tìm kiếm + Lọc + Sắp xếp + Phân trang + Đánh giá ↓ LAB 08 Tích hợp + Hoàn thiện website TMĐT 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 1** 

## **3. LƯU Ý VỀ THUẬT NGỮ “PHÂN TRANG”** 

Trong Lab này có hai khái niệm khác nhau: 

- Phân chia bố cục trang: Header – Content – Footer. 

- Phân trang dữ liệu: chia danh sách sản phẩm thành nhiều trang bằng paginate(). 

Hai khái niệm này không giống nhau. Sinh viên cần phân biệt để tránh nhầm khi lập trình. 

## **4. BỐ CỤC 3 KHU VỰC CỦA WEBSITE** 

┌─────────────────────────────────────────────┐ │ HEADER │ │ Logo | Trang chủ | Sản phẩm | Giỏ hàng │ │ Đăng nhập | Đăng ký | Tài khoản | Admin │ ├─────────────────────────────────────────────┤ │ CONTENT │ │ │ │ Banner / giới thiệu │ │ Tìm kiếm / Lọc / Sắp xếp │ │ │ │ [Product] [Product] [Product] │ │ [Product] [Product] [Product] │ │ │ │ ← 1 2 3 4 → │ ├─────────────────────────────────────────────┤ │ FOOTER │ │ Giới thiệu | Hỗ trợ | Thanh toán | Liên hệ │ │ QR | COD | Email | Điện thoại | Địa chỉ │ └─────────────────────────────────────────────┘ 

## **5. CẤU TRÚC THƯ MỤC / FILE** 

project/ ├── public/ │ ├── css/ │ │ └── style.css │ └── images/ │ └── products/ │ ├── resources/ │ └── views/ │ ├── layouts/ │ │ └── app.blade.php │ ├── components/ │ │ ├── header.blade.php │ │ └── footer.blade.php │ ├── products/ │ │ ├── index.blade.php │ │ └── detail.blade.php │ └── reviews/ │ └── ... │ └── routes/ 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 2** 

└── web.php 

## **6. TẠO CSS DÙNG CHUNG** 

Tạo thư mục public/css nếu project chưa có, sau đó tạo file style.css. 

public/css/style.css 

:root { --primary-color: #6c4ab6; --secondary-color: #e58b36; --text-color: #263044; --background-color: #f7f8fc; --white: #ffffff; --border-color: #e5e5e5; } * { box-sizing: border-box; } body { margin: 0; font-family: Arial, sans-serif; color: var(--text-color); background: var(--background-color); } .container { width: 90%; max-width: 1200px; margin: auto; } a { text-decoration: none; } button, .btn { cursor: pointer; } 

CSS Variables giúp sinh viên thay đổi theme nhanh. Ví dụ website thời trang có thể đổi màu chủ đạo mà không phải sửa từng selector. 

## **7. TẠO LAYOUT CHUNG** 

Tạo file resources/views/layouts/app.blade.php. 

<!DOCTYPE html> <html lang="vi"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 3** 

<title>@yield('title', 'Website TMĐT')</title> <link rel="stylesheet" href="{{ asset('css/style.css') }}"> </head> <body> @include('components.header') 

<main> @yield('content') </main> @include('components.footer') 

</body> </html> 

## **8. COMPONENT HEADER** 

Tạo file resources/views/components/header.blade.php. 

<header class="site-header"> <div class="container header-inner"> <a href="{{ url('/') }}" class="logo"> SHOP ONLINE </a> <nav class="main-nav"> <a href="{{ url('/') }}">Trang chủ</a> <a href="{{ route('products.index') }}"> Sản phẩm </a> <a href="{{ route('cart.index') }}"> Giỏ hàng </a> @auth <span> Xin chào {{ auth()->user()->name }} </span> @if(auth()->user()->role === 'admin') <a href="{{ route('admin.dashboard') }}"> Admin </a> @endif @else <a href="{{ route('login.form') }}"> Đăng nhập </a> 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 4** 

<a href="{{ route('register.form') }}"> Đăng ký </a> @endauth </nav> </div> </header> 

## **9. CSS CHO HEADER** 

.site-header { background: var(--white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; } .header-inner { min-height: 70px; display: flex; align-items: center; justify-content: space-between; gap: 20px; } .logo { color: var(--primary-color); font-size: 24px; font-weight: bold; } .main-nav { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; } .main-nav a { color: var(--text-color); font-weight: 600; } .main-nav a:hover { color: var(--primary-color); } 

## **10. COMPONENT FOOTER** 

Tạo file resources/views/components/footer.blade.php. 

<footer class="site-footer"> 

<div class="container footer-grid"> 

<div class="footer-column"> 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 5** 

<h3>Giới thiệu</h3> <p> Website cung cấp sản phẩm chất lượng và hỗ trợ khách hàng trực tuyến. </p> </div> <div class="footer-column"> <h3>Hỗ trợ</h3> <a href="#">Chính sách mua hàng</a> <a href="#">Chính sách đổi trả</a> <a href="#">Chính sách giao hàng</a> </div> <div class="footer-column"> <h3>Thanh toán</h3> <p>Thanh toán khi nhận hàng (COD)</p> <p>Thanh toán bằng QR</p> <img src="{{ asset('images/qr-payment.png') }}" alt="QR thanh toán" class="footer-qr" > </div> <div class="footer-column"> <h3>Liên hệ</h3> <p>Điện thoại: 0123 456 789</p> <p>Email: shop@example.com</p> <p>Địa chỉ: Hà Nội</p> </div> </div> <div class="footer-bottom"> © BÙI TÁ HẬU – Website TMĐT </div> </footer> 

## **11. CSS CHO FOOTER** 

.site-footer { margin-top: 50px; background: var(--text-color); color: var(--white); } .footer-grid { padding: 45px 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; } .footer-column { display: flex; 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 6** 

flex-direction: column; gap: 8px; } .footer-column a { color: var(--white); } .footer-column a:hover { color: var(--secondary-color); } .footer-qr { width: 100px; height: 100px; } .footer-bottom { text-align: center; padding: 15px; border-top: 1px solid rgba(255,255,255,.2); } 

## **12. RESPONSIVE FOOTER** 

##### 

@media (max-width: 768px) { 

.header-inner { flex-direction: column; padding: 15px 0; } .main-nav { justify-content: center; } .footer-grid { grid-template-columns: 1fr 1fr; } } @media (max-width: 480px) { .footer-grid { grid-template-columns: 1fr; } } 

## **13. TRANG DANH SÁCH SẢN PHẨM** 

Tạo hoặc cập nhật resources/views/products/index.blade.php. 

@extends('layouts.app') @section('title', 'Sản phẩm') @section('content') 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 7** 

<div class="container"> <section class="page-heading"> <h1>Sản phẩm</h1> <p>Khám phá các sản phẩm của chúng tôi.</p> </section> 

{{-- Tìm kiếm / lọc --}} <div class="product-grid"> 

@foreach($products as $product) 

<div class="product-card"> 

<img src="{{ asset( 'images/products/' . $product->image ) }}" alt="{{ $product->name }}" class="product-image" > <div class="product-info"> <h3> {{ $product->name }} </h3> <p class="product-price"> {{ number_format($product->price) }} VNĐ </p> <a href="{{ route( 'products.show', $product ) }}" class="btn btn-detail" > Xem chi tiết </a> <form action="{{ route('cart.add') }}" method="POST" > @csrf <input type="number" name="quantity" value="1" min="1" > <input 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 8** 

type="hidden" name="product_id" value="{{ $product->id }}" > <button type="submit" class="btn btn-cart" > Thêm vào giỏ </button> </form> </div> </div> @endforeach </div> {{ $products->links() }} </div> @endsection **14. CSS PRODUCT CARD** .product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; } 

.product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; } .product-card { background: var(--white); border-radius: 14px; overflow: hidden; border: 1px solid var(--border-color); transition: .25s; } .product-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,.10); } .product-image { width: 100%; height: 230px; object-fit: cover; } .product-info { padding: 18px; 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 9** 

} 

.product-price { color: var(--secondary-color); font-size: 18px; font-weight: bold; } .btn { display: inline-block; border: none; padding: 10px 16px; border-radius: 8px; margin-top: 8px; } .btn-detail { color: var(--primary-color); border: 1px solid var(--primary-color); } .btn-cart { color: var(--white); background: var(--primary-color); } .btn-cart:hover { background: var(--secondary-color); } **15. RESPONSIVE PRODUCT GRID** @media (max-width: 900px) { .product-grid { grid-template-columns: repeat(2, 1fr); } } @media (max-width: 600px) { .product-grid { grid-template-columns: 1fr; } } 

## **16. TÌM KIẾM SẢN PHẨM** 

Sử dụng GET để từ khóa xuất hiện trên URL và người dùng có thể chia sẻ đường dẫn kết quả tìm kiếm. 

resources/views/products/index.blade.php 

<form method="GET" action="{{ route('products.index') }}" class="search-form"> <input 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 10** 

type="text" name="keyword" value="{{ request('keyword') }}" placeholder="Tìm kiếm sản phẩm..." > <button type="submit"> Tìm kiếm </button> </form> 

## **17. PRODUCT CONTROLLER – TÌM KIẾM** 

#### Mở app/Http/Controllers/ProductController.php. 

public function index(Request $request) { $query = Product::query(); if ($request->filled('keyword')) { $keyword = $request->keyword; $query->where(function ($q) use ($keyword) { $q->where('name', 'like', "%{$keyword}%") ->orWhere('description', 'like', "%{$keyword}%"); }); } $products = $query ->latest() ->paginate(9) ->withQueryString(); return view( 'products.index', compact('products') ); } 

withQueryString() giúp giữ lại keyword và các tham số lọc khi người dùng chuyển sang trang pagination tiếp theo. 

## **18. LỌC THEO DANH MỤC** 

<form method="GET" action="{{ route('products.index') }}"> <select name="category_id"> 

<option value=""> Tất cả danh mục </option> @foreach($categories as $category) 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 11** 

<option value="{{ $category->id }}" @selected( request('category_id') == $category->id ) > {{ $category->name }} </option> @endforeach </select> <button type="submit"> Lọc </button> </form> 

Tên Model/Relationship của Category có thể thay đổi tùy project. Sinh viên cần thay theo database thực tế của đề tài. 

## **19. LỌC THEO GIÁ** 

<div class="price-filter"> <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Giá từ" > <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Giá đến" > </div> if ($request->filled('min_price')) { $query->where( 'price', '>=', $request->min_price ); } if ($request->filled('max_price')) { $query->where( 'price', '<=', $request->max_price ); 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 12** 

} 

## **20. SẮP XẾP SẢN PHẨM** 

<select name="sort"> <option value=""> Mới nhất </option> <option value="price_asc"> Giá thấp → cao </option> <option value="price_desc"> Giá cao → thấp </option> <option value="name_asc"> Tên A → Z </option> </select> switch ($request->sort) { case 'price_asc': $query->orderBy('price', 'asc'); break; case 'price_desc': $query->orderBy('price', 'desc'); break; case 'name_asc': $query->orderBy('name', 'asc'); break; default: $query->latest(); } 

## **21. GỘP TÌM KIẾM + LỌC + SẮP XẾP + PHÂN TRANG** 

public function index(Request $request) { $query = Product::query(); if ($request->filled('keyword')) { $keyword = $request->keyword; $query->where(function ($q) use ($keyword) { $q->where('name', 'like', "%{$keyword}%") ->orWhere('description', 'like', "%{$keyword}%"); }); } 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 13** 

if ($request->filled('category_id')) { $query->where( 'category_id', $request->category_id ); } if ($request->filled('min_price')) { $query->where( 'price', '>=', $request->min_price ); } if ($request->filled('max_price')) { $query->where( 'price', '<=', $request->max_price ); } switch ($request->sort) { case 'price_asc': $query->orderBy('price', 'asc'); break; case 'price_desc': $query->orderBy('price', 'desc'); break; case 'name_asc': $query->orderBy('name', 'asc'); break; default: $query->latest(); } $products = $query ->paginate(9) ->withQueryString(); $categories = Category::orderBy('name') ->get(); return view( 'products.index', compact('products', 'categories') ); } 

## **22. PHÂN TRANG DỮ LIỆU** 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 14** 

Pagination là phần chia danh sách sản phẩm thành nhiều trang. Đây là phân trang dữ liệu, không phải chia Layout. 

$products = Product::latest() ->paginate(9); {{ $products->links() }} 

Số 9 có thể thay đổi thành 6, 8, 12... tùy thiết kế. 

## **23. TÙY BIẾN GIAO DIỆN PAGINATION** 

Laravel có thể dùng Bootstrap hoặc Tailwind tùy cấu hình project. Nếu sinh viên dùng CSS riêng, có thể tạo style cho các phần pagination theo HTML mà Laravel render ra. 

.pagination-wrapper { margin: 30px 0; display: flex; justify-content: center; } 

.pagination-wrapper a, .pagination-wrapper span { margin: 0 4px; padding: 8px 12px; border-radius: 6px; } **24. ĐÁNH GIÁ SẢN PHẨM** Chức năng đánh giá được liên kết với User của Lab 06. Một đánh giá nên có user_id, product_id, rating và nội dung. 

reviews -------------------------------id user_id product_id rating comment created_at updated_at 

## **25. TẠO MODEL VÀ MIGRATION REVIEW** 

php artisan make:model Review -m 

Schema::create('reviews', function (Blueprint $table) { 

$table->id(); $table->foreignId('user_id') ->constrained('users') ->cascadeOnDelete(); 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 15** 

$table->foreignId('product_id') ->constrained('products') ->cascadeOnDelete(); 

$table->unsignedTinyInteger('rating'); 

$table->text('comment')->nullable(); $table->timestamps(); }); 

php artisan migrate 

## **26. RELATIONSHIP REVIEW** 

// User.php public function reviews() { return $this->hasMany(Review::class); } // Product.php public function reviews() { return $this->hasMany(Review::class); } // Review.php public function user() { return $this->belongsTo(User::class); } public function product() { return $this->belongsTo(Product::class); } 

## **27. FORM ĐÁNH GIÁ** 

Sinh viên tạo file resources/views/products/detail.blade.php hoặc đặt component đánh giá trong trang chi tiết sản phẩm. 

@auth <form action="{{ route( 'reviews.store', $product ) }}" method="POST" > 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 16** 

@csrf 

<label>Đánh giá</label> 

<select name="rating"> <option value="5">5 sao</option> <option value="4">4 sao</option> <option value="3">3 sao</option> <option value="2">2 sao</option> <option value="1">1 sao</option> </select> 

<textarea name="comment" placeholder="Nhận xét của bạn" ></textarea> <button type="submit"> Gửi đánh giá </button> 

</form> @else <p> Vui lòng đăng nhập để đánh giá sản phẩm. </p> @endauth **28. ROUTE ĐÁNH GIÁ** Route::middleware('auth')->group(function () { 

Route::post( '/products/{product}/reviews', [ReviewController::class, 'store'] )->name('reviews.store'); 

}); 

## **29. REVIEW CONTROLLER** 

php artisan make:controller ReviewController 

public function store( Request $request, Product $product ) { $validated = $request->validate([ 'rating' => 'required|integer|min:1|max:5', 'comment' => 'nullable|string|max:1000', ]); Review::create([ 'user_id' => auth()->id(), 'product_id' => $product->id, 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 17** 

'rating' => $validated['rating'], 'comment' => $validated['comment'] ?? null, ]); return back()->with( 'success', 'Đánh giá đã được gửi.' ); } 

## **30. HIỂN THỊ ĐÁNH GIÁ** 

@foreach($product->reviews as $review) 

<div class="review"> <strong> {{ $review->user->name }} </strong> <div> {{ $review->rating }}/5 sao </div> <p> {{ $review->comment }} </p> </div> @endforeach 

## **31. KIỂM SOÁT ĐÁNH GIÁ NÂNG CAO** 

- Có thể yêu cầu người dùng đã đăng nhập mới được đánh giá. 

- Có thể giới hạn mỗi user chỉ đánh giá một lần cho một sản phẩm. 

- Có thể chỉ cho phép người đã mua sản phẩm đánh giá. 

- Có thể thêm trạng thái pending/approved để Admin duyệt. 

- Có thể thêm thời gian đánh giá và chỉnh sửa đánh giá. 

Nếu yêu cầu “đã mua mới được đánh giá”, cần kiểm tra quan hệ User → Order → OrderDetail → Product trước khi tạo Review. 

## **32. TRANG CHI TIẾT SẢN PHẨM** 

resources/views/products/detail.blade.php 

@extends('layouts.app') @section('title', $product->name) @section('content') 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 18** 

<div class="container product-detail"> <img src="{{ asset( 'images/products/' . $product->image ) }}" alt="{{ $product->name }}" > <div> <h1>{{ $product->name }}</h1> <p class="product-price"> {{ number_format($product->price) }} VNĐ </p> <p> {{ $product->description }} </p> <form action="{{ route('cart.add') }}" method="POST" > @csrf <input type="hidden" name="product_id" value="{{ $product->id }}" > <input type="number" name="quantity" value="1" min="1" > <button class="btn btn-cart"> Thêm vào giỏ </button> </form> </div> </div> @endsection 

## **33. TRANG CHỦ VÀ GIAO DIỆN WEBSITE** 

Sau khi có Layout, trang chủ có thể dùng lại Header, Footer và Product Card. Sinh viên không cần viết lại hai phần này cho từng trang. 

resources/views/home.blade.php 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 19** 

@extends('layouts.app') 

@section('title', 'Trang chủ') 

@section('content') 

<section class="hero"> <div class="container"> <h1>Chào mừng đến với cửa hàng</h1> <p> Khám phá sản phẩm nổi bật của chúng tôi. </p> 

<a href="{{ route('products.index') }}" class="btn btn-cart" > Xem sản phẩm </a> </div> </section> 

@endsection 

## **34. CSS CHO HERO** 

##### 

.hero { padding: 80px 0; text-align: center; background: linear-gradient( 135deg, #eee6ff, #fff4e8 ); } .hero h1 { font-size: 42px; margin-bottom: 15px; } .hero p { font-size: 18px; margin-bottom: 25px; } 

## **35. TƯ DUY THAY THEME THEO ĐỀ TÀI** 

Fruit Shop ↓ Đổi logo + màu + ảnh + nội dung Fashion Shop ↓ Đổi logo + màu + ảnh + nội dung Computer Shop 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 20** 

↓ Đổi logo + màu + ảnh + nội dung Book Store ↓ Đổi logo + màu + ảnh + nội dung 

Cấu trúc Laravel, Layout, Controller, Route và các chức năng dùng chung có thể giữ lại. Sinh viên thay đổi dữ liệu, hình ảnh, màu sắc và nghiệp vụ riêng. 

## **36. CSS THEME CHO ĐỀ TÀI RIÊNG** 

:root { --primary-color: #6c4ab6; --secondary-color: #e58b36; } /* * Website thời trang: * --primary-color: màu thương hiệu thời trang * * Website máy tính: * --primary-color: màu nhận diện cửa hàng * * Website sách: * --primary-color: màu của nhà sách */ 

##### 

## **37. RESPONSIVE WEBSITE** 

Website phải có khả năng hiển thị hợp lý trên máy tính, máy tính bảng và điện thoại. 

@media (max-width: 900px) { .product-grid { grid-template-columns: repeat(2, 1fr); } } @media (max-width: 600px) { .container { width: 94%; } .product-grid { grid-template-columns: 1fr; } .hero h1 { font-size: 30px; } } 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 21** 

## **38. CÁC LỖI THƯỜNG GẶP** 

- Nhầm Layout Header–Content–Footer với Pagination dữ liệu. 

- Đặt CSS sai thư mục nên asset không tải. 

- Quên {{ asset('css/style.css') }}. 

- Quên @extends('layouts.app'). 

- Quên @section('content'). 

- Quên withQueryString() khiến bộ lọc mất khi chuyển trang. 

- Không validate rating từ 1 đến 5. 

- Cho người chưa đăng nhập gửi review. 

- Không kiểm tra quyền với dữ liệu người dùng. 

- Hard-code giao diện khiến đổi đề tài phải sửa quá nhiều file. 

## **39. KIỂM TRA KẾT QUẢ** 

##### 

- Header hiển thị đúng trên các trang. 

- Footer hiển thị đúng trên các trang. 

- CSS được tải thành công. 

- Product Card hiển thị hình ảnh, tên, giá và nút mua. 

- Có thể tìm kiếm theo từ khóa. 

- Có thể lọc sản phẩm. 

- Có thể sắp xếp. 

- Có pagination. 

- Pagination vẫn giữ tham số tìm kiếm/lọc. 

- Người dùng đăng nhập có thể gửi đánh giá. 

- Đánh giá hiển thị đúng người dùng và sản phẩm. 

- Giao diện hoạt động trên màn hình nhỏ. 

- Theme có thể thay đổi bằng CSS Variables. 

## **40. CẤU TRÚC PROJECT SAU LAB 07** 

Laravel Project │ 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 22** 

├── app/ │ ├── Http/Controllers/ │ │ ├── ProductController.php │ │ ├── CartController.php │ │ ├── CheckoutController.php │ │ ├── ReviewController.php │ │ └── Admin/ │ │ ├── DashboardController.php │ │ ├── ProductController.php │ │ └── OrderController.php │ │ │ └── Models/ │ ├── User.php │ ├── Product.php │ ├── Order.php │ ├── OrderDetail.php │ └── Review.php │ ├── public/ │ ├── css/ │ │ └── style.css │ └── images/ │ └── products/ │ ├── resources/views/ │ ├── layouts/ │ │ └── app.blade.php │ ├── components/ │ │ ├── header.blade.php │ │ └── footer.blade.php │ ├── products/ │ │ ├── index.blade.php │ │ └── detail.blade.php │ ├── reviews/ │ ├── auth/ │ └── admin/ │ └── routes/ └── web.php 

## **41. KẾT NỐI SANG LAB 08** 

Sau Lab 07, website đã có nền tảng giao diện và trải nghiệm mua sắm tương đối hoàn chỉnh: Layout, CSS, sản phẩm, tìm kiếm, lọc, sắp xếp, pagination, đánh giá, tài khoản và Admin. 

Lab 08 sẽ tập trung vào tích hợp, kiểm tra toàn bộ luồng, hoàn thiện các chức năng còn thiếu và biến project mẫu thành website TMĐT hoàn chỉnh theo đề tài mà sinh viên lựa chọn. 

**Phát triển hệ thống thương mại điện tử • LAB 07** 

**© BÙI TÁ HẬU  •  Trang 23** 

