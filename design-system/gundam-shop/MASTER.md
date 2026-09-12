# Design System Master File

> **LOGIC:** When building a specific page, first check `design-system/pages/[page-name].md`.
> If that file exists, its rules **override** this Master file.
> If not, strictly follow the rules below.
>
> **PROJECT BRAND OVERRIDE (quyết định dự án, ưu tiên cao nhất):**
> Màu sắc + typography KHÔNG lấy từ bảng Color Palette/Typography dưới đây
> (output generic của script) mà từ `docs/brand-guidelines.md` +
> `assets/design-tokens.json` (dark mecha HUD: bg #0A0A0F, accent-blue #1E88E5,
> accent-red #E53935, accent-gold #FFD54F; fonts Bebas Neue + Inter).
> File này giữ vai trò chuẩn UX: patterns, effects, anti-patterns, checklist.
>
> **SKILL COMPLIANCE PROTOCOL (bắt buộc, không ngoại lệ):**
> Trước khi làm task, xác định loại task và invoke skill TƯƠNG ỨNG trước khi code.
> Không miễn trừ cho việc nhỏ. Khi báo cáo: nêu skill đã invoke + rule đã áp dụng.
> Vi phạm protocol = việc chưa xong.
>
> **A. UI/Design (`.agents/skills/` + `gundam_shop/.agents/skills/`):**
> | Task | Skill |
> |------|-------|
> | Đổi giao diện/tương tác/chuyển động | `ui-ux-pro-max` trước, cộng `ui-styling` khi cần Tailwind/a11y/dark-mode (KHÔNG chạy CLI React/shadcn — project là Blade) |
> | Tokens, component specs, CSS vars, validate slides | `design-system` |
> | Brand, voice, palette, message, asset | `brand` (+ `design` khi cần logo/CIP/icon/social-photos) |
> | Banner, cover, hero | `banner-design` |
> | Slide thuyết trình | `slides` |
> | Gói tổng hợp brand→tokens→UI | `design` (router) điều phối các skill trên |
>
> **B. Engineering (`Skill agent/agent-skills-main/skills/` — 25 skills):**
> TDD→`test-driven-development`; review→`code-review-and-quality`; bug khó→`debugging-and-error-recovery`;
> perf→`performance-optimization`; bảo mật→`security-and-hardening`; docs quyết định→`documentation-and-adrs`;
> plan Scope→`planning-and-task-breakdown`; triển khai→`shipping-and-launch`/`ci-cd-and-automation`;
> git→`git-workflow-and-versioning`; API→`api-and-interface-design`; test trình duyệt→`browser-testing-with-devtools`;
> đo lường→`observability-and-instrumentation`; các skill còn lại (`spec-driven-development`,
> `constraint-driven-development`, `incremental-implementation`, `code-simplification`,
> `context-engineering`, `deprecation-and-migration`, `doubt-driven-development`, `idea-refine`,
> `interview-me`, `source-driven-development`, `using-agent-skills`) invoke khi đúng tình huống tên gọi.
>
> **C. Content & web artifacts (`Skill agent/skills-main/skills/` — 19 skills):**
> UI web phụ→`frontend-design`; test web→`webapp-testing`; theme→`theme-factory`;
> docx/xlsx/pptx/pdf→skill định dạng tương ứng; `brand-guidelines` trùng vai `brand` (canonical: `brand`).
>
> **D. Taste & redesign (`Skill agent/taste-skill-main/skills/` — 13 skills):**
> Gu thẩm mỹ→`taste-skill`; làm lại giao diện→`redesign-skill`; phong cách→`minimalist/soft/brutalist-skill`;
> sinh ảnh web/mobile→`imagegen-frontend-web/mobile`; ảnh→code→`image-to-code-skill`.
>
> **E. Học liệu (`Skill agent/QE-AI-main/`):** curriculum + docs tiếng Việt — chỉ để đọc/học, không invoke như skill.
> Trùng lặp: `ui-ux-pro-max-skill-main` là bản gốc của `ui-ux-pro-max` (canonical: `ui-ux-pro-max`).
>
> **F. AI-gen (logo/CIP/icon của skill `design`) — TẠM DỪNG:**
> User quyết định bỏ (09/2026): key thử nghiệm đều bị phía Google chặn, env/packages đã dọn sạch.
> Mở lại khi có key project sạch: set `GEMINI_API_KEY` (env user, không ghi file) + `pip install google-genai pillow` + test 1 lệnh SDK trước khi chạy script.
> - Các skill trong archive (B/C/D) chưa đăng ký vào skill tool → đọc trực tiếp `SKILL.md` và tuân thủ nội dung.
>
> **G. Pure backend** (migration/model/command/test/seed): được skip skill nhưng phải ghi rõ lý do theo tiêu chí skip.
>
> **H. PHẠM VI ÁP DỤNG (quyết định của user, sau rà soát phạm vi):**
> Full protocol (A–G) CHỈ áp cho màn hình pre-order cốt lõi: batches (user + admin),
> reservations (user + admin + pay-balance), refunds (admin), notifications,
> dashboard batch stats.
> ĐÓNG BĂNG phần còn lại (products/cart/checkout/auth/categories, admin
> products/users/categories/reviews/coupons, section legacy của home):
> không rebuild thêm dưới mọi hình thức; chỉ sửa bug cụ thể do user báo,
> ghi rõ lý do, không yêu cầu ceremony đầy đủ.

---

**Project:** Gundam Shop
**Generated:** 2026-09-12 01:27:29
**Category:** E-commerce

---

## Global Rules

### Color Palette

| Role | Hex | CSS Variable |
|------|-----|--------------|
| Primary | `#1C1917` | `--color-primary` |
| On Primary | `#FFFFFF` | `--color-on-primary` |
| Secondary | `#44403C` | `--color-secondary` |
| Accent/CTA | `#A16207` | `--color-accent` |
| Background | `#FAFAF9` | `--color-background` |
| Foreground | `#0C0A09` | `--color-foreground` |
| Muted | `#E8ECF0` | `--color-muted` |
| Border | `#D6D3D1` | `--color-border` |
| Destructive | `#DC2626` | `--color-destructive` |
| Ring | `#1C1917` | `--color-ring` |

**Color Notes:** Premium dark + gold accent [Accent adjusted from #CA8A04 for WCAG 3:1]

### Typography

- **Heading Font:** Rubik
- **Body Font:** Nunito Sans
- **Mood:** ecommerce, clean, shopping, product, retail, conversion
- **Google Fonts:** [Rubik + Nunito Sans](https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Rubik:wght@300;400;500;600;700&display=swap)

**CSS Import:**
```css
@import url('https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;500;600;700&family=Rubik:wght@300;400;500;600;700&display=swap');
```

### Spacing Variables

| Token | Value | Usage |
|-------|-------|-------|
| `--space-xs` | `4px` / `0.25rem` | Tight gaps |
| `--space-sm` | `8px` / `0.5rem` | Icon gaps, inline spacing |
| `--space-md` | `16px` / `1rem` | Standard padding |
| `--space-lg` | `24px` / `1.5rem` | Section padding |
| `--space-xl` | `32px` / `2rem` | Large gaps |
| `--space-2xl` | `48px` / `3rem` | Section margins |
| `--space-3xl` | `64px` / `4rem` | Hero padding |

### Shadow Depths

| Level | Value | Usage |
|-------|-------|-------|
| `--shadow-sm` | `0 1px 2px rgba(0,0,0,0.05)` | Subtle lift |
| `--shadow-md` | `0 4px 6px rgba(0,0,0,0.1)` | Cards, buttons |
| `--shadow-lg` | `0 10px 15px rgba(0,0,0,0.1)` | Modals, dropdowns |
| `--shadow-xl` | `0 20px 25px rgba(0,0,0,0.15)` | Hero images, featured cards |

---

## Component Specs

### Buttons

```css
/* Primary Button */
.btn-primary {
  background: #A16207;
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 200ms ease;
  cursor: pointer;
}

.btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

/* Secondary Button */
.btn-secondary {
  background: transparent;
  color: #1C1917;
  border: 2px solid #1C1917;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  transition: all 200ms ease;
  cursor: pointer;
}
```

### Cards

```css
.card {
  background: #FAFAF9;
  border-radius: 12px;
  padding: 24px;
  box-shadow: var(--shadow-md);
  transition: all 200ms ease;
  cursor: pointer;
}

.card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}
```

### Inputs

```css
.input {
  padding: 12px 16px;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 200ms ease;
}

.input:focus {
  border-color: #1C1917;
  outline: none;
  box-shadow: 0 0 0 3px #1C191720;
}
```

### Modals

```css
.modal-overlay {
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}

.modal {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: var(--shadow-xl);
  max-width: 500px;
  width: 90%;
}
```

---

## Style Guidelines

**Style:** Vibrant & Block-based

**Keywords:** Bold, energetic, playful, block layout, geometric shapes, high color contrast, duotone, modern, energetic

**Best For:** Startups, creative agencies, gaming, social media, youth-focused, entertainment, consumer

**Key Effects:** Large sections (48px+ gaps), animated patterns, bold hover (color shift), scroll-snap, large type (32px+), 200-300ms

### Page Pattern

**Pattern Name:** Product Review/Ratings Focused

- **Conversion Strategy:** User-generated content builds trust. Show verified purchases. Filter by rating. Respond to negative reviews.
- **CTA Placement:** After reviews summary + Buy button alongside reviews
- **Section Order:** 1. Hero (product + aggregate rating), 2. Rating breakdown, 3. Individual reviews, 4. Buy/CTA

---

## Anti-Patterns (Do NOT Use)

- ❌ Flat design without depth
- ❌ Text-heavy pages

### Additional Forbidden Patterns

- ❌ **Emojis as icons** — Use SVG icons (Heroicons, Lucide, Simple Icons)
- ❌ **Missing cursor:pointer** — All clickable elements must have cursor:pointer
- ❌ **Layout-shifting hovers** — Avoid scale transforms that shift layout
- ❌ **Low contrast text** — Maintain 4.5:1 minimum contrast ratio
- ❌ **Instant state changes** — Always use transitions (150-300ms)
- ❌ **Invisible focus states** — Focus states must be visible for a11y

---

## Pre-Delivery Checklist

Before delivering any UI code, verify:

- [ ] No emojis used as icons (use SVG instead)
- [ ] All icons from consistent icon set (Heroicons/Lucide)
- [ ] `cursor-pointer` on all clickable elements
- [ ] Hover states with smooth transitions (150-300ms)
- [ ] Light mode: text contrast 4.5:1 minimum
- [ ] Focus states visible for keyboard navigation
- [ ] `prefers-reduced-motion` respected
- [ ] Responsive: 375px, 768px, 1024px, 1440px
- [ ] No content hidden behind fixed navbars
- [ ] No horizontal scroll on mobile
