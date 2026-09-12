# **LAB 01** 

## **PHÂN TÍCH BÀI TOÁN VÀ XÂY DỰNG NỀN TẢNG LARAVEL PHÁT TRIỂN HỆ THỐNG THƯƠNG MẠI ĐIỆN TỬ** 

|� Hệ thống mẫu|Fruit Variety Shop|
|---|---|
|⚙ Công nghệ|Laravel 10 • PHP 8.2+ • MySQL • XAMPP • Composer • VS Code|
|� Thời lượng|5 tiết học|
|� Định hướng|Học từ hệ thống mẫu và phát triển được website TMĐT theo chủ đề riêng|



Fruit Variety Shop là hệ thống mẫu. Những kỹ thuật trong Lab có thể được chuyển sang website bán quần áo, máy tính, điện thoại, sách, mỹ phẩm hoặc các chủ đề thương mại điện tử khác. 

### **1. MỤC TIÊU** 

- Phân tích một bài toán thương mại điện tử từ yêu cầu thực tế. 

- Xác định đối tượng, thuộc tính, chức năng và quan hệ cơ bản của hệ thống. 

- Nắm được luồng xử lý cơ bản của ứng dụng Laravel. 

- Hiểu vai trò của Route, Controller, Model, View và Database. 

- Khởi tạo Laravel Project và cấu hình kết nối MySQL. 

- Tạo Route và View đầu tiên. 

- Hình thành cách tiếp cận có thể áp dụng cho một website Laravel khác. 

### **2. NỘI DUNG THỰC HÀNH** 

Fruit Variety Shop được sử dụng làm hệ thống minh họa. Trong Lab 01, sinh viên đi từ phân tích yêu cầu đến khởi tạo Laravel, kết nối Database và tạo trang đầu tiên. 

#### **Chức năng phía khách hàng** 

- Xem danh sách sản phẩm. 

- Xem chi tiết và tìm kiếm sản phẩm. 

- Thêm sản phẩm vào giỏ hàng. 

- Đặt hàng và theo dõi đơn hàng. 

- Đánh giá sản phẩm. 

#### **Chức năng phía quản trị** 

- Quản lý sản phẩm và danh mục. 

- Quản lý người dùng. 

- Quản lý đơn hàng. 

- Theo dõi doanh thu và thống kê. 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 01 

Trang 1 

### **3. PHÂN TÍCH ĐỐI TƯỢNG HỆ THỐNG** 

Từ yêu cầu nghiệp vụ, hệ thống có thể hình thành các đối tượng chính: 

User Product Category Cart Order OrderItem Review Voucher 

Các đối tượng cụ thể có thể thay đổi theo bài toán. Đây là bước chuyển từ yêu cầu nghiệp vụ sang những thành phần có thể quản lý trong phần mềm. 

### **4. LUỒNG XỬ LÝ VÀ KIẾN TRÚC MVC** 

Luồng xử lý cơ bản: 

Người dùng → Trình duyệt → Route → Controller → Model → Database → Controller → View → Trình duyệt 

Ví dụ với trang /products: 

GET /products → Route → ProductController → Product Model → Database → View 

Model: đại diện và thao tác với dữ liệu. Controller: tiếp nhận request và điều phối xử lý. View: hiển thị kết quả cho người dùng. **5. KHỞI TẠO LARAVEL PROJECT Vị trí thực hiện** C:\xampp\htdocs 

#### **Tạo project** 

composer create-project laravel/laravel fruit_variety_shop "10.*" 

#### **Di chuyển vào project** 

cd fruit_variety_shop 

#### **Khởi động** 

php artisan serve 

Mở trình duyệt: http://127.0.0.1:8000 

### **6. CẤU TRÚC PROJECT** 

#### **Các thư mục và file cần biết** 

fruit_variety_shop/ ├── app/ │ ├── Http/Controllers/ → Controller │ └── Models/ → Model ├── database/ → Migration, Seeder, Factory ├── public/ → tài nguyên public ├── resources/views/ → giao diện Blade ├── routes/web.php → Route ├── storage/ → dữ liệu sinh ra ├── vendor/ → package Composer ├── .env → cấu hình môi trường 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 01 

Trang 2 

└── artisan → công cụ dòng lệnh Laravel 

### **7. KẾT NỐI MYSQL** 

#### **Database cần tạo** 

fruit_variety_shop 

#### **File cần chỉnh sửa** 

fruit_variety_shop/.env 

#### **Cấu hình** 

DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=fruit_variety_shop DB_USERNAME=root DB_PASSWORD= 

Nếu MySQL có mật khẩu, nhập mật khẩu tương ứng vào DB_PASSWORD. 

#### **Xóa cache cấu hình** 

php artisan config:clear 

#### **Chạy Migration** 

php artisan migrate Migration thành công cho biết Laravel đã kết nối được với MySQL. **8. TẠO ROUTE File cần chỉnh sửa** routes/web.php 

#### **Nội dung** 

<?php use Illuminate\Support\Facades\Route; Route::get('/', function () { return view('home'); }); 

### **9. TẠO VIEW** 

#### **Thư mục cần tạo** 

resources/views/ 

#### **File cần tạo** 

resources/views/home.blade.php 

#### **Nội dung** 

<!DOCTYPE html> <html> <head> <title>Fruit Variety Shop</title> </head> <body> **© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 01 

Trang 3 

<h1>Fruit Variety Shop</h1> 

<p>Chào mừng bạn đến với cửa hàng trái cây trực tuyến.</p> </body> </html> 

Kiểm tra tại: http://127.0.0.1:8000 

### **10. KHẢ NĂNG CHUYỂN ĐỔI SANG WEBSITE KHÁC** 

Fruit Variety Shop chỉ là ví dụ. Khi xây dựng website khác, cấu trúc Laravel và quy trình phát triển vẫn giữ nguyên; phần nghiệp vụ, dữ liệu và giao diện sẽ được thay đổi theo chủ đề. 

#### **Website bán quần áo** 

Product → name, price, image, stock, size, color, material 

#### **Website bán máy tính** 

Product → name, price, image, stock, cpu, ram, storage, gpu 

#### **Website bán sách** 

Product → name, price, image, stock, author, publisher, isbn 

Các Lab tiếp theo sẽ tiếp tục xây dựng từng thành phần của hệ thống mẫu, đồng thời hình thành khả năng chuyển đổi các thành phần đó sang một dự án Laravel khác. 

### **11. KẾT QUẢ CẦN ĐẠT** 

##### 

- Khởi tạo thành công Laravel Project. 

- Kết nối Laravel với MySQL. 

- Nắm được cấu trúc thư mục cơ bản của Laravel. 

- Hiểu vai trò của Route, Controller, Model và View. 

- Tạo được Route và View đầu tiên. 

- Biết cách bắt đầu phân tích một hệ thống thương mại điện tử. 

- Hiểu rằng Fruit Variety Shop là hệ thống mẫu, không phải giới hạn của bài học. 

**© BÙI TÁ HẬU** 

Phát triển hệ thống thương mại điện tử • LAB 01 

Trang 4 

