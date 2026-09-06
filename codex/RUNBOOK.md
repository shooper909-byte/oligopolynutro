# Codex runbook — apply the Stripe/VERIFIED RUO remediation to oligopolypeptides.com

Self-contained. Assume no prior context.

**Goal:** bring `https://www.oligopolypeptides.com` into line with the VERIFIED Credit Card
Processing RUO requirements (August 2026 guidance) so it can pass the compliance scan that
gates Stripe processing.

**Full findings:** [`../compliance/AUDIT-2026-09-05.md`](../compliance/AUDIT-2026-09-05.md).

---

## 0. Before you touch anything

**Read this section. Two rules here are not negotiable.**

**Do not rename any product.** Eleven products carry names the requirement doc lists as
restricted (Retatrutide, Semaglutide, Tirzepatide, Cagrilintide, Bacteriostatic Water). The
doc says explicitly: *"Do not independently rename or obscure products… confirm the current
accepted naming convention through VERIFIED before publishing or submitting."* Renaming is
blocked on a written answer from VERIFIED that has not arrived. Candidate schemes are in
[`../compliance/NAMING-OPTIONS.md`](../compliance/NAMING-OPTIONS.md) — **proposals to submit,
not instructions to execute.** Nothing in this runbook renames a product, and you should not
add it.

**The goal is a site that is actually compliant, not a site that defeats a scanner.** Every
change below removes a human-outcome claim and replaces it with accurate receptor
pharmacology. Do not invent euphemisms, near-miss spellings, or hidden text to carry the old
meaning past the filter. That is the "obscuring" the doc prohibits, and it fails later —
at underwriting, or at the ongoing monitoring the doc describes in §5.

**A compliance plugin is already live on the site.** `POST /wp-json/compliance-monitor/v1/collect`
exists, so assume changes are observed. All the more reason the changes should be real.

---

## 1. Credentials and environment

WordPress **Application Password** over the site's own REST API. Jetpack is *not* used —
Jetpack Sync on this site has been dead since 2026-08-28 and the WordPress.com proxy path
returns `Unknown Token`. Ignore Jetpack entirely.

```sh
export WP_SITE=https://www.oligopolypeptides.com
export WP_USER=oligopoly                       # verified: the site has exactly one user, id 1
export WP_APP_PASSWORD='xxxx xxxx xxxx xxxx xxxx xxxx'
```

Get the password from **wp-admin → Users → Profile → Application Passwords**. Spaces are
stripped automatically. **Revoke it when this work is finished.**

Confirmed working: Application Passwords are advertised at `/wp-json/`, REST accepts Basic
auth, and `wp/v2/posts`, `wp/v2/pages` and `wp/v2/product` are all exposed.

```sh
python3 codex/remediate.py check      # must print "OK" before continuing
```

If `check` reports no `raw` content, the account lacks edit rights — stop and report that.

---

## 2. Ground rules for execution

1. **Back up first.** `python3 codex/remediate.py backup` writes every affected document to
   `codex/backups/<id>.json`. Do not skip it. To roll one back, POST the saved
   `content.raw` to the same endpoint.
2. **Dry-run everything.** Every command defaults to a dry run and prints a diff. Read it.
   Only then re-run with `--apply`.
3. **One phase at a time**, verifying between phases. Do not chain all phases blind.
4. **Never touch** checkout, cart, payment, shipping, tax, pricing, customer accounts, or
   the age gate. The age gate and the sitewide RUO disclaimers already pass — leave them.
5. **Do not change URL slugs** in this pass. Slug changes need 301s and are out of scope.
6. If a diff looks wrong, stop and report rather than forcing it.

---

## 3. Phases

```sh
python3 codex/remediate.py backup
python3 codex/remediate.py p0            # review the diff
python3 codex/remediate.py p0 --apply
python3 codex/remediate.py p1
python3 codex/remediate.py p1 --apply
python3 codex/remediate.py products      # read §3.3 first — has open questions
python3 codex/remediate.py verify
python3 compliance/scan.py --refresh
```

### 3.1 P0 — internal build note (5 posts: 2074, 2075, 2077, 2078, 2079)

Five published posts carry an internal note reading *"uses the Claude package as source
material and was **sanitized** for public research-use-only deployment."* To an underwriter
"sanitized" reads as an admission the page was scrubbed to pass review. It is the highest
damage-per-word text on the site and pure deletion.

The script removes, on each post:

- `<section id="introduction">` containing the note — heading and paragraph together
- the table-of-contents entry and `<h2>` for "Source-derived comparison guide" (internal vocabulary)
- a stray `<p># Title</p>` markdown heading rendering as body text with a visible `#`

**Verified offline against live content on all five posts:** the leak and the markdown
heading are removed cleanly, ~530 characters each. This phase is low risk.

### 3.2 P1 — human-outcome claims (22 pages)

The finding that decides the scan. The requirement doc §1: *"Do not make personal-benefit,
weight-loss, muscle-building, anti-aging, therapeutic… or similar outcome claims."*

The content is accurate and cited. That does not save it: a hero tile reading
**"~28.7% Retatrutide Weight Loss (68wk)"** next to an add-to-cart button reads as a
weight-loss claim on a purchasable product. The citation makes it worse — it supplies the
efficacy figure a non-research buyer is shopping for.

Four transformations, all driven by [`targets.json`](targets.json) (43 verified targets):

| Shape | Action |
|---|---|
| Stat tiles (`<div class="pl-stat-item">`) | Number/label replaced with receptor counts — Retatrutide → `3 Receptor targets (GLP-1R / GIPR / GCGR)`, Tirzepatide → `2`, Semaglutide → `1` |
| Comparison-table columns | Whole column removed, header and body cells, via a table parser — not regex |
| Body prose | Replaced with pre-written receptor-pharmacology rewrites (`PROSE_MAP`) |
| Benefit-framed names | "Anti-Aging & Repair Research Panel" → "Longevity & Repair Pathway Panel", "Fat Loss Research" → "Metabolic Pathway Research Panels", etc. |

**Receptor count is the honest differentiator** between these compounds and the thing a
laboratory buyer actually selects on. It carries the comparison without an outcome claim.

**FAQ JSON-LD is in scope and is easy to miss.** Several pages repeat the same efficacy
figures inside `<script type="application/ld+json">` FAQPage schema — invisible to a reader,
fully visible to a scanner. `TEXT_SUBS` rewrites these as plain text. All replacements are
free of quotes and angle brackets so they cannot break the JSON.

**Measured offline against live content: 56 flagged terms → 0.** Verify the diff anyway.

> **Pre-existing bug, not yours:** the JSON-LD on post 2079
> (`/epitalon-pinealon-research-comparison/`) already fails to parse — unescaped double
> quotes around `"10mg"` inside a JSON string. It was broken before any edit. Worth fixing
> separately; do not let it look like this pass caused it.

### 3.3 Products — catalog accuracy (not compliance)

**Bacteriostatic water** (`/products/bacteriostatic-water-30ml-research-support/`, SKU
`OP-AUX-BACWATER-10ML`, $19.99) is internally contradictory: 14 mentions of 30 mL against 9
of 10 mL, and **two different SKUs on one page** — `OP-AUX-BACWATER-30ML` in the spec table
beside `OP-AUX-BACWATER-10ML` in the sidebar. The owner confirms it is the **10 mL** product.
Target state: 10 mL everywhere, single SKU `OP-AUX-BACWATER-10ML`.

**Two questions you cannot answer yourself — ask the owner before applying:**

1. Is **$19.99** still correct for 10 mL, or was it priced for 30 mL?
2. Do the **Starter Research Panel** and **Recovery Cellular Research Panel** actually ship
   the 10 mL vial? If they were built around 30 mL, editing the text moves the error rather
   than fixing it.

The script handles the product body. **Title, short description, tags and the Rank Math SEO
title are separate fields** — see the field-by-field table in
[`../compliance/PRODUCT-DATA-FIXES.md`](../compliance/PRODUCT-DATA-FIXES.md).

**Rank Math fields are not writable over `wp/v2`** — confirmed: `_fields=meta` returns only
`_uag_custom_page_level_css` and `footnotes`. SEO titles and descriptions must be edited in
wp-admin by hand. Do not spend time trying to script them.

**Retatrutide 10 mg** — title reads "Research **Material**" while its siblings read "Research
Peptide" and its own slug says `research-peptide`. One field. Do **not** change the SKU.

---

## 3.4 REPAIR — run this if phase 3.2 was applied before 2026-09-06

The first release of `remediate.py` had a bug in `strip_table_column`: it searched
**every** row for the header text, not just the first. Several of these tables are
row-oriented — the first *column* holds the labels — so matching a label like
"Weight loss (highest dose, ~72wk)" deleted **column 0 from every row**, stripping the
label column and leaving a table of bare numbers that still carried the figures.

The bug is fixed (the header is now only matched in the first row, and `strip_table_rows`
handles row-oriented tables). If the buggy version already ran:

```sh
python3 codex/remediate.py repair          # dry run
python3 codex/remediate.py repair --apply
```

`repair.json` carries three corrective edits, each with the exact `find` and `replace`:

| Page | What it does |
|---|---|
| 751 `/retatrutide-vs-tirzepatide/` | Rebuilds the damaged table from the pre-remediation original, with the label column restored and the outcome rows removed |
| 764 `/retatrutide-vs-tirzepatide-research/` | Removes 4 rows from the "Published Efficacy Data Comparison" table |
| 1412 `/retatrutide-vs-semaglutide/` | Removes the "Hepatic fat reduction … Up to 82%" row |

If a `find` string is not located, the page has changed since `repair.json` was generated —
the script says so and skips rather than guessing. Report it instead of forcing it.

---

## 4. Verification

```sh
python3 codex/remediate.py verify
python3 compliance/scan.py --refresh
```

**Baseline before any work: 53 P0/P1 instances.** Target after P0 + P1 + repair: **0**.

`scan.py` now scans **table rows separately**. It previously split text into sentences,
which broke on cell boundaries — a figure in one `<td>` and its label in another never
appeared together, so an intact weight-loss efficacy table passed a clean report. Do not
trust a PASS from a scanner version without the `outcome-table-row` check.

`scan.py` exits non-zero while any P0/P1 finding remains, so it can gate a deploy. The P2
restricted-naming count (~1,885) **will stay high and that is correct** — it counts every
mention of a restricted compound name and is blocked on VERIFIED. Do not drive it to zero.

Spot-check 3–4 pages in a browser afterwards. The table-column removal changes column counts;
confirm nothing renders visibly broken.

---

## 5. What is out of scope

Do not attempt these without a further decision from the owner:

- **Renaming products or editorial mentions** — blocked on VERIFIED (§0).
- **Rewriting the ~100-page editorial corpus.** Renaming products while 104 pages still say
  *Retatrutide* and rank for it is a separate, larger decision with real traffic cost. See
  `NAMING-OPTIONS.md`. Do not start it here.
- **Slug changes and 301s.**
- **AI-generated product imagery** — 20 files named `ChatGPT-Image-*.png` served across all 40
  product pages. Needs re-upload under SKU-derived names and ideally real photographs; it is a
  media task, not a text edit.
- **Fixing Jetpack Sync** — broken since 2026-08-28. Real, worth a host ticket, unrelated.

---

## 6. Report back

State plainly: which phases were applied, the scan count before and after, anything skipped
and why, and the two bacteriostatic-water questions if still unanswered. If a phase was
partially applied, say exactly where it stopped. Do not report success unless `verify` and
`scan.py` both confirm it.
