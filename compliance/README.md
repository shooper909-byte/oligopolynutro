# Compliance

Remediation tracking for the VERIFIED / Stripe RUO website review of
[oligopolypeptides.com](https://www.oligopolypeptides.com).

- **[AUDIT-2026-09-05.md](AUDIT-2026-09-05.md)** — findings, priorities, and drop-in
  replacement copy. Start here.
- **[scan.py](scan.py)** — re-runnable scanner. The requirement doc makes ongoing
  monitoring a merchant obligation (§5), so this exists to be run again, not once.

## Running the scanner

```sh
python3 compliance/scan.py                # crawl (cached) and report
python3 compliance/scan.py --refresh      # discard the cache and re-crawl
python3 compliance/scan.py --json out.json
```

No dependencies beyond the Python 3 standard library. It crawls all 234 sitemap URLs as an
anonymous visitor and caches them in `compliance/.cache/` (gitignored) — pass `--refresh`
after making live edits, or the scan reports stale content.

Exit status is **1** when any P0 or P1 finding remains, so it can gate a deploy or run in CI.

## Baseline — 2026-09-05

```
Scanned 234 pages · 71 boilerplate sentences excluded
[P0] Internal build note published live:                 10 instances / 5 pages
[P1] Human weight-loss / body-composition outcome figure: 17 instances / 13 pages
[P1] Benefit-framed naming or heading:                    15 instances / 14 pages
[P2] Dosing / administration guidance:                     1 instance  / 1 page
[P2] Cycle / stack / personal-use protocol content:       clean
[P0] Dosing or reconstitution calculator:                 clean
[P2] Restricted product naming (flag to VERIFIED):      1591 instances / 153 pages
FAIL: 42 P0/P1 instance(s) outstanding.
```

## Reading the output

**P0 and P1 are the work.** They are what the review is expected to flag, and the audit
carries replacement copy for each.

**The P2 restricted-naming count is informational and will stay high.** It counts every
mention of a restricted compound name across the site. It is *not* 1,591 things to fix —
the requirement doc forbids renaming these unilaterally and directs merchants to confirm
current accepted nomenclature with VERIFIED first. The number is there to show scale when
that conversation happens; it should not be driven to zero locally.

The single P2 dosing hit (`/half-life-pharmacokinetics-hub/` — "Route studied: Subcutaneous
models") is preclinical model description, not human administration guidance. It is
defensible as written; flagged for awareness, not queued for removal.

## Two things the scanner cannot do

It matches **language patterns**, so it will not catch a benefit claim made only in an
image, a vial label, or a PDF — all of which §2 puts in scope. And a clean run is not a
compliance certification; it is a pre-submission check before the §4 step-05 final review.
