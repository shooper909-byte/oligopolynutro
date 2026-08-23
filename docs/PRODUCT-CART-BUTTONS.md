# Purchase controls on product cards

Adds a cart control to every product card on the site — the homepage
`op9-product-card` grid and the `oprc-card` grid on `/research-catalog/`.

**Status: built and tested (37/37). Not yet deployed — one WPCode paste.**

---

## The finding that shaped it

"Add to cart on all products" is not achievable as stated, and the reason is a
deliberate setting rather than missing data.

I got this wrong first time and want to be clear about it. The WooCommerce Store
API reports `is_purchasable: false` and an **empty price** for all 25 products
to an unauthenticated reader, which looks exactly like a store with no pricing.
It isn't. Adding a bundle to a real cart returned **$294.47**, itemised
$94.99 + $123.49 + $75.99. The prices are real; the Store API just withholds
them from guests.

The actual constraint is Mix and Match's **"not sold separately"** flag:

| Group | Count | Control | Why |
|---|---|---|---|
| Individual compounds | 10 | **Available in Kits** → its dedicated kit | `wc-mnm-not-sold-separately`. Priced $64.99–$123.49, but WooCommerce refuses to sell them alone — their own product pages render no add-to-cart form |
| Single-compound kits | 8 | **Add to Cart**, one click | 1 child, `min = max = 6` → exactly one valid selection |
| Curated stacks | 4 | **Select Options** | 6 children, each may contribute up to 6 → many valid selections |
| Build-your-own bundles | 3 | **Select Options** | 8 children → many valid selections |

Verified against the live store, not inferred:

- `?add-to-cart=447` (Selank 5 mg, priced $79.99) → **cart stayed empty**
- `add-to-cart=3454` + `mnm_quantity[39]=6` → **"Tirzepatide 10 mg – 6 Vial
  Research Kit, $413.94" in the cart**
- `add-to-cart=3447` + three `mnm_quantity` fields → **$294.47 in the cart**

So the rule is: **sell it from the card when exactly one valid configuration
exists, send the customer to configure when it does not, and never render a
control that would fail.**

A greyed-out or decorative "Add to Cart" was not an option. On a research-supply
catalogue, a button that silently does nothing is worse than no button.

### How "exactly one configuration" is decided

For a container, sum what each child is *allowed* to contribute. If that total
equals the required container size, no choice remains and the card can post the
selection directly. Anything else is a real choice and belongs on the product
page. This is computed from the live MNM API at render time, never hard-coded.

---

## Files

| File | Role |
|---|---|
| `wordpress/product-cart-buttons.php` | The build |
| `wordpress/product-cart-buttons.wpcode.txt` | Paste-ready, no `<?php`, pure ASCII, 495 lines |
| `wordpress/product-cart-buttons.test.php` | 37-assertion suite, run against real captured pages |
| `screenshots/cart-btn-catalog-card.png`, `cart-btn-home-card.png` | Result |

---

## How it works

Both card grids are produced by other snippets not in this repository, so the
finished HTML is rewritten rather than their templates. Each card is matched on
**its own product permalink** via `url_to_postid()`, so a control can only ever
attach to the product its card actually links to — this is asserted by the test
suite, which re-parses the output and compares every rendered `add-to-cart` ID
against the card's own "View Product" href.

The cart control is a **plain `<form method="post">`** posting to the product
permalink, exactly what the product page itself submits. No JavaScript, so it
works on a slow phone and cannot be left half-wired; WooCommerce handles
validation, stock and redirect.

"Available in Kits" resolves to the compound's **dedicated single-compound kit**
where one exists, preferring a one-child container over a stack that merely
contains it, and falling back to `/research-catalog/`. The container→children
map is cached for an hour so a catalogue page does not load every MNM product on
every render.

---

## Test results

`php wordpress/product-cart-buttons.test.php <home.html> <catalog.html>` —
**37/37 passing**, run against HTML captured from the live site.

**Control selection:** each of the four product groups resolves to the right
control; a kit whose only child is out of stock degrades to "Select Options"
rather than offering an unfulfillable cart.

**Form correctness:** the kit form posts `add-to-cart=3454` and
`mnm_quantity[39]=6` to the right permalink, as a real POST, with no JavaScript
and no `onclick`.

**Never broken:** no bundled-only compound renders a form or an `add-to-cart`
input; no control anywhere carries `disabled`.

**Kit routing:** Tirzepatide links to its own 6-vial kit, not the catalogue and
not a stack that happens to contain it; a one-child kit is preferred over a
multi-child container; an unavailable kit falls back cleanly.

**Integration on real pages:** CSS injected exactly once; no action row created
or destroyed; every "View Product" link preserved; the rewrite is idempotent;
**no control attached to the wrong product**.

Rendered: buttons are 48px tall, screen-reader text correctly clipped to 1×1px,
`prefers-reduced-motion` honoured.

### Not covered

- No live click-through of the deployed buttons yet — the POST payloads were
  verified directly against the live cart, but the rendered buttons have not
  been clicked in a browser on the deployed site.
- Category archive pages are **not** covered, because they currently render no
  products at all (see below).

---

## Two things worth fixing separately

1. **Category archives are empty.** `/product-category/metabolic-research/` and
   the other category pages return WooCommerce's `woocommerce-no-products-found`.
   No products are listed, so there is nothing for this file to add a button to.
   Products are reachable via `/research-catalog/` and the homepage, so the
   store works, but the category navigation currently leads nowhere.

2. **A malformed product link on the homepage.** One card links to
   `?post_type=product&p=447` instead of the pretty permalink. It resolves, but
   it is inconsistent with the other nine and bypasses the canonical URL.

---

## Rollback

Toggle the snippet Inactive in WPCode. Every card returns to exactly its current
markup — the file only ever *adds* a control and never rewrites the existing
"View Product" or "Documentation" links, which the test suite asserts by
counting them before and after.
