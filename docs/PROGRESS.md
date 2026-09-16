# PROGRESS — Gundam Shop Group Pre-order

Log tiến độ theo phần. Mỗi phần ghi: làm gì, file chính, test, quyết định.

## Đã xong (trước rebuild admin)

- Bước 0→3.8: audit, UI tokens (dark mecha HUD), schema (batches/reservations/
  payments/refund_transactions + orders.batch_id + reviews unique), auth fix,
  admin Batch CRUD, reservation + balance payment, order status + hoàn kho,
  cancel reservation + refund, concurrency fix (lock trong transaction),
  review gate (completed/converted), notifications DB, BatchService +
  force success/fail, seeders, factories, 18 tests.
- Skill rebuild (Phase 0→6b): brand-guidelines, tokens 3 tầng, MASTER.md +
  8 page overrides, rebuild 41 views (a11y/tokens/x-status-pill), validate pass,
  hero A (home) + C-compact (batches), slide bảo vệ 10 slides.
- Sau đó: mail channel (conditional), realtime polling progress, N+1 fix (withSum),
  dọn view chết, zoom fix, deploy checklist, README, timezone Asia/Ho_Chi_Minh.
- Gemini AI-gen: TẠM DỪNG (key bị phía Google chặn) — xem MASTER.md mục F.

## Phần 1 — Hoàn thiện Batch admin (đang làm)

- Tách 2 action: `closeEarly` (đủ ngưỡng mới được, nút xanh) vs `forceSuccess`
  demo (ép khi thiếu ngưỡng, nút vàng + confirm demo). `forceFail` giữ nguyên.
- View `admin/batches/show`: hiện nút theo điều kiện (đủ → Đóng sớm; thiếu → Buộc demo).
- `admin/batches/index`: thêm cột Tiến độ (% + mini bar + progressbar ARIA).
- Skill: `ui-ux-pro-max` (destructive-emphasis, confirmation-dialogs, color-not-only).
- Test: `AdminBatchActionsTest` — closeEarly ok/block, force demo, guest 302, customer 403.

## Phần 2 — Admin Reservation (xong)

- Mới: `Admin\ReservationController` (index lọc status + keyword, show full
  relations), routes GET-only, 2 views, link sidebar "Giữ slot".
- Bug phát hiện khi test: `orders` thiếu `reservation_id` → relation
  `Reservation::order()` 500 cả frontend lẫn admin. Fix: migration thêm FK
  nullable + backfill từ payment balance + set khi tạo order.
- Skill: `ui-ux-pro-max` (x-status-pill, tabular-nums, aria-labels).
- Test: `AdminReservationTest` — list/filter, detail, guest 302, customer 403.

## Phần 3 — Admin Refund (xong)

- Mới: `Admin\RefundController` (index + keyword, show full relations, kèm tổng
  đã hoàn), routes GET-only, 2 views, link sidebar "Hoàn cọc".
- Read-only đúng yêu cầu: không create/edit/destroy.
- Skill: `ui-ux-pro-max` (x-status-pill, tabular-nums, dialog semantics).
- Test: `AdminRefundTest` — list, detail, guest 302, customer 403.

## Phần 4 — Dashboard stats (xong)

- Mở rộng `Admin\DashboardController` có sẵn (không controller riêng): successRate/
  failedRate trên batch đã chốt (guard chia 0), heldDeposit (sum reserved),
  topProducts top 5 theo slot (join batches + reservations, group by).
- View: section "Thống kê đợt gom" (3 cards + bảng top), tái dùng style card cũ.
- Skill: `ui-ux-pro-max` (tabular-nums, table responsive, empty state).
- Test: `AdminDashboardStatsTest` — số liệu đúng, empty state, authz.
- Note test: `actingAs` dính sang call sau trong cùng test → luôn assert guest TRƯỚC.

## Rà soát phạm vi skill protocol (user yêu cầu dừng để làm rõ)
- User chỉ ra: hướng "rebuild toàn bộ 41 views" là suy diễn mở rộng của assistant,
  không phải nguyên văn yêu cầu. Assistant nhận lỗi phạm vi.
- Quyết định: full protocol CHỈ áp cho lõi pre-order (batch, reservation, refund,
  notifications, dashboard batch stats). Phần còn lại ĐÓNG BĂNG (ghi MASTER.md mục H).
- Deck 10 slides: user duyệt dàn ý, đóng băng nội dung chờ ý kiến cụ thể (tên nhóm/GVHD...).

## Bổ sung sau audit admin (theo quyết định user, ưu tiên 1→3)

### Phần 1 — Thu hộ tạo đơn (xong)
- Mới trên `Admin\ReservationController`: `collectForm` (prefill profile chủ slot,
  chọn tiền mặt/chuyển khoản) + `collectBalance` (mirror user-flow: validate,
  lockForUpdate, re-check, tạo Order paid/cash + Detail + Payment + converted).
- Routes GET/POST `admin/reservations/{id}/collect`; nút trên admin show khi
  needsPayment; nhánh `'cash'` ở match 2 view orders. KHÔNG đụng OrderController cũ.
- Skill: `ui-ux-pro-max`. Test: `AdminCollectBalanceTest` (form/collect/block/authz).

### Phần 2 — Audit log (xong)
- Mới: migration `admin_action_logs` (admin/action/batch/reservation/reason),
  model + trait `LogsAdminActions`, hook vào 4 actions (closeEarly/forceSuccess/
  forceFail/collectBalance), ô lý do (prompt ở 3 nút batch, textarea ở form thu hộ).
- Trang `admin.action-logs.index` read-only (filter action) + link sidebar "Nhật ký".
- Test: `AdminActionLogTest` (ghi đúng admin/reason, xem index, authz).

### Phần 3 — Reservation history ở admin User (xong)
- `UserController@show` load thêm `reservations.batch.product`; section mới
  "Lịch sử giữ slot" đủ 4 trạng thái (kể cả hoàn cọc/hủy) + cọc + link batch.
- Không đụng controller/view nào khác. Skill: `ui-ux-pro-max`.
- Test: `AdminUserReservationsTest` (đủ 4 trạng thái + empty state).

## Export CSV (xong, theo yêu cầu chốt thứ tự)
- Trait `ExportsCsv`: streamDownload + BOM UTF-8 + fputcsv, không package mới.
- `OrderController@export` (full list + tổng đơn/doanh thu paid),
  `BatchController@export` (tôn trọng filter status/keyword + tổng cọc giữ,
  tách `filteredQuery()` dùng chung với index).
- Routes đặt TRƯỚC `/{param}` để không bị nuốt; nút Xuất CSV 2 index.
- Test: `AdminExportTest` (BOM/header/summary/filter/authz).

## Dọn view coupon chết (xong)
- Xóa `admin/coupons/index.blade.php` (đã verify 0 model/migration/route/
  controller/link). Không CRUD rỗng — coupon thật để dành hướng phát triển.

## Settings (xong)
- Mới: migration `settings` KV + model (cache rememberForever, DEFAULTS khớp số
  đang hardcode) + `SettingController` edit/update + view + sidebar "Cài đặt".
- Nối 3 chỗ hiển thị/prefill: batch create (cọc/deadline), footer contact, title.
  Seeder mặc định mọi môi trường. Không đổi logic cũ.
- Test: `AdminSettingsTest` (lưu + validate + authz).

## Siết guard xóa Batch (xong, theo quyết định user)
- `destroy()`: chỉ xóa khi `open` + `reservations()->exists()` false (kể cả lịch
  sử refunded/converted/cancelled cũng chặn — bảo vệ refund_transactions đối soát).
- `index()`: thêm `withCount` (tránh N+1); nút xóa hiện theo `total_reservations`.
- Test: xóa batch trắng ok; chặn khi chỉ còn lịch sử refunded; chặn khi đã đóng.

## Email chào mừng đăng ký (xong, phương án (a))
- Mới: `WelcomeRegistered` (toMail VI + trait conditional có sẵn), hook 1 dòng
  trong `AuthController@register`. Nội dung đúng mô hình pre-order 3 bước
  (cọc giữ slot → đủ ngưỡng → trả nốt), không nhắc mua ngay/giỏ hàng.
- Không xác thực bắt buộc (tránh rủi ro demo). Test: `WelcomeEmailTest`
  (ghi notify, nội dung đúng/sai từ khóa, kênh theo config).

## Chuẩn bị bảo vệ — rà số liệu + seed demo (xong)
- Đếm thật: 63 tests (62 feature + 1 unit), 189 assertions, 48 views, 24 migrations.
- Sửa chart tests thiếu `WelcomeEmailTest`; số cũ sai Example/AdminBatch (đừng cộng tay).
- Slides + README đồng bộ 63/189, validator pass. Số "8 phân hệ" (slide 05) để user quyết.
- Seed demo sạch: admin OK, demo customer/pass OK, batch 9/10 open, 3/8 open, 5/5 success.

## Fix mail crash khi đăng ký (xong)
- Nguyên nhân: `MAIL_HOST=mailpit` (Docker hostname) trên Windows native →
  TransportException tại `notify()` dòng 35, response 500 dù user đã tạo + login.
- Fix: `.env` → `127.0.0.1` (user tự sửa) + bọc notify trong try-catch
  (`Log::warning`, redirect bình thường khi mail lỗi). Không dùng ShouldQueue
  (queue sync + chưa worker).
- Soát user rác: 10 user thiếu welcome-notify đều là seed hợp lệ (admin/demo/filler),
  KHÔNG có orphan từ lần lỗi (đã bị cuốn theo `migrate:fresh` trước đó). Không xóa gì.
- Verify end-to-end: notify thật → Mailpit API có 1 msg đúng subject (23:47+07).

## Chuyển Gmail SMTP thật (xong)
- `.env`: smtp/smtp.gmail.com/587 + username/from + App Password 16 ký tự
  (chỉ nằm trong lệnh ghi, không in/không docs) + tls + from name Gundam Shop.
- `phpunit.xml` giữ nguyên `MAIL_MAILER=array` — tests mù với SMTP thật.
- Verify: gửi thử tới chính Gmail trên → TESTMAIL_SENT, không exception
  (DB demo hoàn nguyên). Pint pass, 63/63 tests xanh.
