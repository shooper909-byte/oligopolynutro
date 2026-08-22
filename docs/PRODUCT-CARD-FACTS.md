# Product-card fact bullets

Short, keyword-highlighted bullets describing what each compound is, for the
product cards across the site.

**Files**

| File | What it is |
|------|-----------|
| [`wordpress/product-card-facts.php`](../wordpress/product-card-facts.php) | **The one to paste.** Self-contained; adds bullets to all three grids on its own |
| [`wordpress/product-card-facts.html`](../wordpress/product-card-facts.html) | The same bullets as plain `<ul>` blocks, for hand-written card markup |
| [`wordpress/product-card-facts.test.php`](../wordpress/product-card-facts.test.php) | Runs the snippet against saved copies of the three live pages |
| [`wordpress/product-card-facts.css`](../wordpress/product-card-facts.css) | `.opl-facts` / `.opl-key` styling, for reference — the PHP inlines it |

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

## Applying it — paste one snippet into WPCode

`wordpress/product-card-facts.php` is **self-contained** — nothing else has to be
edited. It works in two passes:

1. **`the_content` at `PHP_INT_MAX`** — catches the homepage grid and the
   `/research/` cards.
2. **An output-buffer sweep on `template_redirect`** — catches whatever pass 1
   missed. The research catalog rebuilds its grid *after* `the_content` has run,
   so pass 1 never sees those cards; this pass reads the finished page instead.

The passes are idempotent: pass 2 skips cards pass 1 already handled, and the
stylesheet is inlined exactly once per page.

Both **Code Snippets** and **WPCode Lite** are active and their admin menus look
alike. WPCode's menu is **Code Snippets**; the other plugin's is **Snippets**.
Either will run this, but the steps below are WPCode's.

1. WP Admin → **Code Snippets** → **+ Add Snippet**
2. Choose **Add Your Custom Code (New Snippet)** → **Use snippet**
3. Title it something like `OligoPoly — Product Card Fact Bullets`
4. Set **Code Type** to **PHP Snippet**
5. Paste the contents of `wordpress/product-card-facts.php`, **omitting the
   opening `<?php` line** — WPCode adds it
6. Under **Insertion**, leave **Auto Insert** with location **Run Everywhere**
7. Flip the toggle at the top right from **Inactive** to **Active**
8. **Save Snippet**

Then reload `/research/`, the homepage, and `/research-catalog/`.

**To roll back:** toggle the snippet Inactive. It changes nothing in the
database and nothing in any other snippet.

**If nothing appears:** say which page — the two passes cover `the_content` and
the finished page, so a miss would mean the cards are being written by
JavaScript in the browser rather than by PHP.

### What it does to each card

- Card already has a placeholder `<ul>` (the `/research/` cards) → the list is
  **replaced**.
- Card has no list (homepage, catalog) → one is **inserted** right after the
  product title.
- Product name matches nothing in the map → the card is left **exactly** as-is.
- Runs twice → no duplication; it skips cards it has already handled.

### Verification

`wordpress/product-card-facts.test.php` runs the snippet against saved copies of
the three live pages with minimal WordPress stubs:

```sh
php wordpress/product-card-facts.test.php
```

Result at time of writing — **27/27 cards**, each matched to the right compound:

| Page | Cards | With bullets |
|------|-------|--------------|
| `/research/` | 4 | 4 |
| Homepage | 8 | 8 |
| `/research-catalog/` | 15 | 15 |

Screenshots: `screenshots/product-card-facts-preview.png` (`/research/`) and
`screenshots/product-card-facts-homepage.png` (homepage grid, prices and
"View Product" links intact).

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
| Build Your Research Bundle | Self-selected **multi-compound** research collection · Volume-tiered **3 / 6 / 9 vial** configurations · Research-use-only materials |
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
