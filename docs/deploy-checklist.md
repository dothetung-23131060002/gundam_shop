# Deploy Checklist — Gundam Shop (Laravel 10, PHP ^8.1)

## 1. Môi trường

- [ ] PHP ^8.1 + ext: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo_mysql, tokenizer, xml
- [ ] Composer + Node 18+ (build assets)
- [ ] MySQL 8 (utf8mb4), tạo DB trống
- [ ] `APP_TIMEZONE` đã là `Asia/Ho_Chi_Minh` trong `config/app.php` (deadline batch tính theo giờ VN)

## 2. File `.env` production

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-thật`
- [ ] `APP_KEY` đã generate (không copy key của local)
- [ ] `DB_*` trỏ DB production; `SESSION_DRIVER=file` (hoặc database)
- [ ] `LOG_LEVEL=error`; `MAIL_*` SMTP thật (xem C1)
- [ ] Không commit `.env` lên git

## 3. Build & optimize

```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
php artisan key:generate
php artisan migrate --force          # KHÔNG --seed BatchSeeder (đã gate local)
php artisan db:seed --class=AdminSeeder --force   # chỉ admin
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- [ ] Phân quyền ghi: `storage/`, `bootstrap/cache/`
- [ ] Document root trỏ `public/`

## 4. Scheduler (bắt buộc — batch không tự chốt nếu thiếu)

```cron
* * * * * cd /path/to/gundam_shop && php artisan schedule:run >> /dev/null 2>&1
```

- [ ] Kiểm tra log: batch hết hạn chuyển success/failed đúng giờ
- [ ] Windows Server: Task Scheduler mỗi phút

## 5. Sau deploy

- [ ] Smoke test: home → batches → đặt cọc (user test) → admin chốt → trả nốt → order
- [ ] `php artisan test` xanh trên staging trước khi lên prod
- [ ] Backup DB định kỳ (batch/refund cần đối soát)

## 6. Ghi chú kiến trúc (chưa đổi — roadmap)

- Queue đang `sync`: notify gửi đồng bộ trong request. Production tải cao → chuyển
  `QUEUE_CONNECTION=database`, chạy `php artisan queue:work`, tạo bảng `jobs`.
- Thanh toán đang mô phỏng. Tích hợp VNPay sandbox trước khi nhận tiền thật (xem C2).
- (Tùy chọn) `locale=vi` cho `diffForHumans()` tiếng Việt — cần thêm file lang vi
  cho validation để message đồng nhất.
