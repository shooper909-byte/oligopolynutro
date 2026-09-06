# Codex: finish the remediation — repair, verify, publish

Self-contained. Assume no prior context. Supersedes any earlier "complete" report.

**Status: the site is NOT compliant yet.** A previous run reported zero findings. That report
was wrong for two reasons, both fixed in the tooling but **not yet fixed on the live site**.

---

## What went wrong

**1. The scanner could not see tables.** `compliance/scan.py` split page text into sentences,
and the splitter breaks on `<td>` boundaries. A figure in one cell and its label in another
never appeared in the same sentence, so a complete weight-loss efficacy table passed under a
clean report. The scanner is now table-aware and the same live site reports **FAIL — 5
instances on 2 pages**.

**2. A bug in `remediate.py` damaged a table.** `strip_table_column` searched every row for
the header text instead of only the first row. These comparison tables are row-oriented — the
labels sit in column 0 — so matching the row label "Weight loss (highest dose, ~72wk)" deleted
**column 0 from every row**. On `/retatrutide-vs-tirzepatide/` the entire label column is gone
and the table now reads as bare numbers:

```
Tirzepatide | Retatrutide
~22%        | ~28.7% (68wk)
−11.82 kg   | −16.34 kg
```

Unreadable for customers, and still carrying the exact figures the remediation exists to
remove. Note that stripping the labels made this table *harder* to detect, not safer — no
label-word heuristic matches it now. Repair is the fix, not a looser scanner.

Both bugs are fixed in the committed code. Your job is to apply the repair to the live site.

---

## Step 1 — get the current code

```sh
git fetch origin claude/stripe-compliance-fixes-y72vfp
git checkout claude/stripe-compliance-fixes-y72vfp
git pull origin claude/stripe-compliance-fixes-y72vfp
```

You must be on commit `4308c18` or later. Verify:

```sh
git log --oneline -1
grep -c "strip_table_rows" codex/remediate.py     # must be >= 1
grep -c "outcome-table-row" compliance/scan.py    # must be >= 1
```

If either grep returns 0 you have stale code — stop and re-pull.

## Step 2 — credentials

```sh
export WP_SITE=https://www.oligopolypeptides.com
export WP_USER=oligopoly
export WP_APP_PASSWORD='<application password>'
python3 codex/remediate.py check                  # must print OK
```

The username is `oligopoly` — verified, the site has exactly one user (id 1). Do not use the
application password's *label* as the username. Ask the owner for a fresh application password
(wp-admin → Users → Profile → Application Passwords); the previous one should be revoked.

## Step 3 — back up

```sh
python3 codex/remediate.py backup
```

Writes every affected document to `codex/backups/<id>.json`. Do not skip this. To roll one
back, POST the saved `content.raw` to the same endpoint.

## Step 4 — repair

```sh
python3 codex/remediate.py repair                 # dry run — read the diff
python3 codex/remediate.py repair --apply
```

`repair.json` holds three corrective edits, each with an exact `find` and `replace`.
**Pre-flight confirmed on 2026-09-06: all three still match live content.**

| Page | Action |
|---|---|
| 751 `/retatrutide-vs-tirzepatide/` | Rebuild the damaged table from the pre-remediation original — label column restored, outcome rows removed. Ends as: Parameter / Receptor targets / Development stage / GI tolerability |
| 764 `/retatrutide-vs-tirzepatide-research/` | Remove 4 rows from "Published Efficacy Data Comparison" (−11.82 kg, −23.77%, ~22% vs ~28.7%, liver fat 82%). Leaves: header, GI adverse events, head-to-head trial |
| 1412 `/retatrutide-vs-semaglutide/` | Remove the "Hepatic fat reduction … Up to 82% (Phase 2)" row |

If a `find` string is not located, the page changed after `repair.json` was generated. The
script says so and skips — **report it, do not force it or hand-edit around it.**

## Step 5 — verify, and do not stop before it is clean

```sh
python3 codex/remediate.py verify
python3 compliance/scan.py --refresh
```

**`--refresh` is required.** Without it the scanner reads its cache and reports stale content.

**The finish line is `PASS: no P0/P1 findings` and exit status 0.** Nothing else counts as
done. Check the exit code explicitly:

```sh
python3 compliance/scan.py --refresh; echo "exit=$?"
```

Expected final state:

```
[P0] Internal build note published live: clean
[P1] Human weight-loss / body-composition outcome figure: clean
[P1] Benefit-framed naming or heading: clean
[P1] Human-outcome figure inside a comparison table: clean
[P0] Dosing or reconstitution calculator / interactive tool: clean
PASS: no P0/P1 findings.
```

If any P1 remains, read the reported rows, extend the fix, and re-run. Do not report success
against a scanner version lacking the `outcome-table-row` check.

## Step 6 — spot-check by eye

Open these three pages in a browser and confirm each table has a label column and reads
sensibly. The failure this repairs was invisible to the scanner but obvious to a human:

- https://www.oligopolypeptides.com/retatrutide-vs-tirzepatide/
- https://www.oligopolypeptides.com/retatrutide-vs-tirzepatide-research/
- https://www.oligopolypeptides.com/retatrutide-vs-semaglutide/

---

## Expected to remain — do not "fix" these

- **P2 restricted naming, ~1,866 instances.** Blocked on written guidance from VERIFIED. The
  requirement doc forbids renaming unilaterally. **Do not rename any product**, invent
  alternative names, or alter SKUs or slugs. This count staying high is correct.
- **P2 "Route studied: Subcutaneous models"** on `/half-life-pharmacokinetics-hub/` — describes
  a preclinical model, not human administration. Defensible as written.
- **Bacteriostatic water** — still non-purchasable, blocked by Beacon. Leave it. Do not set a
  price or work around the restriction. Two questions are still open with the owner: whether
  $19.99 is right for 10 mL, and whether the Starter and Recovery Cellular panels actually
  ship the 10 mL vial.
- **Broken JSON-LD on post 2079** (`/epitalon-pinealon-research-comparison/`) — unescaped
  double quotes around `"10mg"`. Pre-existing, present before any remediation. Worth fixing
  separately; not part of this pass.

## Never

Do not add hidden text, euphemisms, near-miss spellings, or markup tricks to move a claim past
the scanner. The goal is a site that is genuinely compliant. A compliance plugin is live on the
site (`POST /wp-json/compliance-monitor/v1/collect`), and the requirement doc makes monitoring
continuous — anything cosmetic surfaces later, at underwriting or after approval.

Do not touch checkout, cart, payment, shipping, tax, pricing, customer accounts, the age gate,
or URL slugs.

## Report back

State: the commit you ran from, which repair entries applied or skipped, the scan output and
**exit code** before and after, and anything left outstanding. If `scan.py --refresh` does not
end in `PASS` with exit 0, say so plainly rather than summarising it as complete.
