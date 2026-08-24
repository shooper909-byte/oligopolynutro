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

---

## 2026-08-22 — Homepage card grids and product-card fact bullets

### Applied live (page 381)

The Research Stacks and Research Access grids were both shredded by `wpautop()`.
Each card was an `<a class="...-card">` wrapping block-level children (`<h3>`, and
a `<span>` containing an `<h3>`). `wpautop` inserts `<p>`/`<br>` around those
blocks, which places block elements inside an anchor — a formatting element — so
the HTML parser's adoption agency algorithm cloned the `<a>` and split each card
across sibling nodes.

The Research Stacks grid rendered **8 children instead of 4**: one partial card
plus loose `<p>`/`<h3>` fragments each carrying a duplicated `.op9-stack-card`
anchor, and every card had lost its "Explore the stack" link. Research Access
broke identically.

Both grids were rebuilt as block containers, with the anchor wrapping only the
title text and a stretched `::after` keeping the whole card clickable. Verified
live: 4 and 3 clean grid children respectively at 1440px, 1024px and 520px, all
titles and CTAs present, whole-card click targets confirmed at every corner.

`wordpress/wpautop_sim.py` was added — a port of WordPress's `wpautop()` used to
verify the fix offline before touching the live page. It reproduces the previous
live output exactly and confirms the new markup survives intact.

### Delivered, not applied

Fact bullets with highlighted keywords for every product and stack card:
`wordpress/product-card-facts.{php,html,css}`.

These could not be applied. No product grid on the site is stored in page
content — `/research/`'s cards come from a PHP snippet that overrides page 3038's
stored content, and the homepage and catalog grids are PHP/WooCommerce. The
WordPress connection used here reaches pages, posts and media only.

Full write-up, including where each grid lives:
[PRODUCT-CARD-FACTS.md](PRODUCT-CARD-FACTS.md)

---

## 2026-08-23 — Certificate Library redesign, `/research-peptides-with-coa/` (page 1652)

**Built and tested. NOT deployed.** Staged for review per the brief; see
`docs/COA-LIBRARY.md` for the full report.

Rebuilds the page as a QR-first batch finder: one H1, a labelled batch field and
Verify button above the fold on a 390px phone, server-rendered results,
`?batch=` deep links, an 8-item accordion replacing a 2,600-word essay, and a
Quality Support path from every dead end. Page height drops 77% on desktop and
82% on mobile; two H1s become one; the cyan/teal palette becomes brand violet.

**The page publishes no certificate records, because the site has none.** Both
COA post types are empty, both lookup endpoints return nothing, and the media
library holds zero PDFs across 692 attachments. The build reads
`op_coa_record` and renders whatever is there — currently an honest "records are
being published" state. The mockup's BPC-157 / TB-500 / KPV cards were not
shipped; publishing them would have invented batch numbers, dates and statuses.

Open question for the owner: the supplied verification graphic has a specimen
certificate, a signature, purity figures and the strap lines "All products are
3rd-party tested" and "TRUSTED FOR SAFETY" baked into the image, none of which
the current records support. Captioned and switchable, but it needs a decision —
`docs/COA-LIBRARY.md` §9a.

- `wordpress/coa-library-page.php` — the build, `[opl_coa_library]`
- `wordpress/coa-library-page.wpcode.txt` — paste-ready
- `wordpress/coa-library-page.test.js` — 138/138 passing, lowest contrast 8.42:1
- `wordpress/backup/page-1652-*.BEFORE.html` — rollback

**Update, same day —** rather than leave the library bare, added two blocks
built from symbols rather than data: a batch-ID shape guide (`OP-######-XXX`)
under the search box, and five specimen cards (one per document status,
doubling as the status legend) whose every field is a placeholder symbol
(`Compound name`, `## mg`, `DD Month YYYY`). Banded SPECIMEN — NOT A RECORD,
linked to nothing, outside the searchable record set, and self-removing once a
real record is published. `opl_cl_specimens()` switches them off.

**Update, same day —** the verification graphic question resolved as *rebuild*.
Attachment 3544 carried a specimen COA with invented purity results, a
conclusion, a report date and a signature, plus six separate strap lines
("All products are 3rd-party tested", "TRUSTED FOR SAFETY", "View real test
results anytime, anywhere"). None were croppable — the fabricated results table
is the centre of the composition — so the useful half, the vial-to-certificate
batch match, was rebuilt from scratch as
`assets/coa-figure/batch-match-diagram.html`. Both batch IDs read
`OP-######-XXX`; there is no result, date, laboratory, signature or claim in it.
1,930 KB PNG becomes a 55 KB WebP. The snippet finds it by filename, and falls
back to no figure at all rather than to 3544, so a missed lookup cannot
reinstate the original. Attachment 3544 is left in the library, just unreferenced
by this page.

**DEPLOYED 2026-08-23.** Snippet installed, page 1652 content replaced with the
shortcode block, figure uploaded as attachment 3546. Live check found two
defects that only appear alongside the site's other snippets: a site-wide FAQ
schema still describing the three questions the redesign removed (now stripped
on this page only), and three BreadcrumbList graphs, two sharing an `@id` with
conflicting names, plus duplicate robots/canonical tags on `?batch=` lookups.
The page now emits no schema of its own and routes noindex through Rank Math's
filters. Verified live: one H1, zero wpautop mangling, 0 PHP errors, no overflow
at 320/390/1440, `/coa/` redirect intact.

---

## 2026-08-23 — Purchase controls on product cards

**Built and tested (37/37). Not deployed — one WPCode paste.** See
`docs/PRODUCT-CART-BUTTONS.md`.

"Add to cart on all products" turned out to be constrained by a deliberate
setting, not missing data. The 10 individual compounds carry Mix and Match's
"not sold separately" flag: they are priced ($64.99–$123.49) but WooCommerce
refuses to sell them alone, and their product pages render no add-to-cart form.
Verified by posting `?add-to-cart=447` — the cart stayed empty.

So the rule is: sell from the card when exactly one valid configuration exists,
send the customer to configure when it does not, never render a control that
would fail.

- 8 single-compound kits (1 child, min=max=6) → real one-click **Add to Cart**
- 4 stacks + 3 build-your-own bundles → **Select Options** to the product page
- 10 compounds → **Available in Kits**, linking to that compound's own kit

Controls are plain form POSTs — no JavaScript, so nothing can be half-wired.
Each card is matched by its own permalink via `url_to_postid()`, and the suite
re-parses the output to prove no control lands on the wrong product.

Note: I first read the Store API's empty prices as "no pricing set". That was
wrong — the API withholds prices from guests. A real cart returned $294.47.

Also found, not fixed: category archive pages render no products at all
(`woocommerce-no-products-found`), and one homepage card links to
`?post_type=product&p=447` instead of its permalink.

- `wordpress/product-cart-buttons.php` / `.wpcode.txt` / `.test.php`

**Update, same day —** both follow-ups fixed.

*Category archives.* Diagnosed and it was not what it looked like: not a broken
query, not stale term counts. 14 of the 15 sellable containers had **no product
category at all**, so they showed only under Uncategorized; meanwhile the 10
compounds that do carry categories are correctly excluded from listings because
they are not sold separately. Categories held the products that must not be
listed and excluded the ones that can be bought. Confirmed by the API split:
`wp/v2/product?product_cat=198` returns 7, the archive returns 0.
`wordpress/product-category-repair.php` is a one-time additive migration giving
each container the categories its contents justify — kits inherit from the
compound inside them, named stacks take their stated area (the neurocognitive
and regenerative stacks contain the *same* three compounds, so contents cannot
tell them apart), build-your-own bundles get umbrella only. 51/51 tests.
Previously-empty categories gain 6/4/3/2 products.

*Malformed homepage link.* `?post_type=product&#038;p=447` now rewrites to the
real permalink, but only when the id resolves to a published product. This also
gained a control — the homepage went 7 → 8, because the repaired link resolves.
The first regex used `#` as its delimiter, which the `&#038;` entity terminated;
caught by the suite. Cart-button suite now 40/40.

**Update, same day —** category cleanup (items 1-4).

*Footer.* `wordpress/footer-shop-links.php` repoints the Shop column from the
five retired supplement categories (all serving HTTP 410) to categories that
hold products. Candidates are verified at render time and skipped if empty, and
if none qualify the footer is left exactly as it was rather than emptied.
30/30 tests; 231-byte delta against the real homepage.

*Stacks shelf.* `/product-category/research-stacks/` is linked as "Research
Stacks" from 11 pages and was empty. The 4 curated stacks and 3 build-your-own
bundles now file there — 7 products. Single-compound kits do not: a six-pack of
one compound is a kit, not a stack.

*Deletion sweep.* The migration now deletes empty, unreferenced product
categories. 17 slugs are protected: the 7 it fills, the 5 retired ones serving
410 (deleting the term would downgrade a deliberate 410 to an accidental 404),
4 still linked from catalogue cards, and uncategorized. The sweep re-verifies
at run time rather than trusting a captured list — and that matters:
`research-catalog` reports `count = 0` but actually holds 7 products, so it is
skipped. 48 deleted, not the 49 the cached counts suggested. Tests cover the
stale-count case, child-term orphaning and the protection list. 93/93.

**Update, same day —** Add to Cart on the homepage.

The homepage grid is entirely individual compounds, none sellable alone, so it
had no Add to Cart. Each does have a dedicated single-compound kit with exactly
one valid selection, so the card now sells that kit in one click.

The button names what it adds and what it costs — **"Add 6-Vial Kit ·
$413.94"** — because the card shows $74.99 and an unlabelled "Add to Cart"
beside it would have been a trap. The card price gains a "per vial" suffix,
which also flatters the kit: $413.94 for six is $68.99 each.

Tests assert no compound id is ever posted to the cart, every kit button carries
a size and a price, and the compound price never appears on the button. 48/48.

**Update, same day —** the homepage now features kits instead of compounds.

Rather than explaining the compound/kit price gap in the button, each compound
card is retargeted to that compound's dedicated kit: heading, both links and
price become the kit's. Seven of the eight cards swap; the eighth is the
Neurocognitive stack, which keeps Select Options. The vial image and fact
bullets are kept — they describe the compound, and the kit is six vials of it.

Cards are rewritten as whole `<article>` blocks, so a heading can never describe
one product while a link points at another; a test re-parses every card and
fails if it holds two different product URLs. `opl_pcb_feature_kits()` returns
false to restore the previous "Add 6-Vial Kit · $413.94" behaviour, which stays
covered by the suite. 53/53.

Two test bugs of my own found and fixed along the way: a fixture with only 3 of
the 8 real kits (so four cards had nothing to swap to), and a substring check
for `opl-pcb-unit` that matched the stylesheet rather than the rendered span.
