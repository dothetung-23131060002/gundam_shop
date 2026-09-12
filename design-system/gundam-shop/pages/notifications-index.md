# Page Override: notifications-index (Thông báo)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
User không bỏ lỡ sự kiện batch/order (luồng 3.4 phụ thuộc vào đây).

## Cấu trúc
1. Header + "Đánh dấu đã đọc tất cả" (chỉ khi có unread).
2. List: unread-first, item unread nổi bật (border accent + nền tint), read mờ hơn.
3. Mỗi item: message + thời gian tương đối + link "Xem →" tới trang liên quan.
4. Empty state: icon chuông + CTA "Xem đợt gom".

## Quy tắc riêng
- Badge header đếm unread; **clear sau khi user vào trang** (tab-badge).
- Link sâu tới đúng trang (deep-linking): success → my-reservations, refund → reservation detail, order → orders.show.
- Paginate 15/trang.
- Toast/alert hệ thống (nếu có) `aria-live="polite"`, không cướp focus.
