# Page Override: pay-balance (Thanh toán phần còn lại)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
Thu thập địa chỉ giao hàng + xác nhận số tiền lần cuối (multi-step cuối).

## Cấu trúc
1. Step indicator: 1 Đặt cọc ✓ → 2 Batch thành công ✓ → 3 Thanh toán (hiện tại).
2. Form: họ tên, SĐT (`type="tel"`, `autocomplete="tel"`), email (optional), địa chỉ — mỗi field có `<label for>`, helper text, lỗi inline.
3. Preview đơn: tổng giá − đã cọc = **còn phải trả** (nổi bật, mono).
4. Nút submit hiển thị số tiền; disable + spinner khi gửi.

## Quy tắc riêng
- Cho phép back về reservation (escape-routes) không mất dữ liệu đã nhập (giữ `old()`).
- Không auto-submit; xác nhận 1 lần duy nhất (chống double-order ở backend đã có lock).
- Lỗi: error-summary gọn + focus field lỗi đầu tiên.
- Touch input ≥44px; keyboard SĐT trên mobile (`input-type-keyboard`).
