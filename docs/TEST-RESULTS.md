# Test Results

Tested against `preview/local-preview.html` in headless Chromium (Playwright). The preview
uses the exact stylesheet, markup, schema, and analytics script that ship in the WordPress
page; it simulates only the site header/footer and the Jetpack form's server-rendered field
HTML, which WordPress produces at render time.

## Responsive layout

| Viewport | Horizontal overflow | Result |
|----------|--------------------|--------|
| Desktop 1440×900 | 0 px | Pass |
| Tablet 834×1112 | 0 px | Pass |
| Mobile 390×844 | 0 px | Pass |

Grids collapse 3→2→1 columns and the hero collapses to a single column with the emblem
above the headline. CTAs go full-width below 600px.

An earlier run showed 166 px of overflow on mobile. That was traced to the *simulated*
header in the preview harness, not to page content — the real site header has its own mobile
menu. Fixed in the harness; page content has never overflowed.

## Application form

- 24 fields render: 7 text/email/tel/url inputs, 6 textareas, 1 select, 1 checkbox group,
  and 5 required consent/confirmation checkboxes, plus the submit button.
- 17 fields are `required` and block submission while empty (verified by counting `:invalid`
  elements in an untouched form).
- Jetpack Forms performs server-side validation independently of the browser, so a
  hand-crafted POST cannot bypass the required fields.
- No bank account, routing, tax ID, SSN, or W-9 field exists anywhere in the form.

## Accessibility

| Check | Result |
|-------|--------|
| Exactly one `<h1>` | Pass (1) |
| Images with alt text | Pass (0 missing) |
| FAQ opens with Enter | Pass |
| FAQ closes with Space | Pass |
| Visible focus ring on tab | Pass (2px outline) |
| `prefers-reduced-motion: reduce` | Pass (transition-duration resolves to 0s) |
| Sections labelled by heading | Pass (`aria-labelledby` on every section) |

### Contrast (WCAG AA needs 4.5:1 for normal text, 3:1 for UI)

| Element | Colors | Ratio |
|---------|--------|-------|
| Body text | `#cbd5e1` on `#030712` | 13.56 |
| Headings | `#e2e8f0` on `#030712` | 16.33 |
| Muted text on panel | `#94a3b8` on `#0f172a` | 6.96 |
| Links / eyebrow | `#d8b4fe` on `#030712` | 11.39 |
| Input text | `#e2e8f0` on `#0b1220` | 15.19 |
| Button label (gradient start) | `#ffffff` on `#8b2fc9` | 6.27 |
| Button label (gradient end) | `#ffffff` on `#ae3ada` | 4.75 |
| Placeholder text | `#78889e` on `#0b1220` | 5.18 |

All pass AA. The button gradient originally ended at `#c24fef`, which gave white bold 16px
text only 3.71:1 — below AA, since 16px bold does not qualify as WCAG "large text". The
gradient end was darkened to `#ae3ada` (4.75:1). The brighter `#c24fef` is still used for
borders, glows, focus rings, and accent marks, none of which carry text.

## Analytics

Simulated a full user journey and read back `window.dataLayer`:

```
affiliate_page_view          (on load)
affiliate_cta_click          (Apply to Partner)
affiliate_terms_click        (Program Terms link)
affiliate_compliance_click   (Compliance Rules link)
affiliate_application_started (first form field focus)
```

`affiliate_application_submitted` fires on the post-submit page load, which Jetpack marks
with `contact-form-sent` in the URL. It could not be exercised locally because it requires a
real submission — **verify this one on staging after publishing.**

`affiliate_application_started` fires exactly once per page view, not on every field focus.

## Markup validation

- Analytics JavaScript parses cleanly (syntax-checked at build time).
- FAQ JSON-LD parses as valid JSON with 10 `Question` entries.
- Build fails if the schema question count ever diverges from the number of visible FAQ
  items, so the two cannot silently drift apart.
- WordPress accepted all three pages with **zero content warnings** — no blocks or HTML
  elements were stripped on save. Re-verified after each edit.
- Block integrity confirmed after every update: 13 top-level blocks on the program page with
  all 24 form fields intact.

## Links

Every internal link on the page points at a real destination:

| Link | Target | Status |
|------|--------|--------|
| Apply to Partner (hero + final CTA) | `#apply` | Anchor exists on page |
| Explore the Program | `#program` | Anchor exists on page |
| Partner Compliance Rules (3 places) | `/research-partner-compliance-rules/` | Live (page 3499) |
| Research Partner Program Terms (2 places) | `/research-partner-program-terms/` | Live (page 3498) |
| Privacy Policy (2 places) | `/privacy-policy/` | Live page (ID 744) |
| Contact (in both documents) | `/contact/` | Live page |

All three pages were published together, so every link resolves.

## Live verification after publishing (2026-08-20)

All three URLs fetched from production:

| URL | HTTP | Check |
|-----|------|-------|
| `/research-partner-program/` | 200 | hero, scoped CSS, form, analytics, FAQ schema all present |
| `/research-partner-program-terms/` | 200 | renders |
| `/research-partner-compliance-rules/` | 200 | renders |

On the live program page:

- Canonical resolves to `https://www.oligopolypeptides.com/research-partner-program/` — the
  site's existing canonical logic handled it; nothing was hardcoded.
- 10 `<details>` FAQ items render, matching the 10 `Question` entries in the schema.
- The Jetpack form renders with its nonce and 63 form controls (inputs, textareas, selects,
  and the checkbox groups expanded).
- Two `ld+json` blocks on the page: the site's existing `BreadcrumbList` and this page's
  `FAQPage`. **No Product schema, and no conflict** with existing sitewide schema.
- The site's shared header and footer wrap the page correctly — the stylesheet's
  `.site-main` neutralization works against the real theme chrome.

## Not testable in this environment

These need a check on staging or after publishing:

1. **End-to-end form submission** — that the email actually arrives at the destination
   address, and that the styled confirmation message renders.
2. **`affiliate_application_submitted`** firing on the post-submit page load.
3. **Akismet spam filtering** on the form. Akismet is active on the site and Jetpack Forms
   uses it automatically, but whether the API key is valid could not be confirmed remotely.
4. **Core Web Vitals** on production infrastructure.

Real theme chrome was verified after publishing (see above) and is no longer open.
