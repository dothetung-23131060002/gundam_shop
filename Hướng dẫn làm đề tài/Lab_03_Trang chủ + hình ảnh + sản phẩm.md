# **LAB 03** 

## **XÂY DỰNG TRANG CHỦ – HÌNH ẢNH VÀ HIỂN THỊ SẢN PHẨM** 

## **ÁP DỤNG CHO ĐỀ TÀI DO SINH VIÊN TỰ CHỌN** 

### **1. MỤC TIÊU** 

- Xây dựng giao diện trang chủ bằng Laravel Blade. 

- Tổ chức Layout, Header, Navigation, Banner, nội dung chính và Footer. 

- Đưa hình ảnh sản phẩm vào website và hiển thị đúng đường dẫn. 

- Hiển thị danh mục và sản phẩm lấy từ Database. 

- Tạo trang danh sách và chi tiết sản phẩm. 

- Biết truyền dữ liệu từ Controller sang Blade View. 

- Áp dụng cấu trúc cho mọi đề tài bán hàng. 

### **2. VỊ TRÍ CỦA LAB 03** 

LAB 01 → Laravel + cấu hình project ↓ LAB 02 → Database + Migration + Model + CRUD ↓ LAB 03 → TRANG CHỦ + HÌNH ẢNH + SẢN PHẨM ↓ LAB 04 → Giỏ hàng ↓ LAB 05 → Checkout + Đơn hàng ↓ LAB 06 → Quản lý đơn hàng ↓ LAB 07 → Đánh giá sản phẩm ↓ LAB 08 → Hoàn thiện website 

Ý nghĩa: Sau Lab 03, website bắt đầu có diện mạo của một hệ thống thương mại điện tử thực tế. 

### **3. ÁP DỤNG CHO ĐỀ TÀI KHÁC** 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 1 

Bán quần áo → áo, quần, giày, balo Bán máy tính → laptop, PC, RAM, SSD Bán sách → sách văn học, kỹ thuật... Bán điện thoại → điện thoại, tablet, phụ kiện Bán đồ ăn → món ăn, đồ uống... 

Logic Laravel giữ nguyên; sinh viên thay dữ liệu, hình ảnh, nội dung và thuộc tính phù hợp với đề tài. 

### **4. CẤU TRÚC THƯ MỤC** 

project-cua-sinh-vien/ ├── app/ │ ├── Http/Controllers/ │ │ ├── HomeController.php ← TẠO │ │ ├── ProductController.php ← TẠO/CẬP NHẬT │ │ └── CategoryController.php ← TẠO NẾU CẦN │ └── Models/ │ ├── Product.php │ └── Category.php ├── public/assets/ │ ├── css/style.css ← TẠO │ └── images/ │ ├── banners/ ← TẠO │ └── products/ ← TẠO ├── resources/views/ │ ├── layouts/app.blade.php ← TẠO/CẬP NHẬT │ ├── home.blade.php ← TẠO │ ├── products/ │ │ ├── index.blade.php ← TẠO │ │ └── show.blade.php ← TẠO │ └── categories/show.blade.php ← TẠO NẾU CẦN └── routes/web.php ← CẬP NHẬT 

### **5. CHUẨN BỊ HÌNH ẢNH** 

public/assets/images/ ├── banners/ │ ├── banner-01.jpg │ └── banner-02.jpg └── products/ ├── product-01.jpg ├── product-02.jpg ├── product-03.jpg └── product-04.jpg 

Tên file nên viết không dấu, không khoảng trắng và thống nhất cách đặt tên. 

### **6. HIỂN THỊ HÌNH ẢNH BẰNG ASSET()** 

<img src="{{ asset( 'assets/images/products/' . $product->image ) }}" 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 2 

alt="{{ $product->name }}"> 

Trong Database, trường image có thể chỉ lưu tên file như product-01.jpg. 

### **7. KIỂM TRA MODEL PRODUCT** 

class Product extends Model { protected $fillable = [ 'category_id', 'name', 'price', 'image', 'description', 'quantity', ]; public function category() { return $this->belongsTo(Category::class); } } 

Các trường phải khớp với Database thực tế của project. **8. QUAN HỆ CATEGORY – PRODUCT** public function products() { return $this->hasMany(Product::class); } 

### **9. TẠO HOMECONTROLLER** 

php artisan make:controller HomeController 

class HomeController extends Controller { public function index() { $categories = Category::latest()->get(); $products = Product::latest() ->take(8) ->get(); return view( 'home', compact('categories', 'products') ); } } 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 3 

Controller lấy dữ liệu từ Database và truyền sang home.blade.php. Có thể thay đổi điều kiện để lấy sản phẩm mới, nổi bật hoặc còn hàng. 

### **10. ROUTE TRANG CHỦ** 

use App\Http\Controllers\HomeController; Route::get( '/', [HomeController::class, 'index'] )->name('home'); 

### **11. LAYOUT CHUNG** 

Tạo resources/views/layouts/app.blade.php: 

<!DOCTYPE html> <html lang="vi"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>@yield('title', 'Website bán hàng')</title> <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}"> </head> <body> <header> <h1>Tên website</h1> <nav> <a href="{{ route('home') }}">Trang chủ</a> <a href="{{ route('products.index') }}">Sản phẩm</a> </nav> </header> <main> @yield('content') </main> <footer> <p>© 2026 Website của sinh viên</p> </footer> </body> </html> 

### **12. TẠO TRANG CHỦ** 

Tạo resources/views/home.blade.php: 

@extends('layouts.app') 

@section('title', 'Trang chủ') @section('content') <section class="hero"> 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 4 

<h2>Chào mừng đến với website</h2> <p>Khám phá các sản phẩm nổi bật</p> <img src="{{ asset( 'assets/images/banners/banner-01.jpg' ) }}" alt="Banner"> </section> <section> <h2>Danh mục sản phẩm</h2> <div class="category-list"> @foreach($categories as $category) <a href="{{ route( 'categories.show', $category ) }}"> {{ $category->name }} </a> @endforeach </div> </section> <section> <h2>Sản phẩm mới</h2> <div class="product-grid"> @foreach($products as $product) <article class="product-card"> <img src="{{ asset( 'assets/images/products/' . $product->image ) }}" alt="{{ $product->name }}"> <h3>{{ $product->name }}</h3> <p> {{ number_format($product->price) }} VNĐ </p> <a href="{{ route( 'products.show', $product ) }}"> Xem chi tiết </a> </article> @endforeach </div> </section> @endsection 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 5 

### **13. CSS TRANG CHỦ** 

Tạo public/assets/css/style.css: 

* { box-sizing: border-box; } 

body { margin: 0; font-family: Arial, sans-serif; background: #f7f7f7; color: #333; } header { background: #7048C8; color: white; padding: 20px 8%; } 

nav a { color: white; text-decoration: none; margin-right: 20px; } main { width: 84%; margin: 30px auto; } .hero img { width: 100%; max-height: 380px; object-fit: cover; border-radius: 12px; } .product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; } .product-card { background: white; padding: 15px; border-radius: 12px; } .product-card img { width: 100%; height: 220px; object-fit: cover; } footer { margin-top: 40px; padding: 25px; 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 6 

text-align: center; background: #263044; color: white; } 

### **14. TẠO PRODUCTCONTROLLER** 

php artisan make:controller ProductController 

class ProductController extends Controller { public function index() { $products = Product::latest() ->paginate(12); return view( 'products.index', compact('products') ); } public function show(Product $product) { return view( 'products.show', compact('product') ); } } 

#### 

### **15. ROUTE SẢN PHẨM** 

use App\Http\Controllers\ProductController; 

Route::get( '/products', [ProductController::class, 'index'] )->name('products.index'); 

Route::get( '/products/{product}', [ProductController::class, 'show'] )->name('products.show'); 

### **16. DANH SÁCH SẢN PHẨM** 

Tạo resources/views/products/index.blade.php: 

@extends('layouts.app') 

@section('title', 'Sản phẩm') @section('content') <h2>Tất cả sản phẩm</h2> 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 7 

<div class="product-grid"> @forelse($products as $product) <article class="product-card"> <img src="{{ asset( 'assets/images/products/' . $product->image ) }}" alt="{{ $product->name }}"> <h3>{{ $product->name }}</h3> <p> {{ number_format($product->price) }} VNĐ </p> <a href="{{ route( 'products.show', $product ) }}"> Xem chi tiết </a> </article> @empty <p>Chưa có sản phẩm.</p> @endforelse </div> {{ $products->links() }} @endsection **17. CHI TIẾT SẢN PHẨM** Tạo resources/views/products/show.blade.php: 

@extends('layouts.app') @section('title', $product->name) @section('content') <div class="product-detail"> <div> <img src="{{ asset( 'assets/images/products/' . $product->image ) }}" alt="{{ $product->name }}"> </div> <div> <h1>{{ $product->name }}</h1> <h2> {{ number_format($product->price) }} VNĐ 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 8 

</h2> <p>{{ $product->description }}</p> <p>Số lượng: {{ $product->quantity }}</p> <a href="#"> Thêm vào giỏ hàng </a> </div> </div> @endsection 

Nút “Thêm vào giỏ hàng” ở đây mới là giao diện. Logic giỏ hàng sẽ thực hiện ở Lab 04. 

### **18. SẢN PHẨM THEO DANH MỤC** 

php artisan make:controller CategoryController 

public function show(Category $category) { $products = $category ->products() ->latest() ->paginate(12); return view( 'categories.show', compact('category', 'products') ); } 

Route::get( '/categories/{category}', [CategoryController::class, 'show'] )->name('categories.show'); 

### **19. ẢNH MẶC ĐỊNH KHI CHƯA CÓ ẢNH** 

public/assets/images/no-image.jpg 

<img src="{{ $product->image ? asset( 'assets/images/products/' . $product->image ) : asset('assets/images/no-image.jpg') }}" alt="{{ $product->name }}"> 

### **20. KIỂM TRA TRANG CHỦ VÀ HÌNH ẢNH** 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 9 

- Truy cập / và kiểm tra trang chủ. 

- Kiểm tra Header, Navigation và Footer. 

- Kiểm tra Banner. 

- Kiểm tra danh mục. 

- Kiểm tra sản phẩm. 

- Kiểm tra ảnh từng sản phẩm. 

- Bấm vào sản phẩm để mở trang chi tiết. 

- Kiểm tra giá, mô tả và số lượng. 

- Kiểm tra liên kết giữa trang chủ, danh mục và sản phẩm. 

### **21. XỬ LÝ KHI ẢNH KHÔNG HIỂN THỊ** 

- File ảnh có nằm trong public/assets/images/products không? 

- Tên file trong Database có đúng không? 

#### 

- Tên file có dấu hoặc khoảng trắng không? 

- Blade có dùng asset() đúng đường dẫn không? 

- Có viết sai products/images hoặc phần mở rộng jpg/png/webp không? 

Database: laptop-dell.jpg 

File: public/assets/images/products/laptop-dell.jpg 

Blade: asset( 'assets/images/products/' . $product->image ) 

### **22. ÁP DỤNG CHO ĐỀ TÀI KHÁC** 

Quần áo: Banner bộ sưu tập; sản phẩm là áo, quần, giày. 

Máy tính: Banner laptop; sản phẩm là laptop, PC, RAM, SSD. 

Sách: Banner sách mới; sản phẩm là sách và ảnh bìa. 

Điện thoại: Banner sản phẩm mới; sản phẩm là điện thoại/tablet/phụ kiện. 

Đồ ăn: Banner món nổi bật; sản phẩm là món ăn, đồ uống. 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 10 

Luồng Controller → Model → View → Image giữ nguyên; sinh viên thay dữ liệu và giao diện theo chủ đề. 

### **23. MỞ RỘNG TRANG CHỦ** 

- Khu vực sản phẩm mới. 

- Khu vực sản phẩm bán chạy. 

- Khu vực sản phẩm nổi bật. 

- Banner nhiều slide. 

- Danh mục có hình ảnh. 

- Nút xem tất cả sản phẩm. 

- Nút xem sản phẩm theo danh mục. 

- Hiển thị giá khuyến mãi nếu Database có trường tương ứng. 

### **24. LỖI THƯỜNG GẶP** 

- View [home] not found → kiểm tra resources/views/home.blade.php. 

- Undefined variable $products → kiểm tra HomeController. 

- Route [products.show] not defined → kiểm tra routes/web.php. 

- Ảnh lỗi → kiểm tra public/assets/images và tên file Database. 

- SQLSTATE unknown column → kiểm tra tên cột Product. 

- Call to undefined relationship → kiểm tra Product/Category. 

- Chi tiết không mở → kiểm tra Route Model Binding. 

### **25. CẤU TRÚC PROJECT SAU LAB 03** 

project-cua-sinh-vien/ ├── app/ │ ├── Http/Controllers/ │ │ ├── HomeController.php │ │ ├── ProductController.php │ │ └── CategoryController.php │ └── Models/ │ ├── Product.php │ └── Category.php ├── public/assets/ │ ├── css/style.css │ └── images/ │ ├── banners/ │ └── products/ ├── resources/views/ 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 11 

│ ├── layouts/app.blade.php │ ├── home.blade.php 

│ ├── products/ │ │ ├── index.blade.php │ │ └── show.blade.php │ └── categories/show.blade.php └── routes/web.php 

### **26. HOÀN THÀNH LAB 03** 

- Có trang chủ hoạt động. 

- Có Header, Navigation và Footer. 

- Có Banner/hình ảnh trên trang chủ. 

- Có danh mục sản phẩm. 

- Có danh sách sản phẩm lấy từ Database. 

- Có hình ảnh sản phẩm. 

- Có trang chi tiết sản phẩm. 

- Có điều hướng giữa trang chủ, danh mục và sản phẩm. 

- Hình ảnh hiển thị đúng đường dẫn. 

- Giao diện có thể áp dụng cho đề tài riêng. 

Chuẩn đầu ra: Sau Lab 03, sinh viên đã có một bộ khung giao diện thương mại điện tử: người dùng có thể vào trang chủ, xem banner, danh mục, hình ảnh và chi tiết sản phẩm. Lab 04 sẽ đưa sản phẩm từ giao diện này vào giỏ hàng. 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 03 

Trang 12 

