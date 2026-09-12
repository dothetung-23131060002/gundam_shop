# Component Specs — Gundam Shop

> Theo Component Spec Pattern của skill `design-system`.
> Token: `{primitive.*}` / `{semantic.*}` trong `assets/design-tokens.json`.
> Class triển khai trong `resources/css/app.css` (`@layer components`).

## Button Primary (`.btn-primary`)

| Property | Default | Hover | Active | Disabled |
|----------|---------|-------|--------|----------|
| Background | `{semantic.color.primary}` | `{semantic.color.primary-hover}` + glow | `primary-700` | muted, opacity 50% |
| Text | white | white | white | muted-fg |
| Border | none | none | none | none |
| Radius | `{component.button.radius}` (12px) | — | — | — |
| Padding | 12px 24px | — | — | — |
| Min touch target | 44px height | — | — | — |

## Button Secondary (`.btn-secondary`)

| Property | Default | Hover | Active | Disabled |
|----------|---------|-------|--------|----------|
| Background | transparent | transparent | `bg-tertiary` | transparent, opacity 50% |
| Text | `text-primary` | `{semantic.color.primary}` | `{semantic.color.primary}` | muted-fg |
| Border | 1px `{semantic.color.border}` | 1px `{semantic.color.primary}` | 1px `{semantic.color.primary}` | muted-border |

## Button Destructive (`.btn-outline-red`)

| Property | Default | Hover | Active | Disabled |
|----------|---------|-------|--------|----------|
| Background | transparent | `{semantic.color.danger}` | `danger-700` | transparent, opacity 50% |
| Text | `{semantic.color.danger}` | white | white | muted-fg |
| Border | 2px `{semantic.color.danger}` | 2px `{semantic.color.danger}` | — | muted-border |

## Badge

| Variant | Background | Text | Radius |
|---------|------------|------|--------|
| Info/new (`.badge-new`) | `{semantic.color.primary}` | white | 6px |
| Sale/danger (`.badge-sale`) | `{semantic.color.danger}` | white | 6px |
| Featured/gold (`.badge-featured`) | `{semantic.color.gold}` | `#0A0A0F` | 6px |
| Status pill | color/10 bg + matching border | matching color | 9999px |

Quy ước status pill: open/reserved → green/blue; needs-payment → warning + `animate-pulse`; refunded/cancelled/failed → red; converted/completed → green/gold.

## Card (`.product-card`, `.category-card`, `.admin-card`)

| Property | Default | Hover |
|----------|---------|-------|
| Background | `{component.card.bg}` | — |
| Border | 1px `{component.card.border}` | 1px `{semantic.color.primary}` |
| Radius | `{component.card.radius}` (16px) | — |
| Shadow | none | `0 8px 32px glow-blue` |
| Transform | none | `translateY(-4px)` 300ms |

## Input (`.form-input`)

| Property | Default | Focus | Error | Disabled |
|----------|---------|-------|-------|----------|
| Background | `{component.input.bg}` | — | — | muted, opacity 50% |
| Border | 1px `{component.input.border}` | 1px focus-ring + `0 0 0 3px glow-blue` | 1px danger | muted-border |
| Text | `text-primary` | — | — | muted-fg |
| Placeholder | `text-secondary` | — | — | — |
| Min height (mobile) | 44px | — | — | — |

## Alert

| Variant | Background | Border | Text |
|---------|------------|--------|------|
| Success (`.alert-success`) | primary/10 | `{semantic.color.primary}` | primary |
| Danger (`.alert-danger`) | danger/10 | `{semantic.color.danger}` | danger |

## States & Motion

- Transition chuẩn: `all 300ms ease` (`{primitive.duration.normal}`).
- Hover dùng transform/opacity (không animate width/height).
- Mọi destructive action có confirm dialog; toast/alert kèm cách khắc phục.
