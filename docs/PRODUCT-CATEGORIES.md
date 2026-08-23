# Product category repair

**Status: built and tested (51/51). Not yet deployed — one WPCode paste, then delete.**

`/product-category/metabolic-research/` and every other research category
returned WooCommerce's "no products found". Category navigation led nowhere.

---

## What was actually wrong

Not a broken query, not a visibility bug, and not stale term counts — the first
two things I checked and ruled out.

| Archive | Products shown |
|---|---|
| `/product-category/uncategorized/` | **15** |
| `/product-category/research-compounds/` | **1** |
| `/product-category/metabolic-research/` | **0** |

Two facts explain all three numbers:

1. **14 of the 15 sellable containers had no product category at all.** Only
   3454 carried one. So every kit, stack and bundle — the things a customer can
   actually buy — appeared solely under *Uncategorized*, which is exactly the 15
   listed there.

2. **The 10 individual compounds do carry categories** — 7 of them are in
   `metabolic-research` — **but they are excluded from catalogue listings**
   because they are flagged "not sold separately". That exclusion is correct:
   they cannot be bought alone, so listing them in a shop category would be a
   dead end.

So the categories contained the products that must not be listed, and excluded
the ones that can be bought. `metabolic-research` really did contain 7 products
and really should have shown none of them.

Confirmed by the split between the two APIs: `wp/v2/product?product_cat=198`
returns **7** (the REST route ignores catalogue visibility) while the front-end
archive returns **0** (it respects it). The term relationships were never
corrupt.

---

## The fix

Give each container the categories its own contents justify.

| Container | Basis | Categories added |
|---|---|---|
| 8 single-compound kits | the compound inside it | that compound's research areas |
| Metabolic Pathways Stack | its name | `metabolic-research` |
| Cellular Energy Stack | its name | `cellular-research` |
| Neurocognitive Pathways Stack | its name | `cognitive-research` |
| Regenerative Biology Stack | its name | `cellular-research`, `longevity-research` |
| 3 build-your-own bundles | spans everything | umbrella only |

Every container also gets `research-compounds` and `research-products`.

**Why stacks go by name.** *Neurocognitive Pathways Stack* and *Regenerative
Biology Stack* contain the **same three compounds** — NAD+, GHK-Cu and Selank.
Contents cannot tell them apart, and inheriting the union would have filed the
neurocognitive stack under Metabolic Research. Their names state the research
area, so that is what is used. This is the one judgment call in the file, and
it is an explicit map rather than a heuristic.

**Why bundles get umbrella only.** Build-your-own spans all four areas by
design. Filing them under each would put the same three bundles at the top of
every category page.

Resulting sizes, all previously zero:

| Category | Sellable products |
|---|---|
| `metabolic-research` | 6 |
| `cellular-research` | 4 |
| `longevity-research` | 3 |
| `cognitive-research` | 2 |
| `research-compounds` | 15 |
| `research-products` | 15 |

---

## Safety

- **Terms are appended**, never replaced — `wp_set_object_terms( …, true )`. No
  existing category is removed from any product, so a deliberate assignment
  cannot be lost.
- **No product data is written.** No name, price, stock, description, SKU or
  meta is touched.
- **Runs once**, guarded by the `opl_pcat_done` option.
- **Never runs on a front-end request** — bound to `admin_init` and gated on
  `manage_woocommerce`.
- **Creates nothing.** A slug that does not already exist is skipped rather than
  creating a category nobody asked for.
- Term counts are recounted and product transients cleared afterwards, so the
  archives update immediately.

---

## Test results

`php wordpress/product-category-repair.test.php` — **51/51 passing**, using the
real container contents and compound categories read from the live store.

Every container's plan is asserted individually. Beyond that:

- the two same-contents stacks receive **different** categories
- the neurocognitive stack is **not** filed under metabolic
- no plan is ever empty — nothing can be left uncategorised
- every plan carries both umbrella categories
- each of the four research categories **gains** sellable products

The suite exercises planning only; it never writes, since `admin_init` is not
fired.

---

## Deploy

1. Paste `wordpress/product-category-repair.wpcode.txt` into a new WPCode PHP
   snippet, Auto Insert → Run Everywhere. Save and activate.
2. Load any wp-admin page once. That is the trigger.
3. Check `/product-category/metabolic-research/` — it should list 6 products.
4. **Deactivate and delete the snippet.** It is a migration, not a feature.

## Rollback

The option `opl_pcat_log` records every product ID and the exact term IDs added
to it. Remove those in WooCommerce → Products, or delete the option and re-run
after editing the map. Because the migration is purely additive, doing nothing
is also safe — it leaves products in more categories than before, never fewer.

---

## Related, not fixed

**66 product categories exist and 64 were empty**, including duplicates that
will confuse the catalogue: two terms are both named "Research Compounds"
(slugs `research-compounds` and `research-catalog`), and there are near-pairs
like `cellular-longevity` / `cellular-longevity-2`, `metabolic` /
`metabolic-research`, `recovery` / `recovery-research`. This migration uses only
`research-compounds` and leaves the duplicates alone. Worth a separate tidy-up —
deleting unused terms is destructive, so it should be a deliberate decision
rather than a side effect of this fix.
