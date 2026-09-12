# Page Override: batches-index (Danh sách đợt gom)

Ưu tiên hơn MASTER.md khi build trang này.

## Mục đích
List mọi batch đang mở + trạng thái, CTA duy nhất mỗi card: "Giữ slot".

## Cấu trúc
1. Header (Bebas display) + sub mô hình cọc/ngưỡng/deadline (1 dòng).
2. Grid cards: mobile 1 col → sm 2 → lg 3/4. Card = ảnh (aspect-square, width/height chống CLS) + tên SP + progress + deadline + cọc/slot + CTA.
3. Empty state: message + nút "Xem sản phẩm".

## Quy tắc riêng (override/bổ sung)
- Progress: **bar + text số slot + %** (color-not-only: không chỉ màu).
- Deadline: text tương đối ("còn 2 ngày") + tabular numbers.
- Card hover: viền accent-blue + glow + lift 4px/300ms (theo component-specs, thay hover generic của MASTER).
- Ảnh `loading="lazy"` (trừ 2 card đầu), `onerror` fallback no-image.
- Không horizontal scroll ở 375px: grid 1 col, CTA full-width ≥44px.
