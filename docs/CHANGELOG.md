# Research Partner Program — Change Log

Site: https://www.oligopolypeptides.com (WordPress.com-connected Jetpack site, blog ID `254585378`)
Theme: **Hello Elementor** · Builder: **Elementor** · Status of this work: **DRAFT ONLY — nothing published**

---

## What was created

Three new WordPress **pages, all saved as drafts**. No existing page, product, setting, or
plugin was modified.

| ID | Title | Slug | Status |
|----|-------|------|--------|
| 3500 | OligoPoly Research Partner Program | `research-partner-program` | draft |
| 3498 | Research Partner Program Terms | `research-partner-program-terms` | draft |
| 3499 | Partner Compliance Rules | `research-partner-compliance-rules` | draft |

Once published these resolve to:

- `/research-partner-program/`
- `/research-partner-program-terms/`
- `/research-partner-compliance-rules/`

No slug collisions: a search of existing pages found nothing occupying these slugs.

## How it was built

Everything lives **inside the three pages' own content** as Gutenberg blocks. Nothing was
added to the theme, no child theme was created, no plugin was installed or activated, and
no file on the server was edited. That means the work survives theme and plugin updates,
and removing the pages removes 100% of the change.

- **Page structure** — `core/html` blocks holding semantic `<section>` markup.
- **Application form** — a `jetpack/contact-form` block with 22 fields. Jetpack is already
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
| 10 | jetpack/contact-form | The 22-field application form |
| 11 | core/html | Consent footnote + form-zone closing tag |
| 12 | core/html | FAQ schema (JSON-LD) + analytics |

## SEO

Set via Rank Math post meta (Rank Math is the active SEO plugin; Yoast is installed but
inactive and was not touched):

- **Program page title:** `Research Partner Program | OligoPoly Laboratories`
- **Program page description:** Earn a 10% pilot commission connecting qualified research
  communities with OligoPoly laboratory research-use-only products. 60-day attribution,
  monthly payouts. Applications reviewed manually.
- Terms and Compliance pages have their own titles and descriptions.

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
wordpress/research-partner-program.tracking.html  FAQ schema + analytics blocks
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
