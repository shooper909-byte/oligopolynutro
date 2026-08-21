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

## Homepage rebuild (staged, 2026-08-21)

A repair-and-optimize pass over the homepage (page ID 381) and the site footer. **Staged, not
published** — this session had no write access to WordPress.

```sh
node build-homepage.js
```

Regenerates from `wordpress/homepage.style.css` + `wordpress/homepage.sections.html`:

- `wordpress/homepage.page.html` — single-line, wpautop-safe page content to paste into page 381
- `preview/homepage-preview.html` — offline preview (uses `preview/assets/`) for visual QA

`wordpress/footer.snippet.html` is the corrected drop-in for the `opl-footer-v2` snippet.

- **[docs/HOMEPAGE-DEPLOY.md](docs/HOMEPAGE-DEPLOY.md)** — paste steps, the two snippet-owned
  sections that still need a change, and the cache purges
- **[docs/HOMEPAGE-CHANGELOG.md](docs/HOMEPAGE-CHANGELOG.md)** — what was broken and what changed
- **[docs/HOMEPAGE-QA.md](docs/HOMEPAGE-QA.md)** — link, image, responsive and accessibility results

Screenshots: `screenshots/homepage-{desktop,tablet,mobile,mobile-small}.png`.

## Ground rules this work respected

No product data, cart, checkout, payment, shipping, tax, WooCommerce, customer-account,
canonical, sitewide-schema, analytics-configuration, or email-automation setting was
touched. No medical, human-use, dosing, or self-administration content appears anywhere —
the pages state research-use-only positioning throughout and prohibit partners from
implying otherwise.
