# Container 3474 — approved change set

Approved 2026-08-21. **Not applied to the live site.** Every wp-admin value below is pending
the coordinated maintenance window.

## Mix-and-Match container edits

| Container | Remove | Result |
|---|---|---|
| 3447 Build Your Research Bundle – 3 Vials | 3395, 3396, 3397 | 39, 63, 436, 441, 447 |
| 3450 – 6 Vials | 3395, 3396, 3397 | 39, 63, 436, 441, 447 |
| 3452 – 9 Vials | 3395, 3396, 3397 | 39, 63, 436, 441, 447 |
| 3474 Metabolic Pathways Stack | 3395 | 39, 63, 436, 441, 447 |

Containers 3447 / 3450 / 3452 keep their existing sizes (3/3, 6/6, 9/9), per-child maximums
and discounts (5% / 8% / 10%). **Nothing but the contents list changes on those three.**

## Container 3474 — Option B, one vial of each

| Field | Current | Set to |
|---|---|---|
| Minimum container size | 6 | **5** |
| Maximum container size | 6 | **5** |
| Per-child maximum | 6 | **1** |

Five children, each capped at one, container fixed at five: the stack always contains exactly
one vial of each of Tirzepatide 10 mg, Cagrilintide 5 mg, NAD+ 500 mg, Selank 5 mg and
GHK-Cu 50 mg. "Includes" wording therefore stays accurate on every card.

Do not apply the per-child maximum of 1 to 3447 / 3450 / 3452 — those remain free-choice
containers where one compound may fill the whole container.

## Description for 3474

> A curated five-component combination organized for metabolic systems research. Each stack
> contains one vial of each component: Tirzepatide 10 mg, Cagrilintide 5 mg, NAD+ 500 mg,
> Selank 5 mg and GHK-Cu 50 mg.

## `/research-stacks/` card text

> Metabolic Pathways Stack: Tirzepatide 10 mg, Cagrilintide 5 mg, NAD+ 500 mg, Selank 5 mg,
> and GHK-Cu 50 mg.

## Out-of-stock safeguard — backup only

Products 3395, 3396, 3397: set stock status **Out of stock**, **backorders Do not allow**.
Keep the product records published and their pages publicly visible as provisional records.

**Do not** enable WooCommerce → Settings → Products → Inventory → "Hide out of stock items
from the catalog". That setting is global and is explicitly out of scope.

This is defence in depth against future containers only. Removing the three products from
every Mix-and-Match container remains the primary requirement.

## Image

`OP-STACK-METABOLIC-5-2.png` — five complete vials, full plinth. Replaces
`OP-STACK-METABOLIC.png` on container 3474, the `/research-stacks/` card and the homepage
Metabolic Systems card. Uploaded and verified live 2026-08-21: 1600x1000, HTTP 200. WordPress de-duplicated the
filename with a `-2` suffix, so this exact URL is the one to use -- the unsuffixed
`OP-STACK-METABOLIC-5.png` returns 404 and must not be referenced.
