# Collection & bundle contents

Bundles and collections say nothing about what they contain. A card reading
`SKU OP-STACK-METABOLIC · Review the product record for specifications and
ordering details.` gives a buyer no reason to click. This snippet renders the real
contents on the product page and on listing cards.

**Every line comes from the product's own WooCommerce Mix and Match configuration.**
Nothing is hard-coded or inferred, so the display cannot drift from what the
customer actually receives, and editing the bundle in wp-admin updates it
automatically. A product with no configured contents renders nothing rather than a
guess.

## What it renders

| Container | Reads as |
|---|---|
| Min = max = number of options (a fixed set) | `Contains 6 materials: Retatrutide 5 mg…` |
| Min < number of options (buyer picks) | `Choose 3 from 24 research materials` |

Saying "contains" about a bundle the buyer composes would be false, so the two
cases are worded differently. The distinction is computed, not configured.

## What the live catalogue actually holds

Read off the live site 2026-09-01.

| Collection | Config | Contents |
|---|---|---|
| Metabolic Pathways | 6 of 6 — fixed | Retatrutide 5 mg, Tirzepatide 10 mg, Cagrilintide 5 mg, NAD+ 500 mg, Selank 5 mg, GHK-Cu 50 mg |
| Cellular Energy | 4 of 4 — fixed | NAD+ 500 mg, GHK-Cu 50 mg, Selank 5 mg, Cagrilintide 5 mg |
| Neurocognitive Pathways | 3 of 3 — fixed | Selank 5 mg, NAD+ 500 mg, GHK-Cu 50 mg |
| Regenerative Biology | 3 of 3 — fixed | GHK-Cu 50 mg, NAD+ 500 mg, Selank 5 mg |
| Build Your Bundle 3 / 6 / 9 | pick 3 / 6 / 9 of 24 | buyer's choice |
| Advanced Multi-Pathway Collection | **none configured** | — |
| Cellular Research Panel | **none configured** | — |

### Four data problems this exposes

1. **Advanced Multi-Pathway Collection and Cellular Research Panel have no
   contents at all.** Both are purchasable — the Panel is priced at $299 — and
   neither has a Mix and Match form or any configured child products. It is not
   clear what a buyer receives. This snippet renders nothing for them, so it does
   not paper over the gap, but it does not fix it either.

2. **Neurocognitive Pathways and Regenerative Biology contain the same three
   materials** — Selank, NAD+, GHK-Cu — in a different order. Two differently
   named, differently priced collections with identical contents is almost
   certainly a misconfiguration.

3. **The descriptions are corrupted** by the "Stack" → "Research Panel"
   find/replace (the same one documented in the `googleconsole` repo, which
   rewrote link targets). The Cellular Research Panel page currently reads
   *"Longevity Research Research Panel is a coordinated research Research Panel
   organized around…"* and *"Research Category: Research Research Panels /
   Longevity Research"*. The Advanced Multi-Pathway page was missed by the same
   pass and still says *"This stack page gives researchers…"*.

4. **The focus lines are the only content not read from WooCommerce.** They are in
   `opl_cc_focus_lines()` keyed by SKU, written from the site's own existing
   pathway language. They state research scope only — no outcome, benefit, dosing
   or human-use claim belongs on a research-use catalogue. Review them before
   activating.

## Install

A **second** WPCode PHP snippet, separate from the COA banner — different concern,
independently removable. Paste [`wordpress/collection-contents.php`](../wordpress/collection-contents.php)
without its opening `<?php` line. Auto Insert → Run Everywhere.

Hook priorities interleave with the COA banner deliberately:

| Priority | Renders |
|---|---|
| 6 | COA banner (single page) |
| 7 | contents panel (single page) |
| 8 | contents line (listing card) |
| 9 | COA pill (listing card) |
| 10 | WooCommerce price |

## Verify

```sh
curl -s -H 'Cache-Control: no-cache' \
  "https://www.oligopolypeptides.com/products/metabolic-pathways-research-collection/?nc=$(date +%s)" \
  | grep -c 'class="opl-cc"'        # expect 1

curl -s -H 'Cache-Control: no-cache' \
  "https://www.oligopolypeptides.com/products/cellular-research-panel/?nc=$(date +%s)" \
  | grep -c 'class="opl-cc"'        # expect 0 — nothing configured to show
```

**Untested against a live WooCommerce install.** The Mix and Match reads
(`get_child_items`, `get_min_container_size`, `get_max_container_size`) are guarded
with `method_exists` so a plugin change degrades to rendering nothing rather than
erroring, but the first load after activating still needs a look.

## Rollback

Deactivate the snippet. Nothing is written to product data.

## Not included

The `/research-catalog/` cards are rendered by a separate snippet whose output this
cannot reach without that snippet's source — the COA pill only attaches there via
CSS generated content, which cannot carry per-product contents. Adding contents to
those cards means editing that snippet.
