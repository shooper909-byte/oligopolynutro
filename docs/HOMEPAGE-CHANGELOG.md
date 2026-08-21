# Homepage rebuild — change log

Audited against the live site on **2026-08-21**. Staged in this repo; not published.

---

## What was broken

The homepage markup is stored in page 381's content and filtered by `wpautop`, which injected
`<p>`, `</p>` and `<br>` tags into the hand-written HTML. Live evidence:

- **Research Stacks** — `<span></p><h3>Metabolic Systems</h3><p><small>…` and a `<br>` between
  every card. The label block was broken out of its card, which is the "missing images and
  floating labels" symptom.
- **Access cards** — `<a class="op9-access-card" href="/my-account/"></p><h3>…` — same break.
- **Build Your Research Bundle** — `…</div></p></div></p></div>` closed one `<div>` more than
  was opened, ending the two-column grid early and leaving the empty container next to it.
- **Trust and product sections** — stray `</p>` after each inline `<svg>` and between cards.

Separately: the second-row product images were **not** broken at the source — all eight
`OP-*-300x300.png` files return HTTP 200. What made the row look unfinished was the `<br>` and
`</p>` injection between cards plus SEO-stuffed titles overflowing the card body.

## 1. Product cards

Rebuilt all eight cards to a single structure: image (1:1, consistent ratio, descriptive alt),
clean name and strength, price, a status chip, three bullets with bold lead-ins, and a
"View Product" CTA pinned to the bottom of the card.

- Titles cleaned: `Tirzepatide 10mg Research Peptide | OligoPoly Laboratories` → **Tirzepatide
  10 mg**. Same for the other seven.
- Selank's link was `?post_type=product&p=447`; it now uses the canonical
  `/products/selank-5mg-research-peptide/`.
- Prices unchanged: $74.99, $109.99, $109.99, $89.99, $79.99, $79.99, $99.99, $129.99 —
  re-read from each product page during the audit.
- Bullet copy is taken from each product's own approved short description. Nothing was
  invented, and no bullet describes an effect on a person.
- **Semaglutide 5 mg, Retatrutide 5 mg and Retatrutide 20 mg have no approved research-focus
  copy** — their product pages read "provisional catalog record … No lot has been released.
  Documentation and availability require manual approval." Their cards state exactly that
  instead of a fabricated research focus, and carry a "Provisional record" chip.
- Every one of the eight products is flagged `not sold separately` on its own product page
  ("This product can only be purchased as part of a mix and match bundle"). The other five
  cards carry an "Available in bundles & stacks" chip so a visitor is not sent to a page with
  no buy button. Checkout behaviour itself is untouched.
- Equal card heights and aligned CTAs verified at four viewports — see `HOMEPAGE-QA.md`.

## 2. Build Your Research Bundle

Rebuilt as a full-width two-column section with both columns stretched to the same height.

- **Left:** headline, explanation, three bundle-size selectors (3 vials / 5%, 6 vials / 8%,
  9 vials / 10% — the discounts already published on `/build-your-research-bundle/`), and the
  "Build Your Bundle" CTA. Each size selector links straight to its own bundle product.
- **Right:** the existing approved `BUNDLE-BUILDER-HERO.png` above a three-step panel —
  select a bundle size → choose eligible research products → review and add to cart.
- The empty container is gone. Columns stack cleanly at ≤980px.
- No new discounts and no change to the bundle rules.

## 3. Curated collections / research stacks

Rebuilt as a four-card grid. Each card carries its approved stack image, the collection title,
the approved one-line description from `/research-stacks/`, the products it contains, and an
"Explore Collection" CTA.

| Card | Destination | Products listed |
|---|---|---|
| Metabolic Systems | `/products/metabolic-pathways-stack/` | Retatrutide 5 mg, Tirzepatide 10 mg, Cagrilintide 5 mg, NAD+ 500 mg, Selank 5 mg, GHK-Cu 50 mg |
| Cellular Energy | `/products/cellular-energy-stack/` | NAD+ 500 mg, GHK-Cu 50 mg, Selank 5 mg, Cagrilintide 5 mg |
| Neurocognitive Pathways | `/products/neurocognitive-pathways-stack/` | Selank 5 mg, NAD+ 500 mg, GHK-Cu 50 mg |
| Regenerative Biology | `/products/regenerative-biology-stack/` | GHK-Cu 50 mg, NAD+ 500 mg, Selank 5 mg |

Product lists were read from each stack's Mix-and-Match child configuration, so they match the
catalog exactly. All four cards previously pointed at the generic `/research-stacks/` index;
they now point at their own stack. No collection was hidden — all four have valid destinations
and configured products.

The whole card is clickable via a stretched link on the CTA, so there is exactly one focusable
link per card with the accessible label "Explore Collection: <stack name>".

## 4. Research-customer access section

The old "Built for Independent and Institutional Research" section (three vague boxes labelled
"Research account →", "Institutional access →", "Open the research library →") is replaced by
**Research Access for Every Customer** with the specified supporting copy and three pathway
cards:

| Card | CTA | Destination |
|---|---|---|
| Shop Research Products | Browse the Catalog | `/research-catalog/` |
| View Testing & COAs | View Testing Documents | `/research-peptides-with-coa/` (the COA Center) |
| Institutional & Bulk Purchasing | Institutional Access | `/institutional-purchasing/` |

The "OligoPoly Intelligence" box is gone; the Research Library remains reachable from the
header nav and the footer. Nothing implies personal consumption — every reference is to a
research customer.

The duplicate account section (`opl-home-pathways`) that currently sits directly under the hero
is injected by a snippet, not by page 381, so it must be removed there — see
`HOMEPAGE-DEPLOY.md` §3.

## 5. Information hierarchy

New order in page 381:

1. Hero — now introduces the whole research catalog ("Research-Use Peptides and Laboratory
   Compounds") rather than leading with Laboratory Stacks. Primary CTA to the catalog, bundles
   demoted to the secondary CTA. RUO line kept directly under the CTAs.
2. Compact research-quality trust strip (four items, one row).
3. Featured research products.
4. Build Your Research Bundle.
5. Curated collections.
6. Why Researchers Choose OligoPoly.
7. Research Access for Every Customer.
8. *(Intelligence List signup — snippet-owned, see `HOMEPAGE-DEPLOY.md` §3.)*
9. RUO compliance band, then the footer.

No form or account request appears before the products.

## 6. Footer

**Before** (as audited):

```
logo.webp (42px)  ·  "Premium nutraceuticals and RUO research compounds…"
Shop:     Vitamins · Longevity · Performance · Cognitive Support · Wellness
Research: Research Catalog · Stacks · Peptide Finder · RUO Policy
Company:  About · Contact · Shipping · Privacy Policy · Terms of Use · Refund Policy
```

Changes:

- **Vitamins removed.** All five links in that column returned **HTTP 410 Gone** — the entire
  Shop column was the obsolete nutraceutical taxonomy. Replaced with the live research
  categories (Metabolic, Cellular, Cognitive, Longevity Research), the catalog, Research Stacks
  and Build Your Research Bundle.
- **Small footer logo removed** and not replaced with another logo; the column now opens with a
  plain-text brand line and the spacing was rebalanced (grid row gap 32px, RUO rule added
  above the copyright). The header logo is untouched.
- "Premium nutraceuticals" removed from the company blurb.
- `/stacks/` (a 301) replaced with its canonical `/research-stacks/`.
- Institutional Purchasing, COA Verification, Quality Standards and the Storage & Handling
  Guide added — all previously reachable only from the header dropdowns.
- Focus-visible outline added to footer links.

Every remaining footer href returns HTTP 200 with no redirect.

## Preserved

Black/purple design system and every palette token, the header and its logo, all product
records, prices, availability, checkout and payment configuration, account requirements, the
age gate, RUO positioning and compliance language, and all existing URLs. No medical,
human-use, dosing, therapeutic, testing or scientific claim was added anywhere — the build
script fails on a banned-vocabulary match.
