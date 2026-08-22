# /research-stacks/ redesign

Page **3487**, canonical URL `/research-stacks/` — unchanged.

**Status: built and validated, not yet deployed.** Deployment is two steps and
they must happen in order (see [Deployment](#deployment)).

---

## 1. Audit of the current implementation

| Aspect | Finding |
|---|---|
| Template | `page-template-default`, Hello Elementor. **Not** Elementor-built, **not** VillaTheme |
| Content | Plain Gutenberg blocks — paragraph, image, heading, buttons, repeated ×5 |
| Shortcodes | None on this page |
| Custom CSS/JS | None specific to this page |
| Spectra | `uagb-style-frontend-3487` is enqueued but the page uses core blocks |
| Data sources | None — every stack is hand-typed text plus a link to a product |
| Responsive | Inherits theme defaults; full-width 1600×1000 PNGs drive the height |
| Age gate | `opb-age-gate-css`, a fixed overlay at z-index 2147483647. **Untouched** |

Measured at 1440×900:

| | Before | After |
|---|---|---|
| Content height | 4544px | 3568px (−21%) |
| H1 | "Research Stacks" | "Build Your Own Research Stack" |
| Custom-stack CTA | 4118px down (**4.6 screens**) | builder at 500px (**0.6 screens**) |

The old page buried its most valuable action almost five screens down.

### The finding that changed the brief

The brief assumed a free-form configurator. The real commerce system is
**WooCommerce Mix and Match**, with three fixed containers:

| Bundle | Product ID | Size | Discount |
|---|---|---|---|
| 3 Vials | 3447 | exactly 3 | 5% |
| 6 Vials | 3450 | exactly 6 | 8% |
| 9 Vials | 3452 | exactly 9 | 10% |

Every container has `min_container_size == max_container_size`. **A selection of
4, 5 or 7 has nowhere valid to go** — MNM rejects it server-side. The builder
therefore enforces 3/6/9 and tells the user exactly how far off they are.

There is **no inquiry or bundle-review workflow** anywhere on the site, so
"Request This Bundle" as a non-cart action would have meant inventing a
non-functional button. Per your decision, the button posts to the real MNM
container and is labelled **"Add Bundle to Cart"**, which is what it does.

Eight eligible compounds, read live from container 3447: Tirzepatide 10 mg,
Cagrilintide 5 mg, NAD+ 500 mg, GHK-Cu 50 mg, Semaglutide 5 mg, Selank 5 mg,
Retatrutide 5 mg, Retatrutide 20 mg.

---

## 2. Files changed

| File | Role |
|---|---|
| `wordpress/research-stacks-page.php` | The build. Registers `[opl_research_stacks]` |
| `wordpress/research-stacks-page.wpcode.txt` | Paste-ready copy (no `<?php`), 797 lines |
| `wordpress/research-stacks-page.test.js` | 49-assertion Playwright suite |
| `wordpress/research-stacks-page.stub.php` | WordPress/WooCommerce stubs for offline testing |
| `wordpress/backup/page-3487-research-stacks.BEFORE.html` | Exact block source, for rollback |
| `wordpress/backup/page-3487-rendered.BEFORE.html` | Rendered page, for reference |

Page 3487's content becomes the single shortcode. **Nothing else is touched** —
no global header, nav, footer, checkout, product data, URLs, analytics, SEO
metadata, schema, age gate, or other pages.

---

## 3. Before / after screenshots

- `screenshots/research-stacks-before-desktop.png`, `-before-mobile.png`
- `screenshots/research-stacks-after-desktop.png`, `-after-mobile.png`
- `screenshots/research-stacks-builder-selected.png` — six compounds selected

Before captures come from a local mirror with the age-gate overlay removed;
the stack PNGs did not load in that mirror, which is a mirror artifact, not a
fault on the live page.

---

## 4. Functional tests

`node wordpress/research-stacks-page.test.js` — **49/49 passing**.

Layout (desktop 1440, tablet 900, mobile 390): no horizontal scroll, exactly one
H1 with the required text, 8 compound cards, 4 curated cards, 4 trust items,
hero CTA above the fold on desktop, dock hidden until a selection exists and
shown/re-hidden correctly on mobile.

Builder: empty state, count updates, submit disabled until a valid size, gap
guidance ("Select 2 more to reach a 3-vial bundle"), enabled at 3, re-disabled
at 4, guidance to 6, remove control, no duplicate entries, clear-all, step
indicator advancing 1→2→3, ARIA live announcements.

Submission: posts to the **3-vial container URL** with exactly three
`mnm_quantity[ID]=1` fields plus `oplrs_size=3`. No console errors.

Keyboard: Space selects, focus stays on the control, 27 reachable controls.

Reduced motion: **0** elements with any animation or transition.

Two real defects were caught and fixed by this suite:
- `.pic` was `display:inline`, so the selection tick positioned 240px off-target
- `display:flex` on the mobile dock overrode the `hidden` attribute, so the dock
  showed at 0 items. Fixed with `#oplrs [hidden]{display:none!important}`

---

## 5. Performance

No libraries — CSS animations plus ~90 lines of vanilla JS, all inline, so no
extra requests. Hero image carries `fetchpriority="high"` and is **not**
lazy-loaded (LCP); every other image is `loading="lazy"`. All images have
explicit `width`/`height` and the hero panel has a fixed `aspect-ratio`, so
there is no layout shift. Existing optimized derivatives are reused via
`wp_get_attachment_image_url()`/`srcset`. Caching and CDN behaviour unchanged —
output is deterministic server-rendered HTML.

Full Lighthouse numbers are **not** included: they need the deployed page on
real hosting, and the isolated harness would give misleading figures. Worth
running after deployment.

---

## 6. Accessibility

- **WCAG 2.2 AA contrast verified on 19 text/background pairs** — lowest 9.1:1,
  against a 4.5:1 requirement
- Real `<input type="checkbox">` controls in a `<fieldset>` with a `<legend>`
- Selection communicated three ways: border, tick, **and the word "Selected"** —
  never colour alone
- 44×44px minimum hit areas; buttons at 52px, remove controls at 32px inside
  larger tiles
- `role="status"` + `aria-live="polite"` announces every count and state change
- Visible focus rings (`:focus-visible`, 3px cyan, 3px offset)
- Semantic heading order: one H1, H2 per section, H3 within panels
- Descriptive alt text on vial and stack images; decorative glow marked
  `aria-hidden`
- `prefers-reduced-motion` removes all motion
- Remove buttons carry per-product accessible names

---

## 7. Rollback

**Snippet:** toggle it Inactive in WPCode. The page falls back to whatever its
content holds.

**Page:** restore the block source from
`wordpress/backup/page-3487-research-stacks.BEFORE.html` into page 3487, or use
a WordPress revision. That file is the exact pre-change content.

Both are independent, and neither touches anything else on the site.

---

## 8. Limitations and data dependencies

1. **Fixed bundle sizes.** Only 3, 6 and 9 are purchasable. Not a UI choice —
   it is how the MNM containers are configured. Changing it means reconfiguring
   the products.
2. **Eligibility comes from container 3447.** Add or remove a child there and
   the builder follows automatically. The ID list in the file is a fallback used
   only if the MNM API cannot be read, and even then names, prices, stock,
   images and links still come from WooCommerce.
3. **Out-of-stock and unpublished compounds are dropped** from the grid rather
   than shown disabled, so a customer can never build an unfulfillable bundle.
4. **COA claims are deliberately soft.** The trust strip says certificates are
   provided *where available*, and each card links to its product record rather
   than asserting a COA exists. Per-product COA state is not exposed in a form
   this page can read, so a stronger claim would not be supportable.
5. **No Product/Offer schema added.** The page lists compounds but is not itself
   a product page; adding Offer markup would not qualify.
6. **Curated stack "Included" lists** are read from each stack product's own MNM
   contents. If a stack is not an MNM product, the line is omitted rather than
   guessed.
7. **Cross-browser testing** covered Chromium only. Firefox and WebKit are not
   installed here. The CSS and JS use long-established features
   (grid, flexbox, `aspect-ratio`, `:focus-visible`, `matchMedia`).
8. **Lighthouse** not run — see Performance.
