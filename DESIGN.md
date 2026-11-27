# DESIGN.md — frontend design system

Source of truth for the UI. Established 2026-11-27 under accepted ADR-0003 (Blade + Livewire + Alpine + Tailwind v4).

## Direction

Dealt style: **Achromatic ledger** (seed `1628694075`, kind `workspace`, light). Its move is kept intact: no colour in the chrome; ink figures on a quiet ground; one blue hue reserved for the main data element; status colour only where a state needs a signal.

Recorded redraws from the dealt sheet, with reasons:

- **Shell:** dealt icon rail → labelled 256px sidebar. The product has more than five destinations and contributors with mixed frontend experience; an unlabelled rail hides meaning. On phones the sidebar folds into a menu sheet with labelled rows, not an icon-only bar.
- **Typeface:** dealt remote Geist/Geist Mono → locally available system grotesk with tabular numerals. A remote font request is an external dependency on every page load, which conflicts with the low-bandwidth constraint. No remote font is loaded.
- **Ground:** dealt `#f8f8f8` → blue-grey `#f2f4f7`, one step from white, from the ledger/meter world named in the brief. Cards stay white.

Face chosen before markup: the school context strip (school name, type, identifiers) is the element only this product has; the current academic year / current period carries the single data hue. Both appear on the first screen.

## Tokens

Defined in `resources/css/app.css` as Tailwind v4 `@theme` custom properties; every component uses tokens, not raw values.

| Role | Token | Value |
| --- | --- | --- |
| Ground | `--color-ground` | `#f2f4f7` |
| Surface | `--color-surface` | `#ffffff` |
| Raised | `--color-raised` | `#fafafa` |
| Active nav | `--color-active` | `#e9eaec` |
| Border | `--color-border` | `#e6e6e6` |
| Row rule | `--color-rule` | `#f0f0f0` |
| Control border | `--color-control` | `#d1d5db` |
| Ink | `--color-ink` | `#141414` |
| Muted text | `--color-muted` | `#5f6368` |
| Primary action | `--color-primary` | `#141414` |
| Data hue | `--color-data` | `#1d5fbf` |
| Positive | `--color-positive` | `#247b50` |
| Negative | `--color-negative` | `#c4242a` |
| Warning | `--color-warning` | `#92400e` |

Radius: 8px cards, 6px controls. Spacing: 12px single gap, 24px canvas padding, 44px row height, 36px control height, 256px sidebar. Title 24px / 600; section 16px / 500; KPI 30px / 400; body 14px; small 12px. Numerals are tabular everywhere they appear.

## Components

`resources/views/components/` holds the reusable set: application shell (sidebar, mobile sheet, header, breadcrumbs, title row), button, input, select, card, panel, KPI strip, status pill, table, empty state, error block, skeleton, and shared SVG icons. States (hover, focus-visible, disabled, loading, empty, error) are part of each component, not additions.

## Rules

- Colour budget: under 1% of pixels. No colour on navigation, headings, cards or KPI tiles. The data hue marks the current period only.
- No gradients, glows, glass, emoji, icon-in-tinted-square KPIs, sparklines, zebra rows, uppercase tracked headers or shadows on resting surfaces. A shadow is only for the mobile menu sheet and dialogs.
- One h1 per screen; sentence case; no eyebrow labels; section headings name their content.
- Real values only. Missing values render as a grey dash; real zeros print as zero. Demo values are isolated behind an explicit "Demo data" marker and are never presented as school records.
- Loading uses flat grey bars at the final layout size; errors sit inside the affected block with a retry action; empty sections keep their frame and state the next step.
- Focus is visible on every interactive element. Touch targets are at least 44px on phones. Reduced-motion preference disables transitions.

## Verification

App-screen lint and inspector (`design-taste-frontend`) are run against the built shell at 1440, 768 and 390px; results and known deviations are recorded in `docs/frontend/progress.md`. Browser QA evidence belongs to the QA workflow, not to a claim in this file.
