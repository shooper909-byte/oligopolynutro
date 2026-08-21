# Homepage deploy — page 381

Staged, **not published**. Everything below is a copy-paste operation in wp-admin.

Site: https://www.oligopolypeptides.com · Theme: Hello Elementor · Homepage: page ID **381**
(`post-381 page` on the rendered document; the page uses the default template, not Elementor).

---

## 1. Replace the homepage content

1. wp-admin → Pages → **OligoPoly Laboratories | Research Materials Supplier for Verified
   Laboratories** (ID 381) → open in the **Code editor** (⋮ → Code editor), not the visual
   editor.
2. Select all and replace with the entire contents of **`wordpress/homepage.page.html`**.
   It is one single line — paste it as-is; do not reformat or pretty-print it.
3. Update.

**Why one line.** Page 381's content runs through `wpautop`, which turns newlines in stored
content into `<p>` and `<br>` tags. That is what broke the live page: stray `</p>` inside the
stack and access cards, `<br>` between grid children, and an extra `</div>` in the bundle
column that closed the grid early and left the empty container. A single-line payload gives
`wpautop` nothing to key on. Re-run `node build-homepage.js` after any source edit rather than
hand-editing the built file.

## 2. Replace the footer snippet

The footer is **not** part of page content and is not a WordPress menu. It is emitted by a
snippet that outputs `<style id="opl-footer-v2-css">` followed by
`<footer class="op-footer opl-footer-v2">`.

1. Find it in **Code Snippets** or **WPCode Lite** (search the snippet list for
   `opl-footer-v2`).
2. Replace that snippet's markup with **`wordpress/footer.snippet.html`**.
3. Save and activate.

The header logo is in a different snippet (`opl-shared-nav-20260815`) and is untouched.

## 3. Two homepage sections owned by a snippet, not by page 381

These render inside `<main id="op9-home">` on the live page but are **absent from page 381's
stored content** (confirmed against `/wp-json/wp/v2/pages/381`). They are injected by a
snippet, so replacing the page content in step 1 will not move or remove them:

| Section | Marker | Required change |
|---|---|---|
| `opl-home-pathways` — "Create an Account and Purchase Research Products" | `data-opl-marker="OPL-ACCOUNT-PATHWAYS-20260815"` | **Remove.** It is the duplicate account section; "Research Access for Every Customer" replaces it. |
| `opl-email-homepage` — "Join the OligoPoly Intelligence List" (Brevo form id 4) | `id="opl-email-homepage"` | **Move** so it renders after the Research Access section, or remove it — the sitewide `opl-email-footer` instance (form id 5) already carries the signup and lands at the end of the page. Either choice leaves exactly one signup on the homepage. |

Both live in the snippet that contains the string `OPL-ACCOUNT-PATHWAYS-20260815`. Until this
step is done the homepage will show the signup and the old account boxes immediately under the
hero, ahead of the products.

## 4. Clear caches

In this order, after steps 1–3:

1. WordPress page cache (WP Super Cache / Jetpack Boost / whichever is active) — **Delete
   cache** / **Purge all**.
2. Any page-builder CSS cache: Elementor → Tools → **Regenerate CSS & Data**, then **Sync
   Library**. (Page 381 is not an Elementor page, but Elementor's global CSS is enqueued on it.)
3. CDN: purge the zone, or at minimum purge `/`, `/wp-content/uploads/oligopoly/`, and the
   footer CSS asset.
4. Reload `https://www.oligopolypeptides.com/` with a hard refresh and confirm the hero
   headline reads "Research-Use Peptides and Laboratory Compounds".

## 5. Rollback

The previous homepage content is in the WordPress revision history for page 381 (revert to the
revision immediately before this change). The previous footer snippet is in the snippet's own
revision history; the live markup as audited is reproduced in
`docs/HOMEPAGE-CHANGELOG.md` under "Footer, before".

## What this change does not touch

Product prices, product availability, stock, cart, checkout, payment, shipping, tax,
WooCommerce settings, customer-account requirements, the age gate, the header and its logo,
legal policy pages, analytics configuration, and every page other than the homepage.
