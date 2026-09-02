# Brief: fix collection data and install the contents snippet

Work order for an agent with WP-CLI or database access to
**oligopolypeptides.com**. Every fact below was read off the live site on
2026-09-01/02 and is reproducible with the commands given.

Tasks are ordered by risk. **Tasks 1 and 2 need a decision from the site owner
before any write.** Do not start there if you cannot get one — do Task 3 onward.

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

**Blocked on the owner. Do not invent contents.**

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

## Do not touch

The COA banner snippet (`OligoPoly — product COA banner`) is live and verified:
29 products with the banner, 10 deliberately excluded, all six category archives,
33 of 43 catalog cards. See `docs/COA-BANNER.md`. Nothing in this brief requires
changing it.
