# Page Override: admin-batches-show (Admin chi tiết đợt gom)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
Admin giám sát tiến độ + can thiệp thủ công (chốt success/fail).

## Cấu trúc
1. Header + status pill + back.
2. Trái: SP + progress (bar + số + %) + 3 stat boxes (cọc/slot, deadline, tổng cọc) + bảng reservations (khách, slot, đã cọc, trạng thái, ngày).
3. Phải sticky: thông tin đợt + action zone.

## Quy tắc riêng
- Bảng: paginate, tabular numbers, responsive (cuộn ngang trong wrapper, không vỡ layout mobile).
- **Chốt thủ công** (chỉ khi open): 2 nút tách biệt — success (green) + fail (red, danger-emphasis), mỗi nút confirm dialog nêu hậu quả (notify/refund).
- Không cho sửa/xóa khi đã có active reservations (thể hiện disabled rõ + lý do).
- Mọi con số tiền format `vi-VN`.
