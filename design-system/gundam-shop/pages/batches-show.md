# Page Override: batches-show (Chi tiết đợt gom)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
Thuyết phục giữ slot: bằng chứng tiến độ + scarcity (deadline) + 1 CTA chính.

## Cấu trúc
1. Breadcrumb (Trang chủ / Đợt gom / #id) + nút back (back-behavior).
2. Grid lg 2/3 + 1/3: trái ảnh + mô tả + lịch sử; phải sticky summary (ngưỡng, đã đặt, còn lại, cọc/slot, deadline) + form giữ slot.
3. Form: label显式 cho quantity stepper (min 1 max 5) + tổng cọc live + nút submit.

## Quy tắc riêng
- **Một primary CTA**: "GIỮ SLOT NGAY"; link phụ (xem SP) subordinate.
- Confirm trước submit tiền: hiển thị tổng cọc trong nút ("Giữ 2 slot — 100.000đ").
- Lỗi validation: inline dưới field + focus field đầu tiên lỗi (focus-management).
- Submit: disable + spinner (loading-buttons), tránh double-submit.
- `prefers-reduced-motion`: tắt pulse progress.
