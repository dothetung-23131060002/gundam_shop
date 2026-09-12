# Page Override: reservations-show (Chi tiết giữ slot)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
Biên lai + timeline tiền + hành động tiếp theo theo đúng 1 trạng thái.

## Cấu trúc
1. Back link + mã giữ slot.
2. Grid: trái SP + timeline giao dịch (cọc → hoàn/balance, +/- số tiền mono) + refund box nếu có; phải summary sticky (slot, cọc/slot, tổng đã cọc, trạng thái, ngày đặt).
3. Action zone duy nhất theo trạng thái:
   - `needsPayment` → box success + số còn lại + CTA "THANH TOÁN PHẦN CÒN LẠI".
   - `converted` → box info + link xem đơn hàng.
   - `isCancellable` → nút Hủy (danger, confirm dialog).
   - `refunded/cancelled` → refund box (số +, lý do, thời gian).

## Quy tắc riêng
- Số tiền: font mono tabular, format `vi-VN` + "đ".
- Hủy slot = destructive: tách biệt CTA chính, confirm nêu rõ "hoàn cọc".
- Timeline dùng icon + text (không màu đơn lẻ).
