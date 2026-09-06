# Codex: product category cleanup — remove consumer-wellness taxonomy

Self-contained. Assume no prior context. Run **after** [FIX-NOW.md](FIX-NOW.md).

**This is a finding no scan has reported.** `compliance/scan.py` crawls the Rank Math
sitemap, and these category archives are not in the sitemap — so they have been invisible to
every scan run so far, while being live and publicly reachable the whole time.

---

## The finding

Your WooCommerce install still carries **20 product categories from an earlier
supplement/wellness configuration** (15 children plus their 5 parents). Every one returns **HTTP 200** with a rendered archive
page. Titles read, verbatim:

> `Strength & Athletic Performance | Research Peptides | OligoPoly Laboratories`
> `Sleep Support | Research Peptides | OligoPoly Laboratories`
> `Gut Health | Research Peptides | OligoPoly Laboratories`

On a research-use-only peptide site, a category called **Strength & Athletic Performance** is
a muscle-building claim and **Sleep Support** is a human-benefit claim — §1 of the requirement
doc. Collectively they present the site as a supplement store, which is §2 *"Website structure
& messaging — navigation, landing pages, collections, menus… should consistently support the
approved business model."*

**They are `noindex`, which is not a defence.** `noindex` speaks to search engines. It does
not stop a compliance crawler following internal links, and it does not stop a human reviewer
opening the URL. The compliance plugin live on this site
(`POST /wp-json/compliance-monitor/v1/collect`) is not a search engine.

**All 15 contain zero products** — they are vestigial, not load-bearing.

## The terms

Exact IDs in [`category-cleanup.json`](category-cleanup.json).

| ID | Name | Slug | Why it fails |
|---|---|---|---|
| 518 | Strength & Athletic Performance | `strength-and-athletic-performance` | muscle-building / performance claim |
| 521 | Bone & Muscle Support | `bone-and-muscle-support` | muscle-building claim |
| 519 | Sleep Support | `sleep-support` | human benefit claim |
| 516 | Gut Health | `gut-health` | human health claim |
| 510 | Daily Foundation | `daily-foundation` | implies daily human consumption |
| 508 | Vitamins & Wellness | `vitamins-wellness` | supplement framing; contradicts RUO |
| 520 | Stress & Adaptogen Support | `stress-and-adaptogen-support` | human benefit claim |
| 511 | Immune & Antioxidant | `immune-and-antioxidant` | human benefit claim |
| 513 | Energy & Cognitive Support | `energy-and-cognitive-support` | human benefit claim |
| 509 | Bone & Immune Support | `bone-and-immune-support` | human benefit claim |
| 512 | Cardiovascular & Brain Support | `cardiovascular-and-brain-support` | human organ-system benefit |
| 517 | Digestive & Immune Support | `digestive-and-immune-support` | human benefit claim |
| 514 | Immune & Metabolic Support | `immune-and-metabolic-support` | human benefit claim |
| 515 | Mitochondrial & Cardiovascular | `mitochondrial-and-cardiovascular` | "cardiovascular" implies human organ-system benefit |
| 616 | `615` | `615` | junk term from a data error — no real name |

### The five "already handled" parents are not actually gone

Vitamins (504), Wellness (506), Performance (183), Longevity (180) and Cognitive Support (505)
return HTTP 410 **at their own URL** — but the terms still exist, and the 15 above are their
**children**, reachable at nested URLs that return 200:

```
/product-category/vitamins/vitamins-wellness/sleep-support/          200
/product-category/performance/strength-and-athletic-performance/     200
/product-category/wellness/gut-health/digestive-and-immune-support/  200
```

So the earlier cleanup 410'd the parent URLs and stopped. **Delete all 20 terms** — the 15
children and the 5 parents, which are themselves supplement-framed and also hold zero products.

**Delete children before parents.** Deleting a parent term in WordPress promotes its children
to top level rather than removing them, which would leave "Sleep Support" sitting at
`/product-category/sleep-support/` — visible, and no longer under a 410'd parent.

## Delete, do not rename

Renaming keeps an empty term alive under a new name for no benefit. These hold zero products,
sit outside the sitemap, and are already noindexed — deleting removes the exposure completely
at zero SEO cost. Then serve **410 Gone**, consistent with the five already done. 410 is
correct here: the content is intentionally removed, not moved.

Do **not** 301 them into research categories. That would tell crawlers "Sleep Support" is an
alias for a research category, which re-associates the claim with live content.

## Steps

**1. Confirm each is genuinely empty before deleting.** Do not trust the table above:

```sh
for id in 508 509 510 511 512 513 514 515 516 517 518 519 520 521 616; do
  curl -s -u "$WP_USER:$WP_APP_PASSWORD" \
    "$WP_SITE/wp-json/wp/v2/product?product_cat=$id&per_page=1&_fields=id" \
    -o /tmp/c.json -w "cat $id -> " ; python3 -c "import json;print(len(json.load(open('/tmp/c.json'))),'product(s)')"
done
```

**Any term returning more than 0 products: stop and report it. Do not delete it.** A populated
term means products would lose their category assignment.

**2. Delete the empty terms.** WooCommerce categories are the `product_cat` taxonomy:

```sh
curl -s -u "$WP_USER:$WP_APP_PASSWORD" -X DELETE \
  "$WP_SITE/wp-json/wp/v2/product_cat/<ID>?force=true"
```

Taxonomy terms have no trash — `force=true` is required and the delete is final. That is why
step 1 is not optional. Alternatively use **wp-admin → Products → Categories** and delete by
hand; 15 terms is quick and gives you a visual confirmation of the product count per term.

**3. Serve 410 for the removed URLs.** After deletion the URLs will 404. Configure 410 for
each `/product-category/<slug>/` using the same mechanism that produced 410 for the five
already-removed categories — check the **Redirection** plugin first (`redirection/v1` is a live
REST namespace on this site), since that is the likely place the existing rules live.

**4. Verify.**

```sh
for s in strength-and-athletic-performance sleep-support gut-health vitamins-wellness \
         daily-foundation stress-and-adaptogen-support bone-and-muscle-support \
         immune-and-antioxidant energy-and-cognitive-support bone-and-immune-support \
         cardiovascular-and-brain-support digestive-and-immune-support \
         immune-and-metabolic-support mitochondrial-and-cardiovascular 615; do
  printf "%-42s " "$s"
  curl -s -o /dev/null -w "%{http_code}\n" "$WP_SITE/product-category/$s/"
done
```

All must return **410** — test the nested URLs in `category-cleanup.json`, not the flat ones. Then re-run the compliance scan:

```sh
python3 compliance/scan.py --refresh; echo "exit=$?"
```

## Out of scope

- **Do not touch the research-framed categories.** Metabolic Research, Cellular Research,
  Longevity Research, Recovery Research, Research Blends, Research Kits, ORII Research
  Collections, Discovery Collections and the rest are correctly framed. Several are also empty
  — that is housekeeping, not compliance, and not this task.
- **Do not rename any product or compound.** Blocked on written guidance from VERIFIED. Product
  titles were checked: **zero** carry benefit framing, so nothing is needed there.
- **Do not touch product categories that contain products**, even if you dislike the name.

## Report back

Which terms you confirmed empty, which you deleted, which you skipped and why, the 410
verification output, and the scan exit code. If any term held products, name it and leave it
alone.
