# OligoPoly Research Partner Program

Source for the Research Partner Program pages on
[oligopolypeptides.com](https://www.oligopolypeptides.com).

**Status: drafted, tested, awaiting approval. Nothing is published.**

## What this is

Three WordPress pages, saved as drafts on the live site:

| ID | Page | Slug |
|----|------|------|
| 3500 | OligoPoly Research Partner Program | `research-partner-program` |
| 3498 | Research Partner Program Terms | `research-partner-program-terms` |
| 3499 | Partner Compliance Rules | `research-partner-compliance-rules` |

The whole implementation lives inside those pages' content as Gutenberg blocks — no theme
changes, no new plugins, no server files. It survives theme and plugin updates, and
deleting the pages removes every trace.

## Read these first

- **[docs/OPEN-ITEMS.md](docs/OPEN-ITEMS.md)** — what still blocks publishing (legal
  placeholders, delivery address, GTM triggers)
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
wordpress/research-partner-program.tracking.html   FAQ schema + analytics
```

## Ground rules this work respected

No product data, cart, checkout, payment, shipping, tax, WooCommerce, customer-account,
canonical, sitewide-schema, analytics-configuration, or email-automation setting was
touched. No medical, human-use, dosing, or self-administration content appears anywhere —
the pages state research-use-only positioning throughout and prohibit partners from
implying otherwise.
