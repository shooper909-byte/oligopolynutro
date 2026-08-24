# Handoff — three WPCode snippets left to install

**Site:** https://www.oligopolypeptides.com — WordPress.com-hosted Jetpack site,
blog ID `254585378`. WooCommerce + Mix and Match. Theme: Hello Elementor.

**Repo:** `shooper909-byte/oligopolynutro`
**Branch:** `claude/homepage-width-empty-space-mrdjp8` (commit `27f2903`)

Everything is written and tested. What remains is **installation**, plus
verification that it took effect.

---

## The access constraint — read this first

Snippets live in the **WPCode** plugin (`insert-headers-and-footers`). They are
not files in this repo's deploy path; they are rows in the site database, edited
through wp-admin.

`POST /wp-json/code-snippets/v1/snippets` exists but returns **401** without site
credentials. If you have an application password or SSH/WP-CLI, you can automate
this. If not, it is a manual paste in wp-admin and your job is to verify the
result rather than perform it.

Do not attempt to work around the 401 by other means.

---

## Rule that has already broken twice

**Overwrite the existing snippet. Do not create a second one.**

Every snippet declares uniquely-prefixed functions. Two active snippets
declaring the same function produce:

```
Cannot redeclare function opl_pcb_catalog_url()
```

WPCode refuses to activate the second one, which is safe but leaves the old code
running. In wp-admin: open the **existing** snippet, select all in the code box,
paste over it, Update. An *inactive* snippet declares nothing and cannot
collide, so deactivating the old one before activating a new one also works.

Do **not** add `function_exists()` guards to solve this. That was tried earlier
in this project and inverted the failure: a stale copy silently suppressed a
newer paste, and the cause took three rounds to find.

---

## Prefix → snippet map

Identify a snippet by the function prefix in its code, not by its title.

| Prefix | Snippet | Touch? |
|---|---|---|
| `opl_pcb_` | product-cart-buttons | done, leave |
| `opl_pcat_` | product-category-repair | **replace, task 1** |
| `opl_fsl_` | footer-shop-links | **create, task 2** |
| `opl_cl_` | coa-library-page | **replace, task 3** |
| `opl_coa_` | research-coa-figure | **do not touch** |
| `opl_facts4_` | product-card-facts | do not touch |
| `oplrs_` | research-stacks-page | do not touch |

`opl_coa_` and `opl_cl_` are the pair most easily confused. `opl_coa_` puts a
figure on `/research/`; `opl_cl_` builds the certificate library on
`/research-peptides-with-coa/`.

---

## Baseline — verified live before handoff

Run these to confirm nothing drifted before you start.

```sh
BASE=https://www.oligopolypeptides.com
N=$(date +%s)$RANDOM   # cache-buster: this site sits behind batcache

# every page 200, no PHP errors, footer present
for p in "" research-catalog/ research-peptides-with-coa/ research/ cart/; do
  curl -s "$BASE/$p?nc=$N" -o /tmp/p.html -w "$p %{http_code} "
  echo "phperr=$(grep -ci 'Fatal error\|Cannot redeclare' /tmp/p.html)"
done
```

Already working, must not regress:

| Check | Expected |
|---|---|
| Homepage kit cards | 7 `<h3>` ending "Vial Research Kit" |
| Homepage cart forms | 7 × `class="opl-pcb-form"` |
| `/research/` figure | `opl-coa-fig` present |
| `/product-category/research-stacks/` | 7 products |
| metabolic / cellular / longevity / cognitive-research | 6 / 4 / 3 / 2 |

---

## Task 1 — `product-category-repair` v3 (replace, run once, then delete)

**File:** `wordpress/product-category-repair.wpcode.txt` (447 lines, pure ASCII,
no leading `<?php`)

A previous revision already ran and did part of the job. Two bugs were found by
checking the live result:

1. **Uncategorized survived on 8 products.** The removal was nested inside the
   "gained a category" branch; the 8 single-compound kits already had their
   categories from the earlier run, so they were `continue`d before reaching it.
2. **The sweep deleted 22 of 48 terms.** Product categories are hierarchical and
   the sweep skips a parent while it still has children, so one pass only clears
   leaves.

v3 fixes both and bumps the guard `opl_pcat_done_v2` → `opl_pcat_done_v3` so it
runs again. Everything in it is idempotent.

**Steps**

1. Overwrite the existing `opl_pcat_` snippet with the file. Insert Method:
   Auto Insert → Run Everywhere. Save, Activate.
2. Load any wp-admin page. That is the trigger — it fires on `admin_init` and
   is gated on `manage_woocommerce`, so it never runs on a front-end request.
3. Verify (below).
4. **Delete the snippet.** It is a migration, not a feature.

**Verify**

```sh
BASE=https://www.oligopolypeptides.com; N=$(date +%s)$RANDOM

# Uncategorized must be empty
curl -s "$BASE/wp-json/wp/v2/product?product_cat=15&per_page=100&_fields=id&nc=$N" \
  | python3 -c "import json,sys;print('uncategorized:',len(json.load(sys.stdin)),'(want 0)')"

# category count
curl -s "$BASE/wp-json/wp/v2/product_cat?per_page=100&_fields=id&nc=$N" \
  | python3 -c "import json,sys;print('categories:',len(json.load(sys.stdin)),'(want 18)')"
```

Before: uncategorized 8, categories 44. After: **0** and **18**.

If the count is above 18 but below 44, the sweep is partway. Delete the option
`opl_pcat_done_v3` and load wp-admin again; the loop is bounded to 6 passes per
run and is safe to repeat.

**Safety properties — preserve these if you modify it.** Terms are appended, not
replaced; the single exception is removing `uncategorized`, and only when a real
category remains. No product name, price, stock, SKU, description or meta is
written. Term deletion re-verifies at run time — it counts products directly
rather than trusting `$term->count`, because `research-catalog` reports `count =
0` while actually holding 7 products and would otherwise be destroyed. 17 slugs
are protected: the 7 it fills, 5 retired ones serving deliberate HTTP 410
(deleting the term would downgrade that to an accidental 404), 4 still linked
from catalogue cards, and `uncategorized`.

---

## Task 2 — `footer-shop-links` (create new)

**File:** `wordpress/footer-shop-links.wpcode.txt` (172 lines)

The footer's Shop column links to five categories that return **HTTP 410 Gone** —
Vitamins, Longevity, Performance, Cognitive Support, Wellness. Leftovers from a
retired supplement catalogue. The 410 is correct and deliberate; the links are
not. They appear on **every page of the site**.

This is a genuinely new snippet — nothing to collide with. Create it: Auto
Insert → Run Everywhere.

It rewrites the Shop nav to categories that actually hold products, checked at
render time. An empty category is skipped rather than becoming the next dead
link, and **if no candidate qualifies the footer is left exactly as-is** rather
than emptied. Do task 1 first, or the candidates may not be populated yet.

**Verify**

```sh
curl -s "https://www.oligopolypeptides.com/?nc=$(date +%s)" \
| python3 -c "
import re,sys
h=sys.stdin.read(); i=h.find('<footer'); f=h[i:h.find('</footer>')]
for m in re.finditer(r'product-category/([a-z0-9-]+)/',f): print(m.group(1))"
```

Expected: `metabolic-research`, `cellular-research`, `longevity-research`,
`cognitive-research`, `research-stacks`. None of the five 410 slugs.

---

## Task 3 — `coa-library-page` (replace)

**File:** `wordpress/coa-library-page.wpcode.txt` (1667 lines)

The page works, but two structured-data defects remain that were fixed in the
repo and never installed:

1. A site-wide snippet (`opb-phase3-faq-schema`) publishes three FAQ questions
   for this URL that the redesign removed. The markup describes content no
   visitor can see, which breaks Google's rule that FAQ markup must correspond
   to visible content. This build strips that block on **page 1652 only**.
2. Three `BreadcrumbList` graphs, two sharing the same `@id` with conflicting
   names. This build emits **no schema of its own** and leaves the site's alone.

It also routes `noindex` for `?batch=` lookups through Rank Math's own filters
rather than printing a second, conflicting `robots` tag.

Overwrite the existing `opl_cl_` snippet.

**Verify**

```sh
curl -s "https://www.oligopolypeptides.com/research-peptides-with-coa/?nc=$(date +%s)" -o /tmp/c.html
grep -c '"FAQPage"' /tmp/c.html          # want 0 (currently 1)
grep -c 'BreadcrumbList' /tmp/c.html     # want 2 (currently 3)
grep -c 'page-scoped removal of FAQ' /tmp/c.html   # want 1

# a batch lookup must be noindex with a clean canonical
curl -s "https://www.oligopolypeptides.com/research-peptides-with-coa/?batch=TEST-123" \
| grep -oE '<meta name="robots"[^>]*>|<link rel="canonical"[^>]*>'
```

Expected: exactly one robots tag reading `noindex,follow`, one canonical on
`https://www.oligopolypeptides.com/research-peptides-with-coa/`.

---

## Test suites

All pass at commit `27f2903`. Run before changing anything.

```sh
php wordpress/product-category-repair.test.php                       # 95/95
php wordpress/footer-shop-links.test.php <captured-homepage.html>    # 30/30
node wordpress/coa-library-page.test.js                              # 143/143
php wordpress/product-cart-buttons.test.php <home.html> <catalog.html>  # 53/53
```

The PHP suites stub WordPress and run offline. The Playwright suite renders the
shortcode through `coa-library-page.stub.php` and drives it in Chromium.

Capture a page for the footer suite with:
`curl -s "https://www.oligopolypeptides.com/?nc=$(date +%s)" -o /tmp/home.html`

---

## Do not

- Publish any COA, batch number, laboratory, date, signature, purity figure or
  test result. **Zero certificate records exist on this site** — both COA post
  types are empty and the media library holds no PDFs across 692 attachments.
  The certificate library renders an honest empty state and specimen cards whose
  every value is a symbol (`OP-######-XXX`, `DD Month YYYY`). Keep it that way.
- Render a cart control that cannot work. The 10 individual compounds carry Mix
  and Match's "not sold separately" flag: they are priced but WooCommerce
  refuses to sell them alone. A disabled or decorative "Add to Cart" is worse
  than none.
- Touch `opl_coa_`, `opl_facts4_` or `oplrs_`.
- Delete a product category that something links to, or one serving a 410.
- Trust `$term->count`. It is out of step with reality on this site.
- Trust the WooCommerce Store API's prices. It reports empty prices and
  `is_purchasable: false` to unauthenticated readers for **every** product; the
  real prices are $64.99–$524.94. This misled an earlier pass into reporting the
  store had no pricing. Read prices server-side, or from a real cart.

---

## Known remaining, not in scope

- Four categories (`growth-hormone-research`, `immune-research`,
  `recovery-research`, `research-blends-cat`) are empty but linked from
  catalogue cards. Deleting them trades an empty page for a 404. They need the
  card markup changed first — a content decision.
- Duplicate terms survive: `research-catalog` and `research-compounds` are both
  named "Research Compounds". `research-catalog` holds 7 products, so it cannot
  simply be deleted; merging is a decision for the owner.
- No Lighthouse run on the deployed `/research-peptides-with-coa/`.
- Cross-browser testing is Chromium only.
