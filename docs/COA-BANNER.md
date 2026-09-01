# Product COA banner

COA availability shown everywhere a product appears:

- **single product pages** — a purple banner between the title and the price
- **shop / category / search / related-product listings** — a compact pill on the card

**Status: live since 2026-09-01.** Widened from thirteen products to the whole
catalogue on 2026-09-01.

![Desktop](../screenshots/coa-banner-desktop.png)

## Which products get it

**All of them.** The rule is an exclusion list, not an inclusion list.

It originally carried a hand-maintained list of thirteen SKUs. That meant every
product added afterwards silently had no banner — which is exactly what happened,
and how the gap was found (Cagrilintide, Retatrutide 20 mg, Semaglutide 10 mg,
BPC-157, TB-500 and ~26 others had none). Inverting the rule means new products
are covered the moment they are published, and only exceptions need naming.

To exclude a product, add its SKU — either in `opl_coa_banner_excluded_skus()`,
or from elsewhere without touching the snippet:

```php
add_filter( 'opl_coa_banner_exclude_skus', function ( $skus ) {
    $skus[] = 'OP-AUX-BACWATER-10ML';
    return $skus;
} );
```

For rules a SKU list cannot express — a category, a stock state, a custom field —
use the per-product filter instead:

```php
add_filter( 'opl_coa_banner_applies', function ( $applies, $product ) {
    if ( has_term( 'research-bundles', 'product_cat', $product->get_id() ) ) {
        return false;
    }
    return $applies;
}, 10, 2 );
```

**Worth deciding:** Bacteriostatic Water and the multi-product bundles and
collections currently carry the banner along with everything else. If a COA is not
genuinely on file for those, exclude them.

The banner links to `/research-peptides-with-coa/` (override with the
`opl_coa_banner_doc_url` filter).

## Install / update

The strip under the product title is rendered by WooCommerce, not stored in the
product description, so there is nothing to paste per product. One install covers
the whole catalogue.

**Option A — WPCode / Code Snippets (recommended, no theme files touched)**

1. Snippets → find `OligoPoly — product COA banner` (or Add New → PHP Snippet).
2. Replace its contents with [`wordpress/product-coa-banner.php`](../wordpress/product-coa-banner.php),
   **without** the opening `<?php` line (WPCode supplies it).
3. Insertion: **Auto Insert → Run Everywhere**. Save and activate.

**Option B — child theme**

Append the same contents (minus the opening `<?php`) to the child theme's
`functions.php`. Lost on a theme change; Option A is not.

> Do not paste it into `hello-elementor` itself — the parent theme is overwritten
> on update.

## Verify

**Bust the cache when you check.** The site serves cached HTML to plain requests —
that is what made the banner look missing on the first check after install. Add a
unique query string and a no-cache header:

```sh
# single product page — expect 1
curl -s -H 'Cache-Control: no-cache' \
  "https://www.oligopolypeptides.com/products/cagrilintide-5mg-research-peptide/?nc=$(date +%s)" \
  | grep -c 'class="opl-coa-banner"'

# category listing — expect one pill per product card on the page
curl -s -H 'Cache-Control: no-cache' \
  "https://www.oligopolypeptides.com/product-category/metabolic-research/?nc=$(date +%s)" \
  | grep -c 'class="opl-coa-pill"'
```

**If the listing pill does not appear**, the archive is being rendered by an
Elementor loop template rather than WooCommerce's own product loop, which does not
fire `woocommerce_after_shop_loop_item_title`. The single-product banner is
unaffected. Fix by adding the pill to the Elementor loop template, or by hooking
whatever the template does emit.

## Rollback

Deactivate the snippet, or delete the appended block. The banner, the pill and
their CSS disappear completely — nothing is written to product data, so there is
nothing else to unwind.

## Notes

- Display only. No product data, price, inventory, cart, checkout, payment,
  shipping, tax, or WooCommerce setting is read or written.
- CSS is inline, `opl-coa-` prefixed, and printed once per request, only on pages
  that actually render something.
- White text clears 4.5:1 (WCAG AA) against both ends of the `#9333ea → #ae3ada`
  gradient, in both the banner and the pill.
- The banner is a real link with a visible focus ring and honours
  `prefers-reduced-motion`. The listing pill is a **`<span>`, not a link** — that
  hook fires inside WooCommerce's own product-card `<a>`, and nesting an anchor
  there is invalid markup. The card's existing link already reaches the product.
- The banner's visible line omits the product name: it sits under the title
  already, and repeating it wrapped the CTA on longer kit titles. Screen readers
  still get the name through `aria-label`.
- `node build-coa-banner.js` regenerates the QA preview by parsing the snippet
  itself, and fails rather than emitting a stale or empty page.
