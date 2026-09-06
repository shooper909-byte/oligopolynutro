# Product data fixes — bacteriostatic water volume, Retatrutide 10 mg name

Two WooCommerce corrections requested 2026-09-06. Neither is a compliance finding, but the
first is a live order-accuracy problem and worth doing before anything else on this page.

**Still not applicable through this connection.** Jetpack continues to report
`isUserConnected: false`, so content writes are rejected. Re-checked 2026-09-06. See
[P0-FIX.md](P0-FIX.md) for how to restore write access.

---

## 1. Bacteriostatic water — should be the 10 mL product throughout

`/products/bacteriostatic-water-30ml-research-support/` · SKU `OP-AUX-BACWATER-10ML` · $19.99

The listing is **internally contradictory**, not merely mislabelled. It currently states both
volumes and **two different SKUs** on the same page:

| | Says 10 mL | Says 30 mL |
|---|---|---|
| Visible text occurrences | 9 | **14** |
| SKU shown | `OP-AUX-BACWATER-10ML` (sidebar, ×5) | `OP-AUX-BACWATER-30ML` (spec table, ×1) |

A customer reading the product title sees **10 mL**; the same page's specification table,
short description and FAQ tell them **30 mL** — a 3× difference on a consumable, with a
conflicting SKU next to it. Beyond the customer-facing confusion, if fulfillment picks from
the spec-table SKU rather than the product SKU, orders ship wrong. Volume disputes on a
$19.99 consumable are exactly the kind of avoidable chargeback exposure underwriting looks
at, so this is worth fixing on its own merits.

**Target state: 10 mL everywhere, single SKU `OP-AUX-BACWATER-10ML`.**

### Fields to change

Already correct — leave alone: the WooCommerce **product title** (`Bacteriostatic Water
10 mL Research Support`), the **SKU field**, and the "Research format: 10 mL support vial"
line.

| # | Field | Current | Change to |
|---|-------|---------|-----------|
| 1 | Rank Math SEO title | Bacteriostatic Water **30mL** Research Support | Bacteriostatic Water 10 mL Research Support |
| 2 | Rank Math / OG description + `og:title` | …**30mL**… | …10 mL… |
| 3 | Short description | "Bacteriostatic Water **30mL** supports controlled research peptide preparation workflows." | "Bacteriostatic Water 10 mL supports controlled research peptide preparation workflows." |
| 4 | Spec table → Catalog Item | Bacteriostatic Water **30mL** Research Support | Bacteriostatic Water 10 mL Research Support |
| 5 | **Spec table → SKU** | `OP-AUX-BACWATER-`**`30ML`** | `OP-AUX-BACWATER-10ML` |
| 6 | RUO disclaimer paragraph | Bacteriostatic Water **30mL** Research Support… | …10 mL… |
| 7 | FAQ (5 questions) | every "**30mL**" | 10 mL |
| 8 | FAQ storage answer | "The **30mL** volume is appropriate for laboratory-scale…" | "The 10 mL volume is appropriate for laboratory-scale…" |
| 9 | Product tag | `Bacteriostatic Water 30mL` | `Bacteriostatic Water 10 mL` (delete the old tag) |
| 10 | Research Panel references | "Bacteriostatic Water **30mL** is included in the Starter Research Panel ($199) and Recovery Cellular Research Panel" | 10 mL — **and confirm the panels actually ship the 10 mL vial** |

A find-and-replace of `30mL` → `10 mL` and `30 mL` → `10 mL` scoped to this product covers
1–8. Do items 9 and 10 by hand.

### Two things to confirm first

- **Is $19.99 still the right price for 10 mL?** If the price was set for a 30 mL vial, it
  needs revisiting alongside the volume.
- **Item 10 is a real question, not just copy.** If the Starter and Recovery Cellular
  Research Panels were built around a 30 mL vial, correcting the text without checking what
  those panels actually contain moves the error rather than fixing it.

### The slug

Current: `/products/bacteriostatic-water-30ml-research-support/`

Changing it to `…-10ml-…` is more consistent, but the slug is also the canonical URL and is
linked from panel pages and the catalog. If you change it, **301 the old slug to the new
one** and update internal links. If you would rather not touch URLs during a compliance
review, leaving the slug and fixing the 10 visible fields is defensible — no customer reads
the slug, and every visible surface would then say 10 mL.

---

## 2. Retatrutide 10 mg — "Research Material" → "Research Peptide"

`/products/retatrutide-10mg-research-peptide/` · SKU `OP-MET-RETA-10MG` · $139.00

Product title reads **"Retatrutide 10 mg Research Material"** while its two siblings read
"Research Peptide", and its own slug already says `research-peptide`.

| Field | Current | Change to |
|-------|---------|-----------|
| Product title | Retatrutide 10 mg Research **Material** | Retatrutide 10 mg Research **Peptide** |
| Rank Math SEO title | check — likely carries "Research Material" | Retatrutide 10 mg Research Peptide |
| Body copy / spec table | any "Research Material" on this product | Research Peptide |

Slug already correct — no redirect needed.

**Sequencing note.** If VERIFIED comes back requiring a new name for this compound, the title
gets rewritten again anyway. This fix is still worth doing now: inconsistent naming *within*
a restricted product line is the kind of thing a reviewer notices, and it costs a minute. Do
not renumber the SKU — see [NAMING-OPTIONS.md](NAMING-OPTIONS.md) on keeping SKUs stable
across a rename.

---

## Verify

Neither change is caught by `scan.py` — it scans compliance language, not catalog accuracy.
Check by reloading both product pages and confirming a single volume and a single SKU appear
on the bacteriostatic water listing.
