# Gundam Shop — Group Pre-order (Laravel 10)

Đồ án môn Phát triển hệ thống thương mại điện tử: cửa hàng mô hình Gundam theo mô hình
**gom đơn theo đợt (group pre-order)** — khách đặt cọc giữ slot, đủ ngưỡng thì thanh toán
phần còn lại thành đơn hàng, thiếu ngưỡng thì hoàn cọc tự động.

## Mô hình hoạt động

```
Batch (đợt gom): product + threshold (ngưỡng slot) + deposit_amount + deadline + status
  open → success (đủ ngưỡng) → reservation trả nốt → Order (paid/pending)
  open → failed (hết hạn, thiếu ngưỡng) → auto-refund (RefundTransaction + Payment)
```

- Giữ slot: `POST /reservations` — transaction + `lockForUpdate`, chống vượt ngưỡng/đặt trùng.
- Hủy slot: `DELETE /reservations/{id}` — chỉ khi batch còn `open`, hoàn cọc.
- Trả nốt: `POST /reservations/{id}/process-balance` — chống submit 2 lần, sinh Order.
- Scheduler mỗi phút: `batches:check-expired` — chốt success/fail + notify + refund.
- Review: chỉ khách đã mua (order completed hoặc reservation converted).

## Công nghệ

Laravel 10 • PHP ^8.1 • MySQL • Blade • Tailwind CSS v4 • Vite 5 • Queue sync • SQLite (test)

## Cài đặt (local)

1. Tạo database `gundam_shop` (utf8mb4).
2. Cấu hình `.env` (DB_*, APP_URL).
3. `composer install`
4. `npm install && npm run build` (hoặc `npm run dev` khi phát triển)
5. `php artisan key:generate`
6. `php artisan migrate --seed` (local: kèm batch + user demo; production: chỉ admin)
7. `php artisan storage:link` (ảnh sản phẩm do admin upload)
8. `php artisan serve` → `http://127.0.0.1:8000`

Tài khoản mẫu (sau seed):

| Role | Email | Mật khẩu | Ghi chú |
|---|---|---|---|
| ADMIN | `admin@gundamshop.vn` | `123456` | Mọi môi trường |
| User demo | `demo@gundam.test` | `password` | Chỉ local (BatchSeeder) |

## Scheduler (bắt buộc)

Batch chỉ tự chốt/refund khi scheduler chạy:

- Linux cron: `* * * * * cd /path/to/gundam_shop && php artisan schedule:run >> /dev/null 2>&1`
- Windows: Task Scheduler chạy `php artisan schedule:run` mỗi phút.
- Chạy tay: `php artisan batches:check-expired`

## Kiểm thử & chất lượng

- `php artisan test` — 63 tests / 189 assertions (SQLite `:memory:`), bao phủ: đặt cọc,
  hết hạn success/fail + refund, trả nốt + chống double-submit, hủy slot, review gate,
  mail channel, welcome email, progress endpoint, N+1, admin batch (đóng sớm/buộc demo/xóa an toàn)/
  reservation/refund/dashboard/action-log/collect/user-history/settings/export + phân quyền.
- Notifications 2 kênh: database (luôn gửi) + mail (tự bật khi SMTP đã cấu hình).
- Trang chi tiết đợt gom tự cập nhật tiến độ mỗi 10s (`GET /batches/{id}/progress`).
- `vendor\bin\pint` — Laravel Pint (PHP).
- `node <skills>/design-system/scripts/validate-tokens.cjs --dir resources/views` — soát hardcode token.
- `npm run build` — verify Tailwind v4 biên dịch.

## Design system (Skill Agent)

- `docs/brand-guidelines.md` — brand + voice + palette (nguồn sự thật).
- `assets/design-tokens.json` → `assets/design-tokens.css` (3 tầng primitive/semantic/component).
- `design-system/gundam-shop/MASTER.md` + `pages/*.md` — luật UX (ui-ux-pro-max).
- `docs/component-specs.md` — spec button/badge/card/input.
- Slide bảo vệ: `public/slides/bao-ve-project.html`.

## Chức năng shop cơ bản (kế thừa)

Trang chủ, danh mục, chi tiết SP, tìm kiếm/lọc/sắp xếp/phân trang, giỏ hàng (Session),
checkout COD/QR mô phỏng, auth + phân quyền, admin dashboard/CRUD, notifications database.

## Admin batch (rebuild riêng, xem `docs/PROGRESS.md`)

- Đợt gom: index (% tiến độ) + CRUD + show (reservations), **Đóng sớm** (đủ ngưỡng)
  vs **Buộc thành công demo** (ép thiếu ngưỡng) vs Chốt thất bại + hoàn cọc.
  Xóa batch chỉ khi `open` + chưa từng có reservation (bảo vệ lịch sử hoàn cọc).
- Giữ slot: index (lọc status + keyword) + show (timeline tiền, link batch/order).
- Thu hộ: admin xác nhận đã nhận tiền → tự tạo Order paid (cash) + converted.
- Hoàn cọc: index (tổng đã hoàn) + show, read-only.
- Nhật ký thao tác: ai/lúc nào/lý do cho 4 actions tiền (close/force/collect).
- Dashboard: tỷ lệ success/fail, tổng cọc đang giữ, top SP được cọc.
- Trang user: thêm lịch sử giữ slot (kể cả hoàn cọc/hủy).
- Cài đặt chung: tên shop, cọc/deadline mặc định, liên hệ footer.

## Đóng gói & nguồn dữ liệu mẫu

- Ảnh sản phẩm mẫu trong `public/assets/images/` (CDN Senshi/HACOM/De Toyz — demo học tập).
- Tên + giá tham khảo (09/2026): Senshi Hobby, AZGundam, HACOM.
