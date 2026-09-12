# **LAB 06** 

## **AUTHENTICATION – AUTHORIZATION – ADMIN** 

## **TÀI KHOẢN, PHÂN QUYỀN VÀ QUẢN TRỊ WEBSITE TMĐT** 

## **1. MỤC TIÊU** 

- Xây dựng chức năng đăng ký, đăng nhập và đăng xuất bằng Laravel. 

- Quản lý người dùng bằng bảng users và Eloquent Model. 

- Mã hóa mật khẩu đúng cách, không lưu mật khẩu dạng văn bản thuần. 

- Bảo vệ các trang yêu cầu người dùng đăng nhập bằng middleware. • Phân biệt quyền Customer và Admin. • Xây dựng khu vực quản trị riêng cho Admin. • Cho phép Admin quản lý sản phẩm và xử lý đơn hàng. • Liên kết tài khoản đăng nhập với đơn hàng. • Hiểu cách mở rộng cơ chế phân quyền cho các đề tài TMĐT khác. 

- Hiểu cách mở rộng cơ chế phân quyền cho các đề tài TMĐT khác. 

Lab 06 là cầu nối giữa phần mua hàng ở Lab 04–05 và phần quản trị, bảo mật, tìm kiếm, đánh giá và hoàn thiện hệ thống ở Lab 07–08. 

## **2. VỊ TRÍ CỦA LAB 06 TRONG TOÀN BỘ HỆ THỐNG** 

LAB 03 Trang chủ + Hình ảnh + Sản phẩm ↓ LAB 04 Giỏ hàng + Session + Validation ↓ LAB 05 Checkout + Order + Thanh toán QR ↓ LAB 06 Đăng ký + Đăng nhập + Phân quyền + Admin ↓ LAB 07 Tìm kiếm + Lọc + Đánh giá + Hoàn thiện ↓ LAB 08 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 1** 

Xây dựng website TMĐT theo đề tài riêng 

## **3. TƯ DUY KIẾN TRÚC** 

WEBSITE TMĐT │ ┌─────────┴─────────┐ │ │ CUSTOMER ADMIN │ │ ┌─────┴─────┐ ┌─────┴────────┐ │ │ │ │ Mua hàng Đơn hàng Sản phẩm Đơn hàng │ │ │ Cart/Checkout CRUD Product Cập nhật trạng thái │ Payment 

Authentication trả lời câu hỏi: “Người dùng là ai?”. Authorization trả lời câu hỏi: “Người dùng đó được phép làm gì?”. 

## **4. CẤU TRÚC THƯ MỤC / FILE** 

project/ ├── app/ │ ├── Http/ │ │ ├── Controllers/ │ │ │ ├── AuthController.php │ │ │ ├── Admin/ │ │ │ │ ├── DashboardController.php │ │ │ │ ├── ProductController.php │ │ │ │ └── OrderController.php │ │ │ └── ... │ │ └── Middleware/ │ │ └── AdminMiddleware.php │ └── Models/ │ ├── User.php │ ├── Product.php │ └── Order.php ├── resources/views/ │ ├── auth/ │ │ ├── login.blade.php │ │ └── register.blade.php │ └── admin/ │ ├── dashboard.blade.php │ ├── products/ │ └── orders/ └── routes/ └── web.php 

## **5. KIỂM TRA BẢNG USERS** 

Laravel đã có sẵn migration users trong nhiều project Laravel. Trước khi tạo thêm bảng, sinh viên cần kiểm tra database và migration hiện tại để 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 2** 

### tránh tạo bảng users trùng. 

database/migrations/ └── xxxx_xx_xx_xxxxxx_create_users_table.php 

Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('email')->unique(); $table->timestamp('email_verified_at')->nullable(); $table->string('password'); $table->string('role')->default('customer'); $table->rememberToken(); $table->timestamps(); }); 

Nếu project hiện tại đã có users nhưng chưa có role, có thể tạo migration riêng để thêm cột role thay vì sửa trực tiếp migration đã chạy. 

## **6. THÊM ROLE CHO USER** 

php artisan make:migration add_role_to_users_table --table=users 

Schema::table('users', function (Blueprint $table) { $table->string('role') ->default('customer') ->after('password'); }); php artisan migrate Hai giá trị cơ bản trong Lab: customer và admin. Có thể mở rộng thành staff, manager... ở mức nâng cao. 

## **7. MODEL USER** 

app/Models/User.php 

class User extends Authenticatable { use HasFactory, Notifiable; protected $fillable = [ 'name', 'email', 'password', 'role', ]; protected $hidden = [ 'password', 'remember_token', ]; protected function casts(): array 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 3** 

{ return [ 'email_verified_at' => 'datetime', 'password' => 'hashed', ]; } public function orders() { return $this->hasMany(Order::class); } } 

Với Laravel 10, nếu project đang dùng cách cấu hình $casts truyền thống thì có thể sử dụng protected $casts thay cho phương thức casts(). Không nên trộn hai cách trong cùng một Model. 

## **8. LIÊN KẾT USER VỚI ORDER** 

Để biết đơn hàng thuộc về tài khoản nào, bảng orders cần có user_id. Đây là thay đổi quan trọng so với Lab 05. 

php artisan make:migration add_user_id_to_orders_table --table=orders Schema::table('orders', function (Blueprint $table) { $table->foreignId('user_id') ->nullable() ->constrained('users') ->nullOnDelete() ->after('id'); }); 

php artisan migrate 

Để giữ khả năng cho khách chưa đăng nhập đặt COD nếu đề tài cho phép, user_id có thể nullable. Nếu hệ thống bắt buộc đăng nhập mới mua hàng, có thể đặt NOT NULL. 

## **9. CẬP NHẬT MODEL ORDER** 

class Order extends Model { protected $fillable = [ 'user_id', 'customer_name', 'customer_phone', 'customer_email', 'shipping_address', 'total_amount', 'payment_method', 'payment_status', 'order_status', ]; 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 4** 

public function user() { return $this->belongsTo(User::class); } public function details() { return $this->hasMany(OrderDetail::class); } } 

## **10. TẠO AUTH CONTROLLER** 

php artisan make:controller AuthController 

app/Http/Controllers/AuthController.php 

use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Facades\Hash; 

## **11. ĐĂNG KÝ TÀI KHOẢN** 

public function registerForm() { return view('auth.register'); } public function register(Request $request) { $validated = $request->validate([ 'name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email', 'password' => 'required|min:6|confirmed', ]); $user = User::create([ 'name' => $validated['name'], 'email' => $validated['email'], 'password' => Hash::make($validated['password']), 'role' => 'customer', ]); Auth::login($user); $request->session()->regenerate(); return redirect()->route('home'); } 

Mật khẩu phải được Hash::make() trước khi lưu. Không lưu mật khẩu người dùng bằng chuỗi văn bản thuần. 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 5** 

## **12. FORM ĐĂNG KÝ** 

resources/views/auth/register.blade.php 

<form action="{{ route('register') }}" method="POST"> @csrf <label>Họ và tên</label> <input type="text" name="name" value="{{ old('name') }}"> <label>Email</label> <input type="email" name="email" value="{{ old('email') }}"> <label>Mật khẩu</label> <input type="password" name="password"> <label>Nhập lại mật khẩu</label> <input type="password" name="password_confirmation"> <button type="submit"> Đăng ký </button> </form> **13. ĐĂNG NHẬP** public function loginForm() { return view('auth.login'); } public function login(Request $request) { $credentials = $request->validate([ 'email' => 'required|email', 'password' => 'required', ]); if (Auth::attempt($credentials)) { $request->session()->regenerate(); return redirect()->intended( route('home') ); } return back() ->withErrors([ 'email' => 'Email hoặc mật khẩu không đúng.', ]) ->onlyInput('email'); } 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 6** 

session()->regenerate() sau khi đăng nhập giúp tạo lại session ID và giảm nguy cơ session fixation. 

## **14. FORM ĐĂNG NHẬP** 

resources/views/auth/login.blade.php 

<form action="{{ route('login') }}" method="POST"> @csrf <label>Email</label> <input type="email" name="email" value="{{ old('email') }}"> <label>Mật khẩu</label> <input type="password" name="password"> <button type="submit"> Đăng nhập </button> </form> 

## **15. ĐĂNG XUẤT** 

public function logout(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('home'); } 

Đăng xuất cần hủy session hiện tại và tạo lại CSRF token. 

## **16. ROUTE AUTHENTICATION** 

use App\Http\Controllers\AuthController; 

Route::get('/register', [AuthController::class, 'registerForm']) ->name('register.form'); 

Route::post('/register', [AuthController::class, 'register']) ->name('register'); Route::get('/login', [AuthController::class, 'loginForm']) ->name('login.form'); 

Route::post('/login', [AuthController::class, 'login']) ->name('login'); 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 7** 

Route::post('/logout', [AuthController::class, 'logout']) ->name('logout'); 

## **17. HIỂN THỊ TRẠNG THÁI ĐĂNG NHẬP** 

@auth <span>Xin chào, {{ auth()->user()->name }}</span> <form action="{{ route('logout') }}" method="POST"> @csrf <button type="submit"> Đăng xuất </button> </form> @else <a href="{{ route('login.form') }}"> Đăng nhập </a> <a href="{{ route('register.form') }}"> Đăng ký </a> @endauth 

#### 

## **18. MIDDLEWARE BẢO VỆ TRANG ĐĂNG NHẬP** 

Các chức năng như xem lịch sử đơn hàng hoặc cập nhật hồ sơ cần yêu cầu người dùng đăng nhập. Route::middleware('auth')->group(function () { Route::get('/my-orders', [OrderController::class, 'myOrders']) ->name('orders.mine'); }); 

## **19. TẠO ADMIN MIDDLEWARE** 

php artisan make:middleware AdminMiddleware 

app/Http/Middleware/AdminMiddleware.php 

namespace App\Http\Middleware; use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response; 

class AdminMiddleware { public function handle( Request $request, Closure $next 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 8** 

): Response { if (!auth()->check()) { return redirect() ->route('login.form'); } if (auth()->user()->role !== 'admin') { abort(403, 'Bạn không có quyền truy cập.'); } return $next($request); } } 

## **20. ĐĂNG KÝ ALIAS MIDDLEWARE** 

Với Laravel 10, mở file app/Http/Kernel.php và thêm alias vào $middlewareAliases. 

protected $middlewareAliases = [ // ... 'admin' => \App\Http\Middleware\AdminMiddleware::class, ]; Nếu project dùng cấu trúc bootstrap/app.php của Laravel phiên bản mới hơn, cách đăng ký middleware alias khác. Sinh viên phải làm theo cấu trúc của phiên bản Laravel đang sử dụng. **21. ROUTE KHU VỰC ADMIN** 

use App\Http\Controllers\Admin\DashboardController; use App\Http\Controllers\Admin\ProductController; use App\Http\Controllers\Admin\OrderController; 

Route::middleware(['auth', 'admin']) ->prefix('admin') ->name('admin.') ->group(function () { 

Route::get('/dashboard', [DashboardController::class, 'index']) ->name('dashboard'); 

Route::resource('products', ProductController::class); 

Route::get('/orders', [OrderController::class, 'index']) ->name('orders.index'); 

Route::get('/orders/{order}', [OrderController::class, 'show']) ->name('orders.show'); 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 9** 

Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']) ->name('orders.update-status'); }); 

## **22. CẤU TRÚC ADMIN CONTROLLER** 

app/Http/Controllers/Admin/ ├── DashboardController.php ├── ProductController.php └── OrderController.php 

Tách Controller Admin thành namespace riêng giúp project rõ ràng hơn và thuận tiện mở rộng. 

## **23. ADMIN DASHBOARD** 

php artisan make:controller Admin/DashboardController 

namespace App\Http\Controllers\Admin; 

class DashboardController extends Controller { public function index() { return view('admin.dashboard'); } } resources/views/admin/dashboard.blade.php <h1>Admin Dashboard</h1> 

<h1>Admin Dashboard</h1> <p> Xin chào {{ auth()->user()->name }} </p> <a href="{{ route('admin.products.index') }}"> Quản lý sản phẩm </a> <a href="{{ route('admin.orders.index') }}"> Quản lý đơn hàng </a> 

## **24. TẠO TÀI KHOẢN ADMIN** 

Trong quá trình học, có thể tạo tài khoản admin bằng Seeder hoặc Tinker. Không nên cho người dùng tự chọn role admin trong form đăng ký. 

php artisan tinker 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 10** 

$user = \App\Models\User::create([ 'name' => 'Administrator', 'email' => 'admin@example.com', 'password' => \Illuminate\Support\Facades\Hash::make('123456'), 'role' => 'admin', ]); 

Thông tin trên chỉ dùng làm tài khoản minh họa trong môi trường học tập. Khi triển khai thực tế cần sử dụng thông tin quản trị an toàn hơn. 

## **25. QUẢN LÝ SẢN PHẨM TRONG ADMIN** 

php artisan make:controller Admin/ProductController --resource 

Controller Resource tạo các phương thức chuẩn index, create, store, show, edit, update, destroy. Sinh viên sử dụng lại kiến thức CRUD đã học ở các Lab trước. 

Route::resource( 'products', ProductController::class ); 

**26. QUẢN LÝ ĐƠN HÀNG** public function index() { $orders = Order::with('user') ->latest() ->paginate(10); return view( 'admin.orders.index', compact('orders') ); } 

Admin cần xem được mã đơn hàng, khách hàng, tổng tiền, phương thức thanh toán, trạng thái đơn và thời gian đặt hàng. 

## **27. XEM CHI TIẾT ĐƠN HÀNG** 

public function show(Order $order) { $order->load([ 'user', 'details.product' ]); return view( 'admin.orders.show', compact('order') ); } 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 11** 

## **28. CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG** 

public function updateStatus( Request $request, Order $order ) { $validated = $request->validate([ 'order_status' => 'required|in:pending,confirmed,shipping,completed,cancelled', ]); $order->update([ 'order_status' => $validated['order_status'], ]); return back()->with( 'success', 'Đã cập nhật trạng thái đơn hàng.' ); } 

Validation trạng thái giúp tránh việc người dùng gửi giá trị tùy ý lên server. 

**29. FORM CẬP NHẬT TRẠNG THÁI** <form action="{{ route( 'admin.orders.update-status', $order ) }}" method="POST" > @csrf @method('PATCH') <select name="order_status"> <option value="pending">Chờ xử lý</option> <option value="confirmed">Đã xác nhận</option> <option value="shipping">Đang giao</option> <option value="completed">Hoàn thành</option> <option value="cancelled">Đã hủy</option> </select> <button type="submit"> Cập nhật </button> </form> 

## **30. PHÂN QUYỀN Ở GIAO DIỆN** 

@auth @if(auth()->user()->role === 'admin') <a href="{{ route('admin.dashboard') }}"> Quản trị 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 12** 

</a> @endif @endauth 

Ẩn nút Admin ở giao diện chỉ giúp trải nghiệm người dùng tốt hơn. Bảo mật thực sự vẫn phải nằm ở middleware/server. 

## **31. BẢO VỆ ROUTE KHÔNG ĐƯỢC BỎ QUA** 

// Không nên chỉ kiểm tra như thế này ở Blade: 

@if(auth()->user()->role === 'admin') ... @endif 

// Phải bảo vệ Route bằng middleware: 

Route::middleware(['auth', 'admin']) ->prefix('admin') ->group(function () { // Admin routes }); 

**32. LIÊN KẾT CHECKOUT VỚI USER** Khi người dùng đã đăng nhập và đặt hàng, có thể lưu user_id vào Order. 'user_id' => auth()->id(), 'customer_name' => $request->customer_name, 'customer_phone' => $request->customer_phone, 'customer_email' => $request->customer_email, 

Nếu hệ thống bắt buộc đăng nhập trước Checkout, Route Checkout có thể đặt trong middleware auth. 

Route::middleware('auth')->group(function () { 

Route::get('/checkout', [CheckoutController::class, 'index']) ->name('checkout.index'); 

Route::post('/checkout', [CheckoutController::class, 'store']) ->name('checkout.store'); 

}); 

## **33. LỊCH SỬ ĐƠN HÀNG CỦA CUSTOMER** 

public function myOrders() { $orders = auth()->user() ->orders() ->latest() 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 13** 

->paginate(10); return view( 'orders.mine', compact('orders') ); } 

Customer chỉ được xem các Order thuộc tài khoản của mình. 

## **34. LUỒNG PHÂN QUYỀN HOÀN CHỈNH** 

LOGIN │ ┌───────┴────────┐ │ │ CUSTOMER ADMIN │ │ ┌──────┴──────┐ ┌────┴───────────┐ │ │ │ │ Mua hàng Đơn của mình Products Orders │ │ │ Cart CRUD Update status │ Checkout │ Payment **35. CÁC LỖI SINH VIÊN THƯỜNG GẶP** • Quên @csrf trong form POST/PATCH/DELETE. • Lưu password trực tiếp thay vì Hash::make(). 

- Lưu password trực tiếp thay vì Hash::make(). 

- Cho người dùng tự nhập role=admin khi đăng ký. 

- Chỉ ẩn nút Admin trên giao diện nhưng không bảo vệ Route. 

- Quên session()->regenerate() sau đăng nhập. 

- Quên invalidate session khi logout. 

- Tạo bảng users trùng với migration có sẵn. 

- Không kiểm tra user_id khi xem lịch sử đơn hàng. 

- Dùng middleware admin nhưng quên đăng ký alias. 

- Nhầm cấu trúc middleware giữa các phiên bản Laravel. 

## **36. ÁP DỤNG CHO ĐỀ TÀI KHÁC** 

Website bán quần áo Customer / Admin ↓ 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 14** 

Cart → Checkout → Order ↓ Admin quản lý sản phẩm + đơn hàng Website bán máy tính Customer / Admin ↓ Cart → Checkout → Order ↓ Admin quản lý sản phẩm + đơn hàng Website bán sách / mỹ phẩm / đồ gia dụng Customer / Admin ↓ Cart → Checkout → Order 

Sinh viên giữ nguyên kiến trúc Authentication và Authorization; thay đổi chủ yếu nằm ở Product, thuộc tính sản phẩm và nghiệp vụ riêng của đề tài. 

## **37. MỞ RỘNG HỆ THỐNG PHÂN QUYỀN** 

- Thêm role staff để nhân viên xử lý đơn hàng. 

- Thêm permission chi tiết như product.create, product.delete, order.update. 

- Phân quyền theo nhóm người dùng. 

- Khóa/mở khóa tài khoản. 

- Thêm xác minh email. 

- Thêm quên mật khẩu và đặt lại mật khẩu. 

- Thêm giới hạn số lần đăng nhập sai. 

- Thêm nhật ký hoạt động của Admin. 

## **38. KIỂM TRA KẾT QUẢ** 

- Đăng ký tài khoản Customer thành công. 

- Email trùng được hệ thống từ chối. 

- Mật khẩu được mã hóa trong database. 

- Đăng nhập đúng hoạt động. 

- Đăng nhập sai hiển thị thông báo phù hợp. 

- Đăng xuất hoạt động và session được hủy. 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 15** 

### • Customer không truy cập được khu vực Admin. 

- Admin truy cập được Dashboard. 

- Admin quản lý được sản phẩm. 

- Admin xem được danh sách và chi tiết đơn hàng. 

- Admin cập nhật được trạng thái đơn hàng. 

- Customer chỉ xem được đơn hàng của chính mình. 

- Checkout có thể lưu user_id khi người dùng đăng nhập. 

## **39. KIỂM TRA DATABASE SAU LAB 06** 

users │ ├── id ├── name ├── email ├── password └── role orders │ ├── id ├── user_id ──────────→ users.id ├── customer_name ├── total_amount ├── payment_method ├── payment_status └── order_status 

order_details │ ├── id ├── order_id ──────────→ orders.id ├── product_id ├── product_name ├── price ├── quantity └── subtotal 

## **40. CẤU TRÚC PROJECT SAU LAB 06** 

Laravel Project │ ├── app/ │ ├── Http/ │ │ ├── Controllers/ │ │ │ ├── AuthController.php │ │ │ ├── CartController.php │ │ │ ├── CheckoutController.php │ │ │ └── Admin/ │ │ │ ├── DashboardController.php │ │ │ ├── ProductController.php 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 16** 

│ │ │ └── OrderController.php │ │ └── Middleware/ │ │ └── AdminMiddleware.php │ │ │ └── Models/ │ ├── User.php │ ├── Product.php │ ├── Order.php │ └── OrderDetail.php │ ├── database/migrations/ │ ├── ...create_users_table.php │ ├── ...add_role_to_users_table.php │ ├── ...create_orders_table.php │ └── ...add_user_id_to_orders_table.php │ ├── resources/views/ │ ├── auth/ │ │ ├── login.blade.php │ │ └── register.blade.php │ └── admin/ │ ├── dashboard.blade.php │ ├── products/ │ └── orders/ │ └── routes/ └── web.php **41. KẾT NỐI SANG LAB 07** Sau Lab 06, hệ thống đã có ba lớp nghiệp vụ quan trọng: sản phẩm, mua hàng và người dùng/quản trị. Lab 07 sẽ tiếp tục hoàn thiện trải nghiệm website bằng tìm kiếm, lọc sản phẩm, đánh giá và các chức năng nâng cao. 

**Phát triển hệ thống thương mại điện tử • LAB 06** 

**© BÙI TÁ HẬU  •  Trang 17** 

