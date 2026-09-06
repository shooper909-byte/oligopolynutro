# Naming options for the restricted metabolic compounds

Working options for renaming Semaglutide, Tirzepatide, Retatrutide and Cagrilintide, plus
the decision that actually determines whether a rename works.

**These are candidates to put to VERIFIED, not a scheme to apply.** The requirement doc is
explicit — *"Do not independently rename or obscure products… confirm the current accepted
naming convention through VERIFIED before publishing."* The point of this file is to arrive
at that conversation with a concrete proposal instead of an open question, which is usually
what makes it a one-round exchange.

---

## The line that matters

There are two very different things a rename can be, and they look identical in a spreadsheet:

**A different identifier for the same compound.** Catalog codes, CAS numbers, systematic
descriptors. A qualified buyer identifies the material from the documentation; the product
is what it says it is. This is ordinary practice for reference materials — Sigma-Aldrich and
Tocris list plenty of compounds where the catalog number is the primary handle.

**A label chosen so the compound is still findable by people looking for the drug, while the
scanner sees something else.** Same page, same buyer intent, same demand signals — new
string.

Only the first survives. The second is what the doc means by *obscuring*, and it fails later
rather than never: underwriting review, a chargeback pattern, or the §5 ongoing monitoring
that re-scans the site after approval. An account terminated at month four is materially
worse than an application that takes an extra week now.

The practical test: **if the new name is correct, a researcher can confirm the identity from
your COA, and nothing else on the site is doing the work of the old name.** That last clause
is the hard part, and it is the next section.

---

## The rename alone will not pass — and this is the real decision

Renaming 11 products changes 11 titles. The scanner reads all 234 pages.

| Term | Pages carrying it (boilerplate excluded) | URL slugs |
|---|---|---|
| Retatrutide | 104 | 6 |
| Semaglutide | 92 | — |
| Tirzepatide | 77 | — |
| Cagrilintide | 70 | — |
| GLP-1 / GLP-1R | 69 | 7 |

If the products become `OP-MET-203` while 104 pages still say *Retatrutide*, comparison
guides still rank for it, and internal links still point at
`/what-is-retatrutide/` — the rename is cosmetic. It reads worse than not renaming: the site
demonstrates it knows the compound identity and moved the label off the product page.

So there are really three options, and they differ by how much organic traffic you are
willing to give up:

**A · Rename products, retire the editorial corpus.** Cleanest compliance outcome. Retire or
`noindex` the ~100 comparison and "what is X" pages and 301 them to receptor-pathway hubs.
**Cost: this is most of your organic traffic.** Those pages are what rank.

**B · Rename products, rewrite the editorial corpus to pathway framing.** Keep the pages,
rewrite them around receptor pharmacology — GLP-1R/GIPR/GCGR agonism, amylin receptor
signalling — with compound identity carried by catalog code and CAS. Preserves topical
authority and much of the ranking; loses the head terms. **Cost: ~100 pages of rewriting**,
the largest job in this remediation by far.

**C · Rename products, keep the corpus, accept the risk.** Cheapest now. Most likely to fail
the scan, and the failure mode is "you renamed the products but not the site", which is
harder to argue than never having renamed.

**Recommendation: B, staged.** Start with the ~20 pages that both rank and sit closest to
purchase (the `*-vs-*` comparisons already flagged as P1 — they need editing anyway for the
weight-loss figures). That single pass buys the most compliance improvement per page and
overlaps work already scheduled. Then reassess with VERIFIED before touching the long tail.

Worth asking VERIFIED directly (it is question 2 in the draft email): **whether educational
mentions are treated the same as product names at all.** If they are not, option B shrinks
dramatically and this whole section gets cheaper. Do not start rewriting 100 pages before
that answer arrives.

---

## Candidate naming schemes

### Recommended — catalog code + receptor-class descriptor

Keeps your existing SKU logic as the customer-facing handle and describes the compound by
what it does, which is both accurate and what a laboratory buyer actually selects on.

| Current | Proposed | SKU (unchanged) |
|---|---|---|
| Semaglutide 5 mg Research Peptide | **OP-MET-201 · GLP-1 Receptor Agonist Peptide, 5 mg** | `OP-MET-SEMA-5MG` |
| Tirzepatide 20 mg Research Peptide | **OP-MET-202 · Dual Receptor Agonist Peptide, 20 mg** | `OP-MET-TIRZ-20MG` |
| Retatrutide 10 mg Research Peptide | **OP-MET-203 · Triple Receptor Agonist Peptide, 10 mg** | `OP-MET-RETA-10MG` |
| Cagrilintide 5 mg Research Kit | **OP-MET-204 · Amylin Receptor Agonist Peptide, 5 mg** | `OP-KIT-CAGRI-5MG-6` |

Two open questions for VERIFIED, since the doc restricts *"certain GLP-related terminology"*
without drawing the boundary:

- Is **"GLP-1 Receptor Agonist"** acceptable as receptor nomenclature? If not, the fallback
  for the first row is **"Incretin Receptor Agonist Peptide"**, and if *incretin* is also out,
  the descriptor drops entirely and the catalog code carries the listing.
- Does the restriction reach **SKUs**? `OP-MET-SEMA-5MG` contains `SEMA`. Ask before
  changing them — **SKU churn is genuinely expensive**: it breaks order history, COA
  batch-traceability, inventory records and fulfillment pick lists. If SKUs must change, keep
  a permanent internal crosswalk so historical COAs still resolve.

### Alternative — CAS Registry Number as primary identifier

The most defensible identifier available: unambiguous, verifiable, and impossible to read as
marketing.

> **OP-MET-203 · CAS [number] · Triple Receptor Agonist Peptide, 10 mg**

**Pull each CAS number from your own COA rather than a web source, and have whoever signs off
on your COAs confirm it.** I have deliberately not filled these in — a wrong CAS number on a
listing is a chemical misidentification, which is a worse problem than the one being solved.

### Not recommended

- **Invented brand names** ("MetaboLux", "OP-Slim") — reads as marketing, and anything
  evoking the outcome is the §1 benefit-claim problem in a new place.
- **Near-miss spellings** ("Sema-glutide", "Reta") — textbook obscuring; will not survive.
- **Sequence-only names** ("31-aa GLP-1 analog") — still contains the restricted term and is
  unusable as a product title.

---

## Preserving what the rename costs you

- **301 every changed slug.** Old product URLs are linked from panels, comparison pages and
  the catalog; a rename without redirects is a compliance fix that reads as a site outage.
- **Keep SKUs stable if VERIFIED allows** — see above.
- **Update structured data.** Product schema `name` must match the visible title, or the
  page contradicts itself in a way scanners notice.
- **Do not leave the old name in `alt` text, image filenames, meta descriptions or tags.**
  These are the leftovers that make a rename look like concealment rather than
  reclassification. The AI-generated image filenames flagged in the audit need re-uploading
  anyway — do both in one pass.

---

## Suggested sequence

1. **Send the VERIFIED email** ([VERIFIED-NAMING-REQUEST.md](VERIFIED-NAMING-REQUEST.md)) —
   add the proposed scheme above so they can approve or correct a concrete proposal.
2. **Wait for the answer before renaming anything.** Nothing else is blocked on it.
3. Meanwhile: P0 deletions, P1 weight-loss figures, benefit-framed panel names, the two
   product-data fixes. All independent of naming.
4. Once VERIFIED replies: apply the approved names, redirects, schema and image pass
   together, then re-run `scan.py --refresh`.
