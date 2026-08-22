# Homepage — "Build Your Research Bundle" empty right column

**Page:** `home` (ID 381) — https://www.oligopolypeptides.com/
**Section:** `<section class="op9-section" aria-labelledby="op9-bundle-title">`
**Fix:** [`wordpress/home.style-patch.css`](../wordpress/home.style-patch.css)

## Symptom

The bundle section rendered the promo image and the copy card stacked on top of
each other in the left half of the shell, leaving the entire right-hand column
blank.

## Cause

It is not a width problem — the section is already full width. `#op9-home
.op9-shell` resolves to 1100px at a 1440px viewport, and `.op9-feature` is
already a two-column grid:

```css
#op9-home .op9-feature{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(320px,.75fr);gap:26px;align-items:stretch}
```

The page content stored in WordPress is clean, well-formed HTML. WordPress runs
`wpautop()` over it on output, and because the markup is indented and
hand-written, `wpautop` converts the newlines between block tags into empty
`<p></p>` elements. Those injected paragraphs are real DOM children of the grid,
so they take grid tracks:

| child | grid position |
|-------|---------------|
| `a.op9-feature-media` | col 1, row 1 |
| **injected `<p></p>`** | **col 2, row 1** |
| `div.op9-feature-copy` | col 1, row 2 |
| **injected `<p></p>`** | **col 2, row 2** |

The two injected paragraphs occupy the right column, which is exactly the blank
area, and force the real content to stack in column 1.

The same injection adds a phantom 4th item to the `.op9-offers` grid (a dead row
under the 3/6/9 vial tiles) and a stray `<br>` to `.op9-actions`.

## Fix

Two rules appended to the page's inline `<style id="op9-home-direct-20260820">`
block. They neutralise the injected elements without editing the section markup,
so the layout survives future edits and re-saves in the WordPress editor:

```css
#op9-home p:empty{display:none}
#op9-home .op9-feature>br,#op9-home .op9-offers>br,#op9-home .op9-grid-4>br,#op9-home .op9-access-grid>br,#op9-home .op9-actions>br,#op9-home .op9-product-grid>br,#op9-home .op9-section-head>br{display:none}
```

No markup, copy, product data, or responsive breakpoint was changed. The
`@media(max-width:1024px)` rule still collapses `.op9-feature` to a single
column on tablet and mobile.

## Verification

Measured in headless Chromium at a 1440px viewport against a local mirror of the
rendered page (`.op9-feature` children, x / width / height in px):

| child | before | after |
|-------|--------|-------|
| `a.op9-feature-media` | x=170 w=671 h=330 | x=170 w=671 h=530 |
| injected `<p>` | x=867 w=403 h=316 | hidden (0×0) |
| `div.op9-feature-copy` | x=170 w=671 h=463 | x=867 w=403 h=530 |
| injected `<p>` | x=867 w=403 h=449 | hidden (0×0) |

Section height drops from 964px to 675px, and the two cards together span the
full 1100px shell with the designed 26px gap.

Re-measured against the live page after the change was applied:

| viewport | `.op9-feature-media` | `.op9-feature-copy` | injected `<p>` |
|----------|----------------------|---------------------|----------------|
| 1440px | x=170 w=671 h=530 | x=867 w=403 h=530 | hidden |
| 1024px | x=132 w=760 h=305 | x=132 w=760 h=394 | hidden |
| 520px | x=23 w=474 h=280 | x=23 w=474 h=575 | hidden |

The 1024px and 520px rows are the intended single-column collapse from the
existing `@media(max-width:1024px)` rule — unchanged by this fix. The
`.op9-offers` grid shows exactly 3 tiles at every width, with no phantom 4th.

`screenshots/homepage-bundle-before.png` is the broken layout (captured against a
local mirror, so the promo image is a placeholder there).
`screenshots/homepage-bundle-after.png` is the live section after the fix.
