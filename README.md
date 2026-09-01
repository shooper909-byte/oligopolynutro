# OligoPoly Research Partner Program

Source for the Research Partner Program pages on
[oligopolypeptides.com](https://www.oligopolypeptides.com).

**Status: published 2026-08-20.** Copy reconciled against the approved launch package.

## What this is

Three WordPress pages, live:

| ID | Page | URL |
|----|------|-----|
| 3500 | OligoPoly Research Partner Program | `/research-partner-program/` |
| 3498 | Research Partner Program Terms | `/research-partner-program-terms/` |
| 3499 | Partner Compliance Rules | `/research-partner-compliance-rules/` |

The whole implementation lives inside those pages' content as Gutenberg blocks — no theme
changes, no new plugins, no server files. It survives theme and plugin updates, and
deleting the pages removes every trace.

## Read these first

- **Published handoff page** — https://claude.ai/code/artifact/8fcc7788-4b94-494c-b0fc-363f239d7ca2
  (source: [docs/handoff.html](docs/handoff.html); republish that file to update the same URL)
- **[docs/OPEN-ITEMS.md](docs/OPEN-ITEMS.md)** — what still needs a human (counsel fields,
  GTM triggers, SEO titles)
- **[docs/NAVIGATION.md](docs/NAVIGATION.md)** — exact snippet edit to add the nav link
- **[docs/CHANGELOG.md](docs/CHANGELOG.md)** — everything that was created and how
- **[docs/TEST-RESULTS.md](docs/TEST-RESULTS.md)** — responsive, accessibility, contrast,
  and analytics results
- **[docs/ROLLBACK.md](docs/ROLLBACK.md)** — how to undo it

## Working on it

```sh
node build.js
```

Regenerates two things from the `wordpress/*.html` source blocks:

- `wordpress/research-partner-program.page.html` — full page content, ready to paste into
  the WordPress code editor
- `preview/local-preview.html` — standalone preview for visual QA (open it in a browser)

The build also syntax-checks the analytics JavaScript and fails if the FAQ schema stops
matching the visible FAQ.

Edit the source blocks, never the two generated files:

```
wordpress/research-partner-program.style.html      scoped stylesheet
wordpress/research-partner-program.sections.html   hero through final CTA
wordpress/research-partner-program.form.html       application form
wordpress/research-partner-program.analytics.html  analytics (FAQ schema is generated)
```

## Product COA banner

A separate, self-contained piece of work: COA availability shown everywhere a
product appears — a purple banner between the title and price on single product
pages, and a compact pill on the product cards in shop and category listings.
Applies to the whole catalogue; exclude a product by SKU. **Live since 2026-09-01.**

- **Published handoff page** — https://claude.ai/code/artifact/a92860d7-3738-48a4-a17e-9738cf8e6e48
  (source: [docs/coa-banner-handoff.html](docs/coa-banner-handoff.html); republish that file
  to update the same URL)
- **[docs/COA-BANNER.md](docs/COA-BANNER.md)** — install, verification, rollback, and
  the product list
- [`wordpress/product-coa-banner.php`](wordpress/product-coa-banner.php) — the snippet
  to install (WPCode or child theme)
- `node build-coa-banner.js` — regenerates `preview/coa-banner-preview.html` from that
  snippet for visual QA

Display only. No product, cart, or WooCommerce data is written.

## Collection & bundle contents

Bundles and collections showed nothing about what they hold. This renders the real
contents — read from each product's own Mix and Match configuration, never inferred —
on the product page and on listing cards.

- **[docs/COLLECTION-CONTENTS.md](docs/COLLECTION-CONTENTS.md)** — install, what the
  live catalogue actually holds, and **four data problems it exposed** (two
  collections have no contents configured at all)
- [`wordpress/collection-contents.php`](wordpress/collection-contents.php) — a second,
  independent snippet

## Ground rules this work respected

No product data, cart, checkout, payment, shipping, tax, WooCommerce, customer-account,
canonical, sitewide-schema, analytics-configuration, or email-automation setting was
touched. No medical, human-use, dosing, or self-administration content appears anywhere —
the pages state research-use-only positioning throughout and prohibit partners from
implying otherwise.
