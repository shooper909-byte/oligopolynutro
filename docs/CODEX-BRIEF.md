# Brief: fix collection data and install the contents snippet

Work order for an agent with WP-CLI or database access to
**oligopolypeptides.com**. Every fact below was read off the live site and is
reproducible with the commands given.

## Start here — status as of 2026-09-02

**This is a work order to implement, not a document to review.** Do the tasks marked
READY, in the order listed. Follow the Ground rules for every write.

| Task | State | Do now? |
|---|---|---|
| **3e** — finish the copy repair (2 body strings + SEO metadata) | **READY** | **Yes — start here.** Smallest, fully specified, no decisions left |
| **1** — configure the two empty collections | **READY** | **Yes.** Contents, Retatrutide strength and product name were all supplied by the owner; see "Decisions taken" |
| **6** — Retatrutide 10 mg listed but unreachable | **READY** | **Yes.** Found 2026-09-02; same bug as product 55 |
| 2 — Neurocognitive and Regenerative hold identical materials | BLOCKED | No. The owner has not said what each should contain |
| 3d — five phantom products, and the 10 mL / 30 mL conflict | BLOCKED | No. Owner decision |
| 3a–3c — the 20 exact replacements | **DONE** 2026-09-02 | — |
| 4 — install the contents snippet | **DONE** 2026-09-02 (snippet 3763) | — |
| 5 — verify the Mix and Match reads | **DONE** — verified working against the live plugin | — |

Two questions were raised and are answered in place: the Retatrutide strength
(20 mg — and note only two are buyable, see Task 6) and the $262.99 vs $299 price
(keep $262.99, fix the prose, and do it last). Both are inside Task 1.

Report per task, with before/after counts, and stop at anything marked BLOCKED.

---

## Ground rules

1. **Take a database backup before the first write.** UpdraftPlus is installed.
2. **Never run an unscoped find/replace on `post_content`.** That is what caused
   Task 3: a `Stack` → `Research Panel` replace was run without word boundaries or
   a column/post filter, and it rewrote link `href` values and mid-sentence text
   across the site. The `googleconsole` repo documents the link damage from the
   same pass. Every replacement in this brief is a full, unique phrase for exactly
   that reason.
3. **Dry-run first.** `wp search-replace` supports `--dry-run`; use it and read the
   report before applying. Scope every call with `--include-columns=post_content`
   (and `post_excerpt` where stated) and a specific post ID where possible.
4. **Do not invent product data.** If a task asks what a collection contains and
   there is no configured answer, stop and ask. Guessing what a customer receives
   for $299 is worse than leaving it undefined.
5. **Bust the cache when verifying.** Plain requests are served cached HTML. Use
   `-H 'Cache-Control: no-cache'` and a unique `?nc=` query string.
6. Report what you changed, per task, with before/after counts.

---

## Task 1 — Two collections have no contents configured

**Contents supplied by the owner 2026-09-02.** One detail still needs confirming
before any write — see "Before applying" below. Everything else is settled.

### The groupings

| Product | ID | SKU | Contents |
|---|---|---|---|
| Advanced Multi-Pathway | 70 | `OP-STK-ADVANCED-MULTIPATHWAY` | Retatrutide 20 mg + Cagrilintide 5 mg |
| Cellular Research Panel | 468 | `OP-STK-LONGEVITY` | GHK-Cu 50 mg + BPC-157 5 mg |

Child products, verified live 2026-09-02:

| Material | SKU | ID |
|---|---|---|
| Cagrilintide 5 mg Research Peptide | `OP-MET-CAGRI-5MG` | 436 |
| GHK-Cu 50 mg Research Peptide | `OP-LON-GHKCU-50MG` | 441 |
| BPC-157 5 mg Research Peptide | `OP-REC-BPC157-5MG` | 3398 |
| Retatrutide 5 mg Research Peptide | `OP-MET-RETA-5MG` | 3395 |
| Retatrutide 20 mg Research Peptide | `OP-MET-RETA-20MG` | 3396 |

### Decisions taken 2026-09-02

**Retatrutide 20 mg** (`OP-MET-RETA-20MG`, #3396, $179.99). The owner delegated the
choice.

> **On "Retatrutide has 5 mg, 10 mg and 20 mg products":** only **two** are publicly
> buyable. Retatrutide 10 mg is product #12 and it is shadowed exactly the way
> product 55 was — see Task 6. Do not use #12 as a child product; a panel containing
> an unreachable product is worse than the gap it fills. Reasoning: the product is named "Advanced" and is currently the most
expensive non-kit item in the catalogue, and pairing that with the entry-level 5 mg
undercuts the positioning; the Metabolic Pathways Collection already carries the
5 mg, so using 20 mg here differentiates the two rather than adding a second pair of
collections with overlapping contents. This is positioning logic, **not** a margin
calculation — no COGS data was available. Changing it later is one child-product
swap.

**"Cellular Research Panel"** for product 468. The owner asked for whichever is
easiest, and this one measurably is:

| Candidate | post_title | URL slug | Redirect needed | Text to change |
|---|---|---|---|---|
| **Cellular Research Panel** | already correct | already correct | **no** | 12 body mentions + Rank Math meta |
| Longevity Research Panel | needs change | mismatched | yes, or a confusing URL | 4 body mentions + post_title |
| Cellular Biology Research Panel | needs change | needs change | yes | all 16 + slug |

"Cellular Research Panel" is already the title, the H1 and the URL, so it is the only
option needing **no slug change and no redirect** — which matters on this site
specifically, given the 404 that a previous slug change caused. Fixing the Rank Math
title and description clears all nine `<head>` instances at once (Task 3e-ii).

**The draft third panel is not to be created.** The owner mentioned a *draft*
"Cellular Bioenergetics Research Panel" (NAD+ 500 mg `OP-AUX-NAD-500MG` #63 +
GHK-Cu 50 mg `OP-LON-GHKCU-50MG` #441). It was described, not requested. Do not
create it without an explicit instruction.

### The $262.99 vs $299 question — keep $262.99, and fix the copy last

Three reasons to keep the displayed price and correct the prose, rather than raising
the price to match the prose:

1. **$262.99 is what is actually charged**, and what sits in the page's structured
   data. $299 appears only in body text. Editing prose to match reality is a
   correction; raising the charged price is a pricing decision the owner has not
   made.
2. **$262.99 fits the catalogue's own model better.** Against $149.98 of components
   it is a 1.75x multiplier; Metabolic Pathways runs about 1.65x. $299 would be
   1.99x — an outlier.
3. It is the lower-risk edit. Nothing about a text fix can change what a customer
   is billed.

**Sequence this last.** Converting product 468 to Mix and Match may recompute its
price (see below), so a prose price written before the conversion can be stale
again within the hour. Apply the conversion, read the resulting live price, *then*
correct the copy to match.

**Better still, remove the price from the prose entirely.** A hardcoded price in body
copy is exactly how this drift happened, and it will happen again on the next price
change. Prices belong in the price field, which the page already renders.

### Check the price before and after configuring — this will probably move

The four already-configured collections all display a **computed price range**, not a
fixed price. Metabolic Pathways shows `$932.93 – $1,126.13`. Products 70 and 468
currently show single fixed prices — $499.99 and $262.99.

So converting them to Mix and Match will most likely **replace those fixed prices
with a computed range**, and the result may not be what is on the page today. For
reference, the component list prices are:

| Panel | Components | Sum of component list prices | Price shown today |
|---|---|---|---|
| Advanced Multi-Pathway (70) | Retatrutide 20 mg $179.99 + Cagrilintide 5 mg $109.99 | $289.98 | $499.99 |
| Cellular Research Panel (468) | GHK-Cu 50 mg $89.99 + BPC-157 5 mg $59.99 | $149.98 | $262.99 |

Note the existing collections also price **above** the sum of their components
(Metabolic's components list at ~$564 against a $932.93 floor), so pricing above
component value is this catalogue's deliberate model, not an anomaly — do not
"correct" it. The point is only that the number may change when the product type
does. **Record the price before the change, apply the configuration, then check the
price again and confirm with the owner before leaving it live.**

### Applying it

Both become **fixed two-item sets**, so set min = max = 2 and attach both children.

1. Product type → **Mix and Match** (product 70 already carries the type but has no
   children; product 468 is currently `simple` and needs the type set).
2. Attach the two child products named above.
3. Min container size = 2, max container size = 2.

Once done, the contents snippet renders `Contains 2 materials: …` automatically —
no code change needed.

### Wording constraint — carries into the copy

The owner's instruction: these **remain separately packaged research materials** and
must **not be described as a combined blend** unless there is a manufactured,
documented blended vial.

This matters because the catalogue genuinely contains both kinds. Real single-vial
blends exist (`BPC-157 10 mg + TB-500 10 mg Research Blend`, KLOW, the
Semaglutide + Cagrilintide blend) and must keep reading as blends; these panels must
not.

The contents display already complies — it renders `Contains 2 materials: GHK-Cu
50 mg…`, which lists separate items and never says "blend". **The body copy is what
needs checking.** Product 468's current description says it covers "GHK-Cu, NAD+, and
BPC-157", which contradicts the new two-item grouping (NAD+ moves to the draft
Bioenergetics panel). Update the description to match the configured contents, and
make sure neither page implies a single combined vial.

### Verify

```sh
for u in advanced-multi-pathway-research-collection cellular-research-panel; do
  echo -n "$u: "
  curl -s -H 'Cache-Control: no-cache' \
    "https://www.oligopolypeptides.com/products/$u/?nc=$(date +%s)" \
    | grep -o 'opl-cc__count">[^<]*' | sed 's/.*">//'
done
# expect "2 materials" for both once configured
```

### Original finding, for reference

**Do not invent contents.**

| Product | ID | SKU | Price | Mix and Match form | Child products |
|---|---|---|---|---|---|
| Advanced Multi-Pathway Research Collection | 70 | `OP-STK-ADVANCED-MULTIPATHWAY` | listed | **none** | **none** |
| Cellular Research Panel | 468 | `OP-STK-LONGEVITY` | $262.99 shown / $299 in copy | **none** | **none** |

Both are purchasable. Neither defines what the buyer receives.

Product 70 carries the `mix-and-match` product type but renders no form, which
means the type is set and no child products were ever attached. Product 468 is a
plain `simple` product despite being sold as a panel.

**What to do**

1. Confirm with the owner, per product, whether it is (a) a fixed set, (b) a
   pick-N, or (c) discontinued.
2. For (a) or (b): set the product type to **Mix and Match**, attach the child
   products the owner names, and set min/max container size. Min = max = number of
   children for a fixed set; min < children for a pick-N.
3. For (c): unpublish it and add a redirect to a live product or the catalogue —
   see Task 3's note about the last time a product was left half-removed.
4. Reconcile the price discrepancy on 468 ($262.99 rendered, $299 in the FAQ copy
   and specification table). Whichever is right, make both agree.

**Verify**

```sh
curl -s -H 'Cache-Control: no-cache' \
  "https://www.oligopolypeptides.com/products/cellular-research-panel/?nc=$(date +%s)" \
  | grep -c 'mnm_form'      # expect 1 once configured
```

---

## Task 2 — Two collections hold identical materials

**Blocked on the owner.**

| Collection | ID | SKU | Config | Children |
|---|---|---|---|---|
| Neurocognitive Pathways | 3480 | `OP-STACK-NEURO` | 3 of 3 | Selank 5 mg, NAD+ 500 mg, GHK-Cu 50 mg |
| Regenerative Biology | 3483 | `OP-STACK-REGEN` | 3 of 3 | GHK-Cu 50 mg, NAD+ 500 mg, Selank 5 mg |

Same three materials, different order, different names, different positioning.
Almost certainly a configuration copy-paste.

For contrast, these two are coherent and need no change:

| Collection | ID | Config | Children |
|---|---|---|---|
| Metabolic Pathways | 3474 | 6 of 6 | Retatrutide 5 mg, Tirzepatide 10 mg, Cagrilintide 5 mg, NAD+ 500 mg, Selank 5 mg, GHK-Cu 50 mg |
| Cellular Energy | 3477 | 4 of 4 | NAD+ 500 mg, GHK-Cu 50 mg, Selank 5 mg, Cagrilintide 5 mg |

**What to do:** ask the owner which materials each of 3480 and 3483 should
actually contain, then correct the child products on whichever is wrong. Do not
pick for them.

---

## Task 3 — Repair the copy the rename corrupted

**Not blocked. Do this one first if Tasks 1–2 are waiting on the owner.**

Only three products are affected. Damage runs in both directions: over-replacement
on two, under-replacement on one.

### 3a. Cellular Research Panel (ID 468) — over-replaced

`Stack` was replaced inside phrases where it was already followed by other words,
producing doubled nouns. Apply these as **exact, whole-phrase** replacements,
scoped to post 468:

| Find | Replace |
|---|---|
| `Longevity Research Research Panel` | `Longevity Research Panel` |
| `a coordinated research Research Panel` | `a coordinated research panel` |
| `Coordinated research Research Panel` | `Coordinated research panel` |
| `Research Research Panels /` | `Research Panels /` |
| `Longevity Research & Repair Research Research Panel` | `Longevity Research & Repair Panel` |
| `Starter Research Research Panel` | `Starter Research Panel` |
| `Recovery Cellular Research Research Panel` | `Recovery Cellular Research Panel` |

Order matters: run the two longest (`Longevity Research & Repair …`, `Recovery
Cellular …`) **before** the shorter ones, or the short rules will consume their
prefixes and leave fragments.

```sh
wp search-replace 'Longevity Research & Repair Research Research Panel' \
  'Longevity Research & Repair Panel' wp_posts \
  --include-columns=post_content,post_excerpt --dry-run
```

Repeat per row, then re-run without `--dry-run`.

### 3b. Bacteriostatic Water (ID 816) — over-replaced

Same doubled-noun damage in its FAQ. The `Starter Research Research Panel` and
`Recovery Cellular Research Research Panel` rules above cover it.

### 3c. Advanced Multi-Pathway Collection (ID 70) — *under*-replaced

This page was missed by the rename entirely and still says "stack":

| Find | Replace |
|---|---|
| `This stack page gives researchers` | `This collection page gives researchers` |
| `The stack is supplied for research use only` | `The collection is supplied for research use only` |

### 3d. Phantom products referenced with prices

Separate problem, same pages. The copy sells products that are **not in the
catalogue**, with specific prices:

- `MOTS-c + SS-31 Blend ($169)`
- `Epitalon + Pinealon Blend ($139)`
- `Longevity Research & Repair Panel ($349)`
- `Starter Research Panel ($199)`
- `Recovery Cellular Research Panel ($349)`

Bacteriostatic Water's FAQ additionally claims it "is included in the Starter
Research Panel and Recovery Cellular Research Panel" — neither exists.

Confirm with the owner whether these are planned, discontinued, or copy errors,
then remove or correct the references. Do not silently delete paragraphs that may
be about real upcoming products.

**Also note:** product 816 is titled "Bacteriostatic Water **10 mL**", its slug is
`bacteriostatic-water-**30ml**-research-support`, and its body copy says "**30mL**".
Confirm the real volume and make all three agree.

**Verify 3a–3c**

```sh
for u in cellular-research-panel bacteriostatic-water-30ml-research-support \
         advanced-multi-pathway-research-collection; do
  echo -n "$u: "
  curl -s -H 'Cache-Control: no-cache' \
    "https://www.oligopolypeptides.com/products/$u/?nc=$(date +%s)" \
    | grep -oE 'Research Research Panel|This stack page|The stack is supplied' | wc -l
done
# expect 0 for all three
```

---

## Task 3e — Follow-up: two gaps this brief caused

Added 2026-09-02 after verifying the first pass. Both gaps are defects in **this
brief**, not in the work done against it. The agent applied the 20 rules exactly as
written, verified them, and correctly refused to broaden scope when the rules did
not match — which is what it was told to do.

Everything below is scoped to **post 468 only**. No other product is affected: a
scan of all 39 products found body damage on 468 alone and metadata damage on 468
alone.

### 3e-i. The ampersand rule never matched — wrong encoding

Task 3a listed:

    Longevity Research & Repair Research Research Panel

The database stores the ampersand **encoded**, so an exact match on a literal `&`
finds nothing and the rule silently no-ops. Two instances remain in post 468's
FAQ. Run all three encodings; the ones that do not match cost nothing:

```sh
PFX=$(wp db prefix)
for AMP in '&amp;' '&#038;' '&'; do
  wp db query "UPDATE ${PFX}posts
    SET post_content = REPLACE(post_content,
      'Longevity Research ${AMP} Repair Research Research Panel',
      'Longevity Research ${AMP} Repair Panel')
    WHERE ID = 468;"
done
```

Both instances sit inside the phantom-product FAQ (Task 3d) and mention a
`$349` product that is not in the catalogue. Repairing the doubled noun is safe and
independent of that decision — the sentence is malformed either way. If the owner
later deletes the paragraph, this edit is simply discarded with it.

### 3e-ii. SEO metadata was never in scope — it lives in postmeta

This brief scoped every rule to `post_content` and `post_excerpt`. Rank Math keeps
its own title and description in **`wp_postmeta`**, which those rules never
touched. Post 468 therefore still ships:

    <title>Longevity Research Research Panel | OligoPoly Laboratories</title>

Nine instances in total: `<title>`, `meta description`, `og:title`,
`og:description`, `twitter:title`, `twitter:description`, and three inside the
JSON-LD `Product` and `WebPage` blocks. The JSON-LD is generated from the same
fields, so fixing the meta rows fixes all nine.

This is the page title Google indexes and shows, so it is the most publicly visible
instance of the damage that remains.

The `post_title` itself is **clean** (`Cellular Research Panel`) — do not change it.

```sh
PFX=$(wp db prefix)

# 1. See which meta keys hold it, before writing anything.
wp db query "SELECT meta_id, meta_key FROM ${PFX}postmeta
  WHERE post_id = 468 AND meta_value LIKE '%Research Research Panel%';"

# 2. Apply, scoped to post 468.
wp db query "UPDATE ${PFX}postmeta
  SET meta_value = REPLACE(meta_value,
    'Longevity Research Research Panel', 'Longevity Research Panel')
  WHERE post_id = 468 AND meta_value LIKE '%Longevity Research Research Panel%';"

# 3. Flush caches so the head regenerates.
wp cache flush
wp rankmath sitemap generate 2>/dev/null || true
```

### Verify 3e

```sh
U="https://www.oligopolypeptides.com/products/cellular-research-panel/?nc=$(date +%s)"
curl -s -H 'Cache-Control: no-cache' "$U" | grep -o '<title>[^<]*'
# expect: <title>Cellular Research Panel | OligoPoly Laboratories
#     or: <title>Longevity Research Panel | OligoPoly Laboratories
#     NOT: Longevity Research Research Panel

curl -s -H 'Cache-Control: no-cache' "$U" \
  | grep -oE 'Research Research Panel' | wc -l
# expect 0 — this counts head and body together
```

### Still not in scope

Task 3d (the five phantom products with prices, and the 10 mL / 30 mL / slug
disagreement on product 816) and the $262.99-vs-$299 price conflict on 468 remain
owner decisions. Do not resolve them by guessing.

---

## Task 4 — Install the collection-contents snippet

Source: `wordpress/collection-contents.php` in this repo (branch
`claude/wordpress-product-coa-banners-37qjtp`).

This is a **second, separate** WPCode snippet. Do not merge it into the existing
`OligoPoly — product COA banner` snippet; they are independent concerns and must
stay independently removable.

1. WPCode → Add Snippet → Add Your Own → **PHP Snippet**.
2. Title: `OligoPoly — collection contents`.
3. Paste the **entire** file **minus its opening `<?php` line** — WPCode supplies
   its own. A truncated paste leaves the opening `/**` comment unclosed and WPCode
   deactivates the snippet with "Unterminated comment starting line 1". Confirm the
   last line in the editor is a lone `}` before saving.
4. Insertion: **Auto Insert → Run Everywhere**. Save and activate.

**Install Task 4 after Tasks 1–2, or accept that products 70 and 468 will render
nothing** — the snippet deliberately shows nothing rather than guessing, so it
does not create a false claim, but it will visibly skip those two.

Hook priorities interleave with the COA banner snippet on purpose:

| Priority | Renders |
|---|---|
| 6 | COA banner (product page) |
| 7 | contents panel (product page) |
| 8 | contents line (listing card) |
| 9 | COA pill (listing card) |
| 10 | WooCommerce price |

---

## Task 5 — Verify the Mix and Match reads against the live plugin

**This is the one part that has never run against a real WooCommerce install.**
It was written from the site's rendered HTML, not from the plugin's API.

`opl_cc_read_contents()` calls, all guarded with `method_exists`:

- `WC_Product::get_child_items()` — expected to return child-item objects
- each child's `->get_product()` — expected to return a `WC_Product`
- `WC_Product::get_min_container_size()` / `get_max_container_size()`

If the installed WooCommerce Mix and Match version names these differently, the
guards make the snippet render **nothing** rather than fatal — so the failure mode
is silence, not a white screen. That also means a silent failure is easy to miss.

**What to do**

1. Activate the snippet and load
   `/products/metabolic-pathways-research-collection/`.
2. Expect a panel reading **"What this contains · 6 materials"** listing
   Retatrutide 5 mg, Tirzepatide 10 mg, Cagrilintide 5 mg, NAD+ 500 mg,
   Selank 5 mg, GHK-Cu 50 mg.
3. Load `/products/build-your-research-bundle-3-vials/` and expect
   **"What you choose from · pick 3 of 24"**.
4. If either renders nothing, the API names differ. Find the correct accessors on
   the installed plugin version and adjust `opl_cc_read_contents()` only — the
   rendering and wording logic below it is version-independent and correct.
5. Check `wp-content/debug.log` (or enable `WP_DEBUG_LOG`) for notices from the
   snippet on those two page loads.

```sh
curl -s -H 'Cache-Control: no-cache' \
  "https://www.oligopolypeptides.com/products/metabolic-pathways-research-collection/?nc=$(date +%s)" \
  | grep -c 'class="opl-cc"'      # expect 1

curl -s -H 'Cache-Control: no-cache' \
  "https://www.oligopolypeptides.com/products/cellular-research-panel/?nc=$(date +%s)" \
  | grep -c 'class="opl-cc"'      # expect 0 until Task 1 is done
```

---

## Task 6 — Retatrutide 10 mg is listed for sale but unreachable

**Found 2026-09-02 while resolving Task 1. Same bug as product 55, still open.**

| | |
|---|---|
| Product | Retatrutide 10 mg Research Peptide |
| ID | 12 |
| Listed at | `$139.00` on `/research-catalog/`, with a working Add to Cart form (`add-to-cart" value="12"`) |
| Permalink | `/products/retatrutide-10mg-research-peptide/` → **301** → `/retatrutide-source/` (an article) |
| `?p=12` | → **301** → `/retatrutide-source/` |
| In sitemap | no |

A customer browsing the catalogue sees the product at $139.00 and can click Add to
Cart. Every route to the product itself lands on an article instead. This is the
identical failure that took the BPC-157 + TB-500 blend off sale: a redirect rule
shadowing a live product's own URL.

**What to do** — same as product 55:

1. Find it: `wp post get 12 --field=post_status` and
   `wp db query "SELECT ID, post_name, post_status FROM $(wp db prefix)posts WHERE ID = 12;"`
2. If it should be on sale: restore `publish`, and delete the redirect rule sending
   its slug to `/retatrutide-source/` (Rank Math → Redirections).
3. If discontinued: remove it from the catalogue listing so it stops advertising a
   price and an Add to Cart button, and point the redirect at a live product —
   Retatrutide 5 mg (#3395) or 20 mg (#3396) — rather than an article.

Either way it must stop being *listed and priced but unbuyable*, which is the worst
of both.

**Verify**

```sh
curl -s -o /dev/null -w '%{http_code} -> %{url_effective}\n' -L \
  "https://www.oligopolypeptides.com/?p=12"
# expect 200 at a /products/ URL, or the product gone from the catalogue entirely
```

**Worth a sweep:** two instances of this bug have now been found by accident. Before
closing out, check whether any other product is listed in the catalogue but
redirects away from its own permalink:

```sh
curl -s "https://www.oligopolypeptides.com/research-catalog/" \
  | grep -oE 'https://www\.oligopolypeptides\.com/products/[a-z0-9-]+/' | sort -u \
  | while read -r u; do
      c=$(curl -s -o /dev/null -w '%{http_code}' "$u")
      [ "$c" != "200" ] && echo "$c  $u"
    done
# expect no output
```

---

## Do not touch

The COA banner snippet (`OligoPoly — product COA banner`) is live and verified:
29 products with the banner, 10 deliberately excluded, all six category archives,
33 of 43 catalog cards. See `docs/COA-BANNER.md`. Nothing in this brief requires
changing it.
