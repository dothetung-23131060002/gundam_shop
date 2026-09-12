# Page Override: product-show reviews (Đánh giá sản phẩm)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
Review đáng tin (chỉ người đã mua) — đúng pattern "Product Review/Ratings Focused" của MASTER.

## Cấu trúc (cột review)
1. Aggregate: điểm TB + breakdown sao + tổng lượt.
2. Form theo đúng 1 trong 4 trạng thái:
   - Guest → mời đăng nhập (link login).
   - Chưa mua → explainer "Chỉ khách đã mua hàng mới được đánh giá" (empty-nav-state: nói rõ vì sao).
   - Được quyền → form: star rating (button có aria-label từng sao) + textarea có label + submit.
   - Đã review → "Cảm ơn bạn đã chia sẻ!".
3. List reviews: avatar chữ cái + tên + thời gian + sao + nội dung.

## Quy tắc riêng
- Star input keyboard-operable, focus ring显式.
- Không bao giờ hiện form cho người chưa đủ điều kiện (gate ở cả controller + view).
- Verified-purchase gợi ý: ưu tiên hiển thị review của đơn completed/converted.
