# **LAB 02** 

## **DATABASE – MODEL – CRUD VÀ THIẾT KẾ CSDL THEO ĐỀ TÀI** 

**ÁP DỤNG CHO WEBSITE TMĐT BẤT KỲ** 

## **2. CẤU TRÚC NỘI DUNG LAB** 

LAB 01 Laravel + cấu trúc Project + MVC + Route + Blade ↓ LAB 02 Database + Migration + Model + Relationship + CRUD ↓ LAB 03 Trang chủ + Hình ảnh + Sản phẩm ↓ LAB 04 Giỏ hàng ↓ LAB 05 Checkout + Đơn hàng + Thanh toán ↓ LAB 06 → LAB 08 Quản trị + Mở rộng + Dự án theo đề tài riêng 

Trong Lab 02, sinh viên vừa làm theo project mẫu Fruit Variety Shop, vừa bắt đầu học cách tự thiết kế Database cho đề tài của mình. 

## **3. DATABASE TRONG LARAVEL** 

Laravel sử dụng Migration để mô tả cấu trúc bảng bằng code. Việc này giúp sinh viên tạo, thay đổi và đồng bộ cấu trúc Database một cách có kiểm soát. 

php artisan migrate php artisan migrate:rollback php artisan migrate:fresh 

## **4. TẠO MIGRATION** 

php artisan make:migration create_categories_table 

Schema::create('categories', function (Blueprint $table) { $table->id(); $table->string('name'); 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 1** 

$table->text('description')->nullable(); $table->timestamps(); }); 

Sau khi tạo Migration, chạy php artisan migrate để tạo bảng trong Database. 

## **5. TẠO BẢNG PRODUCTS** 

Schema::create('products', function (Blueprint $table) { $table->id(); $table->foreignId('category_id') ->constrained('categories') ->cascadeOnDelete(); $table->string('name'); $table->decimal('price', 12, 2); $table->integer('quantity')->default(0); $table->string('image')->nullable(); $table->text('description')->nullable(); $table->timestamps(); }); 

category_id là khóa ngoại liên kết Product với Category. **6. QUAN HỆ CATEGORY – PRODUCT** categories │ │ 1 - N ↓ products 

Một Category có nhiều Product. Mỗi Product thuộc về một Category. 

// Category.php public function products() { return $this->hasMany(Product::class); } // Product.php public function category() { return $this->belongsTo(Category::class); } 

## **7. TẠO MODEL** 

php artisan make:model Category php artisan make:model Product 

class Product extends Model { protected $fillable = [ 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 2** 

'category_id', 'name', 'price', 'quantity', 'image', 'description', ]; public function category() { return $this->belongsTo(Category::class); } } 

## **8. CRUD – TƯ DUY CƠ BẢN** 

Create → Tạo dữ liệu Read → Đọc / hiển thị dữ liệu Update → Cập nhật dữ liệu Delete → Xóa dữ liệu 

CRUD là nền tảng để xây dựng trang quản trị sản phẩm, danh mục, đơn hàng và nhiều đối tượng khác trong hệ thống TMĐT. **9. TẠO CONTROLLER CRUD** php artisan make:controller ProductController --resource public function index() { $products = Product::latest()->paginate(12); return view('products.index', compact('products')); } public function create() { $categories = Category::all(); return view('products.create', compact('categories')); } 

## **10. ROUTE RESOURCE** 

use App\Http\Controllers\ProductController; 

Route::resource( 'products', ProductController::class ); 

Resource Route tự tạo các route phổ biến cho CRUD: index, create, store, show, edit, update và destroy. 

## **11. CREATE – THÊM SẢN PHẨM** 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 3** 

public function store(Request $request) { $request->validate([ 'category_id' => 'required', 'name' => 'required|max:255', 'price' => 'required|numeric|min:0', 'quantity' => 'required|integer|min:0', ]); Product::create($request->all()); return redirect() ->route('products.index'); } 

### Validation giúp hạn chế dữ liệu không hợp lệ được đưa vào Database. 

## **12. READ – HIỂN THỊ SẢN PHẨM** 

@foreach($products as $product) <h3>{{ $product->name }}</h3> <p>{{ number_format($product->price) }} VNĐ</p> <p>Danh mục: {{ $product->category->name }}</p> @endforeach **13. UPDATE – CẬP NHẬT** public function update( Request $request, Product $product ) { $request->validate([ 'name' => 'required|max:255', 'price' => 'required|numeric|min:0', 'quantity' => 'required|integer|min:0', ]); 

$product->update($request->all()); return redirect() ->route('products.index'); } 

## **14. DELETE – XÓA** 

public function destroy(Product $product) { $product->delete(); return redirect() ->route('products.index'); } 

## **15. PHÂN TÍCH DATABASE TRƯỚC KHI CODE** 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 4** 

Trước khi viết Migration, sinh viên phân tích đề tài theo trình tự: 

Đề tài ↓ Xác định đối tượng ↓ Xác định thuộc tính ↓ Xác định quan hệ ↓ Xác định bảng ↓ Thiết kế Database ↓ Migration ↓ Model 

Không bắt đầu bằng việc tạo bảng một cách máy móc. Hãy xác định hệ thống cần quản lý những đối tượng nào trước. 

## **16. VÍ DỤ: WEBSITE BÁN MÁY TÍNH** 

Đối tượng: 1. Người dùng 2. Danh mục 3. Thương hiệu 4. Sản phẩm 5. Đơn hàng 6. Chi tiết đơn hàng 7. Đánh giá 

Bảng dự kiến: users categories brands products orders order_details reviews 

Đây là ví dụ mở rộng. Sinh viên không bắt buộc phải dùng đúng số lượng bảng này nếu đề tài có yêu cầu khác. 

## **17. XÁC ĐỊNH THUỘC TÍNH** 

products ├── id ├── category_id ├── brand_id ├── name ├── price ├── quantity ├── image ├── description 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 5** 

└── created_at categories ├── id ├── name ├── description └── created_at brands ├── id ├── name └── created_at 

## **18. XÁC ĐỊNH QUAN HỆ** 

Category 1 ───── N Product Brand 1 ───── N Product User 1 ───── N Order Order 1 ───── N OrderDetail Product 1 ───── N OrderDetail 

Quan hệ giúp xác định khóa ngoại và cách xây dựng Eloquent Relationship trong Laravel. 

## **19. ÁP DỤNG CHO ĐỀ TÀI BÁN QUẦN ÁO** categories products sizes colors product_variants 

Category │ └──── N Product │ └──── N ProductVariant │ ┌──────┴──────┐ ↓ ↓ Size Color 

Một áo có thể có nhiều biến thể như Size S – Trắng, Size M – Trắng, Size M – Đen. Cấu trúc Database phụ thuộc vào nghiệp vụ của từng đề tài. 

## **20. ÁP DỤNG CHO ĐỀ TÀI BÁN SÁCH** 

categories books authors publishers orders order_details reviews 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 6** 

Category 1 ───── N Book Author 1 ───── N Book Publisher 1 ───── N Book 

## **21. CHUYỂN THIẾT KẾ THÀNH MIGRATION** 

php artisan make:migration create_brands_table 

Schema::create('brands', function (Blueprint $table) { $table->id(); $table->string('name'); $table->timestamps(); }); 

php artisan migrate 

## **22. CHUYỂN BẢNG THÀNH MODEL** 

php artisan make:model Brand 

class Brand extends Model { protected $fillable = ['name']; public function products() { return $this->hasMany(Product::class); } } **23. CẤU TRÚC THƯ MỤC CẦN TẠO / KIỂM TRA** project/ ├── app/ │ ├── Http/Controllers/ │ │ └── ProductController.php │ └── Models/ │ ├── Category.php │ └── Product.php ├── database/migrations/ │ ├── xxxx_create_categories_table.php │ └── xxxx_create_products_table.php ├── resources/views/products/ │ ├── index.blade.php │ ├── create.blade.php │ ├── edit.blade.php │ └── show.blade.php └── routes/web.php 

## **24. KIỂM TRA DATABASE** 

php artisan migrate:status php artisan route:list 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 7** 

Có thể kiểm tra trực tiếp Database bằng phpMyAdmin hoặc công cụ quản lý MySQL đang sử dụng. 

## **25. PHẦN ÁP DỤNG CHO ĐỀ TÀI RIÊNG** 

Sinh viên chọn một đề tài TMĐT và xác định: 



<!-- Start of picture text -->
Tên đề tài:<br>....................................................<br>Các đối tượng chính:<br>1. .................................................<br>2. .................................................<br>3. .................................................<br>4. .................................................<br>5. .................................................<br>Các bảng:<br>1. .................................................<br>2. .................................................<br>3. .................................................<br>4. .................................................<br>5. .................................................<br>Quan hệ:<br>....................................................<br>....................................................<br>....................................................<br>Sau khi xác định cấu trúc, sinh viên bắt đầu tạo Migration và Model.<br>Không cần sao chép nguyên Database của Fruit Variety Shop.<br>© BÙI TÁ HẬU<br><!-- End of picture text -->

## **26. CÁC TRƯỜNG HỢP CÓ THỂ PHÁT TRIỂN** 

- Website bán quần áo → Size, Color, ProductVariant. 

- Website bán máy tính → Brand, Specification. 

- Website bán sách → Author, Publisher. 

- Website bán điện thoại → Brand, RAM, Storage, Color. 

- Website bán đồ ăn → loại món, kích thước, topping. 

- Website bán mỹ phẩm → thương hiệu, dung tích, loại da. 

## **27. KIỂM TRA KẾT QUẢ** 

- Database được tạo thành công bằng Migration. 

- Các bảng có khóa chính và khóa ngoại phù hợp. 

- Model hoạt động với Database. 

- Relationship hoạt động đúng. 

- CRUD Product/Category thực hiện được. 

- Validation cơ bản hoạt động. 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 8** 

- Có thể hiển thị dữ liệu liên quan giữa các bảng. 

- Sinh viên đã xác định được Database cho đề tài riêng. 

## **28. CẤU TRÚC PROJECT SAU LAB 02** 

Laravel Project │ ├── Database │ ├── categories │ └── products ├── Models │ ├── Category │ └── Product ├── Controllers │ └── ProductController ├── Routes │ └── web.php └── Views └── products ├── index ├── create ├── edit └── show 

Sau Lab 02, sinh viên đã có nền tảng dữ liệu và CRUD để bước sang Lab 03: xây dựng Trang chủ, hình ảnh và sản phẩm. 

**Phát triển hệ thống thương mại điện tử • LAB 02** 

**© BÙI TÁ HẬU  •  Trang 9** 

