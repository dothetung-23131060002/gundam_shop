# Brand Guidelines v1.0 — Gundam Shop

> Last updated: 2026-09-12
> Status: Approved (khớp tokens trong `resources/css/app.css`)

## Quick Reference

| Element | Value |
|---------|-------|
| Primary Color | #1E88E5 |
| Secondary Color | #E53935 |
| Accent Color | #FFD54F |
| Primary Font | Bebas Neue |
| Body Font | Inter |
| Voice | Am hiểu mô hình, nhiệt tình, rõ ràng |

---

## 1. Color Palette

### Primary Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| Gundam Blue | #1E88E5 | rgb(30,136,229) | CTA chính, link, trạng thái đã xác nhận |
| Gundam Blue Dark | #1565C0 | rgb(21,101,192) | Hover CTA, nhấn mạnh |

### Secondary Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| Mecha Red | #E53935 | rgb(229,57,53) | Hành động nguy hiểm, badge hủy/thất bại |
| Mecha Red Dark | #C62828 | rgb(198,40,40) | Hover destructive |

### Accent Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| Victory Gold | #FFD54F | rgb(255,213,79) | Tiền cọc, giá tiền, badge nổi bật, sao đánh giá |

### Neutral Palette

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| Background Primary | #0A0A0F | rgb(10,10,15) | Nền trang (dark) |
| Background Secondary | #12121A | rgb(18,18,26) | Card, section |
| Background Tertiary | #1A1A24 | rgb(26,26,36) | Nền input, hover |
| Text Primary | #FFFFFF | rgb(255,255,255) | Tiêu đề, nội dung chính |
| Text Secondary | #A0A0B0 | rgb(160,160,176) | Caption, mô tả phụ |
| Border | #2A2A35 | rgb(42,42,53) | Viền, divider |

### Semantic Colors

| State | Hex | Usage |
|-------|-----|-------|
| Success | #22C55E | Đợt gom thành công, đã thanh toán, đã giao |
| Warning | #F59E0B | Chờ xác nhận, cần thanh toán |
| Error | #E53935 | Lỗi, hủy, thất bại |
| Info | #1E88E5 | Thông tin, link |

### Status → Màu (quy ước toàn shop)

| Trạng thái | Màu |
|------------|-----|
| Đợt gom đang mở / Đang giữ slot | Success green / accent-blue |
| Cần thanh toán | Warning amber + pulse |
| Đã hoàn cọc / Đã hủy / Thất bại | Mecha Red |
| Đã chuyển đơn / Đã giao | Success green / accent-gold |

### Accessibility

- Mục tiêu WCAG 2.1 AA (tương phản text 4.5:1) — verify lại ở checklist Phase 5.
- Không truyền tải thông tin chỉ bằng màu sắc (kèm icon/text).

---

## 2. Typography

### Font Stack

```css
--font-display: 'Bebas Neue', sans-serif;
--font-body: 'Inter', sans-serif;
--font-mono: ui-monospace, 'JetBrains Mono', monospace;
```

### Type Scale

| Element | Size (Desktop) | Size (Mobile) | Weight | Line Height |
|---------|----------------|---------------|--------|-------------|
| Display (Bebas) | 48px | 32px | 400 | 1.2 |
| H1 | 36px | 28px | 700 | 1.25 |
| H2 | 28px | 24px | 600 | 1.3 |
| H3 | 24px | 20px | 600 | 1.35 |
| Body | 16px | 16px | 400 | 1.5 |
| Small | 14px | 14px | 400 | 1.5 |
| Caption | 12px | 12px | 400 | 1.4 |
| Mono (giá, số liệu) | 14-20px | 14-18px | 700 | 1.5 |

### Font Loading

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

---

## 3. Logo Usage

Logo hiện tại: SVG mecha-mark + wordmark "GUNDAM SHOP / PREMIUM MODEL KITS" trong header.

### Variants

| Variant | File | Use Case |
|---------|------|----------|
| Icon + wordmark (header) | inline SVG trong `components/header.blade.php` | Navbar mọi trang |
| Icon Only | SVG mark 40-48px | Favicon, mobile |

### Don'ts

- Don't rotate or skew the logo
- Don't change colors outside approved palette
- Don't add shadows or effects
- Don't crop or modify proportions
- Don't place on busy backgrounds without sufficient contrast

---

## 4. Voice & Tone (tiếng Việt)

### Brand Personality

| Trait | Description |
|-------|-------------|
| **Am hiểu mô hình** | Giải thích rõ cọc/ngưỡng/deadline, không để user bỡ ngỡ |
| **Nhiệt tình** | Khích lệ gom đủ slot, ăn mừng khi batch thành công |
| **Rõ ràng** | Số tiền, deadline, bước tiếp theo luôn hiển thị cụ thể |

### Voice Chart

| Trait | We Are | We Are Not |
|-------|--------|------------|
| Am hiểu | Giải thích cơ chế trước khi thu tiền | Úp mở, thuật ngữ khó hiểu |
| Nhiệt tình | Cổ vũ, minh bạch tiến độ | Thờ ơ, spam |
| Rõ ràng | Số liệu + deadline cụ thể | Chung chung, mập mờ |

### Tone by Context

| Context | Tone | Example |
|---------|------|---------|
| Đặt cọc thành công | Vui, xác nhận rõ | "Đã giữ slot thành công! Bạn đã cọc 2 slot, tổng 100.000đ." |
| Batch thành công | Khẩn trương + hướng dẫn | "Đợt gom đã thành công! Vui lòng thanh toán phần còn lại để nhận hàng." |
| Batch thất bại | Trấn an + giải pháp | "Đợt gom chưa đạt ngưỡng. Tiền cọc đã được hoàn về tài khoản." |
| Lỗi form | Bình tĩnh, chỉ cách sửa | "Số điện thoại chưa đúng định dạng." |
| Review | Trân trọng | "Cảm ơn bạn đã chia sẻ!" |

### Prohibited Terms

| Avoid | Reason |
|-------|--------|
| Giá rẻ nhất thị trường | Không kiểm chứng được |
| Cam kết 100% có hàng | Trái bản chất gom đơn theo ngưỡng |
| Cọc không hoàn lại | Sai chính sách (hoàn khi fail/hủy) |

---

## 5. Imagery Guidelines

### Photography Style

- **Subjects:** Ảnh sản phẩm Gundam thật, nền tối đồng bộ.
- **Fallback:** `assets/images/no-image.jpg` khi ảnh lỗi (`onerror`).
- **Treatment:** Giữ palette brand, hiệu ứng glow-blue khi hover card.

### Icons

- Style: Outlined SVG, lưới 24px.
- Stroke: 2px consistent, bo góc.
- Không dùng emoji làm icon chức năng.

---

## 6. Design Components

### Buttons

| Type | Background | Text | Border Radius |
|------|------------|------|---------------|
| Primary | #1E88E5 | #FFFFFF | 12px |
| Primary hover | #1565C0 + glow | #FFFFFF | 12px |
| Secondary | Transparent + border #2A2A35 | #FFFFFF | 12px |
| Outline Red | Transparent + border #E53935 | #E53935 | 12px |

### Spacing Scale (Tailwind 4pt)

| Token | Value | Usage |
|-------|-------|-------|
| xs | 4px | Tight spacing |
| sm | 8px | Compact elements |
| md | 16px | Standard spacing |
| lg | 24px | Section spacing |
| xl | 32px | Large gaps |
| 2xl | 48px | Section dividers |

### Border Radius

| Element | Radius |
|---------|--------|
| Buttons | 12px |
| Cards | 16px |
| Inputs | 12px |
| Badges nhỏ | 6px |
| Pills/Tags | 9999px |

### Cards

- Nền `bg-secondary`, viền `border`, radius 16px.
- Hover: viền accent-blue + glow + lift 4px (transition 300ms).

---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-09-12 | Initial guidelines (trích xuất từ app.css + views hiện tại) |
