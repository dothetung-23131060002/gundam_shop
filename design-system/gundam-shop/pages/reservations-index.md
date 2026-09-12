# Page Override: reservations-index (Slot của tôi)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
Tracking tất cả slot: trạng thái nhìn là hiểu, hành động tiếp theo rõ ràng.

## Cấu trúc
1. Header + (tương lai: filter theo status).
2. List rows/cards: ảnh nhỏ + tên SP + batch #id + deadline + status pill + CTA theo trạng thái.
3. Empty state: message + CTA "Xem đợt gom".

## Quy tắc riêng
- Status pill = **màu + text + (icon khi cần)**: Đang giữ (green/blue), Cần thanh toán (warning + `animate-pulse` + CTA "Thanh toán"), Đã hoàn cọc/Đã hủy (red), Đã chuyển đơn (gold/green).
- Row là link tới detail (deep-linking) + chevron affordance.
- Paginate 10/trang (quy tắc stack laravel), giữ `links()`.
- Không dùng màu đơn lẻ để phân biệt trạng thái (color-not-only).
