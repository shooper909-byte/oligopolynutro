# Product-card fact bullets

Short, keyword-highlighted bullets describing what each compound is, for the
product cards across the site.

**Files**

| File | What it is |
|------|-----------|
| [`wordpress/product-card-facts.php`](../wordpress/product-card-facts.php) | `oplhub_product_facts( $product_name )` — drop into the snippet that renders the cards |
| [`wordpress/product-card-facts.html`](../wordpress/product-card-facts.html) | The same bullets as plain `<ul>` blocks, for hand-written card markup |
| [`wordpress/product-card-facts.css`](../wordpress/product-card-facts.css) | `.oplhub-facts` / `.oplhub-key` — the bullet list and the keyword highlight |

Preview: `screenshots/product-card-facts-preview.png`

## Where the cards actually come from

This is the blocker. **None of the product grids are stored in page content**, so
none of them can be edited through the WordPress connection used here — that
connection reaches pages, posts and media only.

| Grid | Where it lives | Editable here |
|------|----------------|---------------|
| `/research/` — "Featured Research Products" (4 cards) | PHP snippet emitting `oplhub-refresh-20260821`; overrides page 3038's stored content entirely | No |
| Homepage — "Launching Research Products" (8 cards) | `[op9_home_products]` shortcode, PHP | No |
| `/research-catalog/` and `/shop/` (15 cards) | PHP rendering WooCommerce records | No |
| WooCommerce products themselves | `product` post type | No — not exposed by the connection |

Both **Code Snippets** and **WPCode Lite** are active, so the snippet is in one
of those. Search either plugin for `oplhub-refresh-20260821` (the `<style>` id it
emits) to find the one that renders the `/research/` cards.

Page 3038's stored content still holds an older two-card version using
`oplhub-static-20260815`. That content is dead — the snippet replaces it on
output — so editing the page in WordPress will not change the live page.

## Applying it

1. Paste `product-card-facts.php` into the snippet that renders the cards.
2. Where the card currently builds its `<ul>`, call:

   ```php
   echo oplhub_product_facts( $product_name );
   ```

   Keys match as substrings of the lowercased product name, so both
   `Tirzepatide 10 mg` and `Tirzepatide 10 mg – 6 Vial Research Kit` resolve to
   the same entry. An unmatched product returns `''` and keeps whatever it
   renders today.
3. Add `product-card-facts.css` to the same snippet's stylesheet (or any
   sitewide CSS). It uses the existing `--violet` / `--muted` tokens.

The same `<ul>` markup works unchanged in the homepage and catalog grids; only
the surrounding class names differ.

## The copy

Mechanism, target and molecular-class descriptors only — **no human-use, dosing,
therapeutic, or outcome claims** — and every entry keeps its research-use-only
line, consistent with the research-use-only positioning used across the site.

| Compound | Bullets (keywords highlighted in bold) |
|----------|----------------------------------------|
| Tirzepatide | Dual **GIP** / **GLP-1** receptor agonist · **Incretin signaling** pathway studies · Research-use-only material |
| Semaglutide | Selective **GLP-1** receptor agonist · **Incretin signaling** and receptor-selectivity studies · Research-use-only material |
| Retatrutide | Triple **GIP** / **GLP-1** / **glucagon** receptor agonist · Multi-receptor **incretin pathway** studies · Research-use-only material |
| Cagrilintide | Long-acting **amylin** receptor analog · **Amylin** and **calcitonin receptor** signaling studies · Research-use-only material |
| NAD+ | **Redox cofactor** — nicotinamide adenine dinucleotide · **Mitochondrial** and **sirtuin** pathway studies · Research-use-only material |
| GHK-Cu | **Copper-binding** tripeptide (Gly-His-Lys) · **Extracellular matrix** and **collagen** signaling studies · Research-use-only material |
| Selank | Synthetic **tuftsin** analog heptapeptide · **GABAergic** and **BDNF** pathway studies · Research-use-only material |
| Metabolic Pathways Stack | **GIP** / **GLP-1** / **glucagon** receptor coverage · Multi-compound **incretin** study design · Research-use-only materials |
| Cellular Energy Stack | **Mitochondrial** and **redox cofactor** coverage · **Sirtuin** pathway study design · Research-use-only materials |
| Neurocognitive Pathways Stack | **GABAergic** and **BDNF** pathway coverage · **Neuropeptide** study design · Research-use-only materials |
| Regenerative Biology Stack | **Extracellular matrix** and **collagen** signaling coverage · **Tissue-repair** pathway study design · Research-use-only materials |

Keyword highlight contrast is ~10.5:1 (`#f3dcff` on the blended violet tint over
`--panel`), comfortably past WCAG AA.

## Unrelated thing worth knowing

On `/research/`, the Selank card's "View Product" is not a link — it renders as
`<span class="opl4-educational-reference" data-link-status="unavailable">`.
That is a deliberate guard from another snippet: Selank has no product permalink
(its homepage link is `?post_type=product&p=447`), so the guard degrades the
button rather than emitting a broken link. Giving Selank a proper product slug
would restore the button. This was left alone.
