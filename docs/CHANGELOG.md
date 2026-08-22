# Research Partner Program — Change Log

Site: https://www.oligopolypeptides.com (WordPress.com-connected Jetpack site, blog ID `254585378`)
Theme: **Hello Elementor** · Builder: **Elementor** · Status: **PUBLISHED 2026-08-20** (approved by the site owner)

---

## What was created

Three new WordPress pages. No existing page, product, setting, or plugin was modified.

| ID | Title | URL | Status |
|----|-------|-----|--------|
| 3500 | OligoPoly Research Partner Program | `/research-partner-program/` | published |
| 3498 | Research Partner Program Terms | `/research-partner-program-terms/` | published |
| 3499 | Partner Compliance Rules | `/research-partner-compliance-rules/` | published |

All three returned HTTP 200 after publishing and were verified live.

### Copy reconciliation (2026-08-20)

The approved launch package (`OligoPoly_Affiliate_Program_Launch_Package.docx`) arrived after
the first draft was built, and the pages were reconciled against it before publishing:

- **Program Terms** rewritten from the package's §2.1–2.13 — a materially more complete
  document than the first draft, including the real "as is" / liability / indemnity language.
  Only counsel-owned fields remain bracketed.
- **Compliance Rules** rewritten from the package's §4 — five non-negotiables, approved
  language-pattern table, disclosure examples, channel-rules table, content review checklist,
  and enforcement ladder.
- **FAQ answers** aligned to package wording, including code-precedence in attribution, the
  exact Net Eligible Product Revenue definition, and "earnings are not guaranteed".
- **Application form** extended to the package's question set: payout country, channel
  tenure, share of research content, and the package's own option lists and phrasing.
- **Benefits, eligibility, compliance lead-in, and final CTA** aligned to package copy.

Deliberately kept from the brief rather than the package, because the brief overrides:
**"OligoPoly Laboratories"** naming (the package says "OligoPoly Labs"), the hero eyebrow /
headline / CTA labels, and the 10-question FAQ set.

No slug collisions: a search of existing pages found nothing occupying these slugs.

## How it was built

Everything lives **inside the three pages' own content** as Gutenberg blocks. Nothing was
added to the theme, no child theme was created, no plugin was installed or activated, and
no file on the server was edited. That means the work survives theme and plugin updates,
and removing the pages removes 100% of the change.

- **Page structure** — `core/html` blocks holding semantic `<section>` markup.
- **Application form** — a `jetpack/contact-form` block with 24 fields. Jetpack is already
  active on this site, so no new form plugin was needed.
- **Styling** — one `<style id="opp-styles">` block at the top of the program page. Every
  selector is prefixed `.opp-` or scoped under `.opp-form-zone`, so it cannot leak into
  other pages. It only loads on this page because it ships inside this page's content.
- **Scripts** — one `<script id="opp-analytics">` block at the bottom of the program page.

### Page composition (program page, 13 top-level blocks)

| Index | Block | Section |
|-------|-------|---------|
| 0 | core/html | Scoped stylesheet + `.opp-page-start` marker |
| 1 | core/html | Hero |
| 2 | core/html | Program at a glance (6 stat cards) |
| 3 | core/html | Partner benefits (6 cards) |
| 4 | core/html | How it works (4 steps) |
| 5 | core/html | Eligibility (Who should apply / Not a fit) |
| 6 | core/html | Compliance rules |
| 7 | core/html | FAQ (10 questions) |
| 8 | core/html | Final CTA |
| 9 | core/html | Application intro + form-zone opening tag |
| 10 | jetpack/contact-form | The 24-field application form |
| 11 | core/html | Consent footnote + form-zone closing tag |
| 12 | core/html | FAQ schema (JSON-LD) + analytics |

## SEO

Rank Math is the active SEO plugin (Yoast is installed but inactive and was not touched).

**Meta description — working.** Each page's excerpt is set, and Rank Math renders it as the
meta description. Live on the program page:

> Apply to the OligoPoly Research Partner Program: a closed pilot paying a 10% commission for
> connecting qualified research communities with laboratory research-use-only products.

**Title — needs a manual fix.** `rank_math_title` is **not writable** through the
WordPress.com MCP connection: the value is silently dropped and the meta object comes back
without it (verified twice). Rank Math therefore falls back to its title template, producing:

> OligoPoly Research Partner Program | Research Peptides & Laboratory Compounds | OligoPoly Laboratories

That is 103 characters and will be truncated in search results. Fix it in **wp-admin → edit
page → Rank Math → Edit Snippet → Title**:

> Research Partner Program | OligoPoly Laboratories

Canonical URLs are left to the site's existing canonical logic (the OligoPoly SEO
Remediation plugin enforces `www` canonicals sitewide) — no canonical tags were hardcoded
and no existing canonical was altered. The pages are ordinary published pages once
approved, so they become sitemap-eligible automatically.

**Schema:** `FAQPage` JSON-LD on the program page only, with all 10 entries matching the
visible FAQ text verbatim. The site's existing sitewide schema (`WebSite`, `WebPage`,
`Place`, `PostalAddress`, `SearchAction`, `ImageObject`) is untouched. **No Product schema**
was added — this is an informational page. The build script fails if the schema count ever
drifts from the number of visible FAQ items.

## Analytics

The site already runs Google Tag Manager (container `GTM-PFX8385C`). The page pushes into
the **existing** `window.dataLayer` — no new GA4, Meta, or other tag was installed, and no
existing analytics configuration was changed.

| Event name | Fires when |
|------------|-----------|
| `affiliate_page_view` | Page loads |
| `affiliate_cta_click` | "Apply to Partner" clicked (either instance) |
| `affiliate_application_started` | First focus into any form field |
| `affiliate_application_submitted` | Page loads with Jetpack's `contact-form-sent` marker |
| `affiliate_terms_click` | Program Terms link clicked |
| `affiliate_compliance_click` | Compliance Rules link clicked |

Every event also carries `page_area: "research_partner_program"` so it can be isolated in
GTM. **These events will not appear in GA4 until someone creates matching triggers/tags in
the GTM container** — that is a deliberate boundary, since editing the container was out of
scope.

## Assets

The hero uses the logo already in the media library at
`/wp-content/uploads/2026/07/cropped-Logo3-3.png` (512×341, 41 KB). No new upload was made,
and the original file is untouched. The `<img>` carries explicit `width`/`height` to
prevent layout shift, plus `loading="eager"` and `decoding="async"` since it is above the
fold.

## Accessibility and performance

- One `<h1>`; sections descend `h1 → h2 → h3` in order.
- Every `<section>` is labelled via `aria-labelledby` pointing at its heading.
- FAQ uses native `<details>`/`<summary>` — keyboard operable (Enter and Space both toggle)
  with no JavaScript required.
- Visible focus ring: 2px solid violet with a 3px offset on all interactive elements.
- `prefers-reduced-motion: reduce` disables all transitions and the hover lift.
- All body, heading, muted, link, and input text meets WCAG AA (≥4.5:1). Button labels use
  a dedicated `--opp-violet-btn: #ae3ada` so white bold text stays at 4.75:1 — the brighter
  `#c24fef` is used only for borders, glows, and accents that carry no text.
- No web fonts, no external requests, no JS libraries. The CSS is ~12 KB inline.

## Files in this repository

```
build.js                                        Generates the page content + local preview from source
wordpress/research-partner-program.style.html   Scoped stylesheet block
wordpress/research-partner-program.sections.html  Hero → final CTA section blocks
wordpress/research-partner-program.form.html    Application form blocks
wordpress/research-partner-program.analytics.html  Analytics block (FAQ schema is generated)
wordpress/research-partner-program.page.html    BUILT: full page content (do not edit by hand)
wordpress/research-partner-program-terms.blocks.html      Program Terms page content
wordpress/research-partner-compliance-rules.blocks.html   Compliance Rules page content
preview/local-preview.html                      BUILT: standalone visual preview
preview/logo.png                                Local copy of the logo, for the offline preview
screenshots/                                    Desktop, tablet, and mobile captures
docs/CHANGELOG.md                               This file
docs/ROLLBACK.md                                How to undo everything
```

Run `node build.js` after editing any source file to regenerate the page content and the
preview. The build validates the analytics JavaScript and checks the FAQ schema against the
visible FAQ.

---

## 2026-08-22 — Homepage bundle section layout fix

Separate from the Research Partner Program work. Page **381** (`home`, the front
page) was updated to fix the blank right-hand column on the "Build Your Research
Bundle" section.

Two CSS rules were added to the page's existing inline
`<style id="op9-home-direct-20260820">` block:

```css
#op9-home p:empty{display:none}
#op9-home .op9-feature>br,#op9-home .op9-offers>br,#op9-home .op9-grid-4>br,#op9-home .op9-access-grid>br,#op9-home .op9-actions>br,#op9-home .op9-product-grid>br,#op9-home .op9-section-head>br{display:none}
```

They neutralise the empty `<p></p>` elements `wpautop()` injects into the page's
hand-written markup, which were taking grid tracks and forcing the promo image
and copy card to stack in the left column.

Nothing else on page 381 changed — the stylesheet was verified byte-identical to
the live version apart from those two lines, and the markup's visible text and
URLs were verified unchanged. No product data, cart, checkout, WooCommerce, or
theme setting was touched.

Full write-up: [HOMEPAGE-BUNDLE-LAYOUT.md](HOMEPAGE-BUNDLE-LAYOUT.md)
Page source of record: [`wordpress/home.page.html`](../wordpress/home.page.html)
