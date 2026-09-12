# Assets — Design Tokens

## Source of truth

`assets/design-tokens.json` — 3 tầng theo skill `design-system`:

```
primitive (giá trị thô) → semantic (alias theo mục đích) → component (riêng từng component)
```

- Sửa token: sửa JSON → chạy `generate-tokens.cjs` → ra `assets/design-tokens.css`.
- CSS generated dùng cho: slides (Phase 6b import trực tiếp), tra cứu, audit.

## Tailwind v4 adaptation

Skill `design-system` viết cho Tailwind v3 (`tailwind.config.js`), nhưng project dùng
Tailwind **v4** (CSS-first). Quy ước:

- `@theme` trong `resources/css/app.css` là nguồn sinh utilities (`bg-bg-primary`, …).
- Giá trị trong `@theme` **mirror** tầng semantic của JSON (literal hex, không `var()`),
  vì Tailwind v4 cần giá trị tĩnh để sinh utilities + opacity modifiers + `.light-theme` overrides.
- Khi đổi token: cập nhật **cả 2 nơi** (JSON → regenerate CSS, và `@theme` trong app.css),
  rồi chạy `validate-tokens.cjs` để soát hardcode.

## Scripts (skill)

```bash
# Sync từ brand guidelines (skill brand)
node "<skill>/brand/scripts/sync-brand-to-tokens.cjs" [--dry-run]

# Generate CSS (skill design-system)
node "<skill>/design-system/scripts/generate-tokens.cjs" --config assets/design-tokens.json -o assets/design-tokens.css

# Validate hardcode (skill design-system)
node "<skill>/design-system/scripts/validate-tokens.cjs" --dir resources/views
```
