# Certificate Library redesign — `/research-peptides-with-coa/`

Page **1652**. URL, slug and canonical unchanged. QR codes already in the field
keep working.

**Status: built and tested. NOT deployed.** The brief asks for a staged version
reviewed before production, and there is an open compliance question (§9) that
is the owner's to answer.

---

## 1. Implementation summary

The page is now a batch finder first and an education page second. A customer
scanning a QR code lands on an H1, a labelled batch field and a Verify button,
all above the fold on a 390×844 phone — the finder sits 222px down, its button
ends at 383px, with 460px of viewport to spare.

Everything renders from a single shortcode, `[opl_coa_library]`, installed as a
WPCode PHP snippet. Page 1652's stored content becomes that one shortcode.

### The finding that shaped the build

**There are no certificates on this site.** The audit is unambiguous:

| Source | Result |
|---|---|
| `op_coa_record` post type ("COA Records") | registered, **0 posts** |
| `opl_lot_record` post type ("Lot Records") | registered, **0 posts** |
| `GET /wp-json/oligopoly/v1/coa?batch=<id>` | `{"found":false,"records":[],"message":"No published COA record matched that batch or lot number."}` |
| `GET /wp-json/opl-lot/v1/lookup?identifier=<id>` | `{"enabled":false,"message":"Lot-specific verification records are being prepared."}` |
| Media library, `media_type=application` | **0 items** — 692 attachments, not one PDF |
| WooCommerce products (25) | no batch, lot or COA attribute on any |

The mockup shows six populated cards — BPC-157 / OP-250718-BPC, TB-500 /
OP-250720-TB5, KPV, Ipamorelin, CJC-1295, AOD-9604, each with a report date and
a green "Documents available" badge. **None of those records exist.** Publishing
them would invent batch numbers, dates and document statuses, which the brief
prohibits outright.

So the build publishes no records of its own. It reads `op_coa_record` and
renders whatever is actually there. With the post type empty:

- the library section says records are being published, and routes to Quality
  Support;
- every search lands on the no-result state, which also routes to Quality
  Support;
- no card, batch ID, date, laboratory or status is displayed anywhere.

The moment real records are published they appear here with no code change.

### Search

- Case-insensitive and separator-insensitive: `test-000005-eee`,
  `TEST-000005-EEE` and `  TEST 000005 EEE  ` all resolve to the same record.
- Enter submits.
- `?batch=OP-250718-BPC` runs the search on load and scrolls to the result, so a
  future QR card can deep-link a batch while keeping this permanent URL.
- **Server-rendered.** The result exists in the HTML before any JavaScript runs,
  so a QR scan works on a phone with a slow or blocked script. JavaScript then
  upgrades the same region in place.
- A near miss is **never** auto-selected. Non-exact matches are presented as a
  candidate list headed "No record matches X exactly… check yours character by
  character before using one."

### Status vocabulary

Only five labels are ever printed: Documents Available, Partial Documentation,
Pending, Archived, Superseded.

A stored status containing *verified*, *passed*, *approved*, *certified* or
*compliant* is **downgraded, not printed** — to "Documents Available" if a
document is actually attached, "Pending" if not. A test result is never inferred
from the presence of a PDF. Green is used for one status only, and every badge
carries an icon and the word, so status is never colour alone.

---

## 2. Files

| File | Role |
|---|---|
| `wordpress/coa-library-page.php` | The build. Registers `[opl_coa_library]` |
| `wordpress/coa-library-page.wpcode.txt` | Paste-ready copy, no `<?php`, pure ASCII, 1424 lines |
| `wordpress/coa-library-page.stub.php` | WordPress stubs + test fixtures for offline rendering |
| `wordpress/coa-library-page.test.js` | 125-assertion Playwright suite |
| `wordpress/backup/page-1652-research-peptides-with-coa.BEFORE.html` | Exact stored content, for rollback |
| `wordpress/backup/page-1652-rendered.BEFORE.html` | Rendered page, for reference |
| `screenshots/coa-before-*.png`, `screenshots/cl-*.png` | Before / after |

Nothing else is touched: no header, nav, footer, checkout, product data, URLs,
redirects, analytics, other pages, or the age gate. The `/coa/` → this page 301
is untouched.

**Test fixtures live only in `coa-library-page.stub.php`.** They are named
`Fixture Compound Alpha`, batch `TEST-000001-AAA` and so on, precisely so they
can never be mistaken for records. They are not published and cannot reach the
site.

---

## 3. Before / after

| | Before | After |
|---|---|---|
| H1 elements | **2** ("COA Center", "Quality Documentation & Certificate Library") | **1** ("Find Your Batch Certificate") |
| Batch search | **none anywhere on the page** | above the fold, 202px down on desktop / 222px on mobile |
| Page height, desktop | 13,659px | 3,165px (**−77%**) |
| Page height, mobile | 24,642px | 4,327px (**−82%**) |
| Words of body copy | 3,150 | 339 (**−89%**) |
| Palette | cyan/teal/blue (`#67e8f9`, `#22c7b8`, `#60a5fa`) | brand violet on `#03040A` |
| Education | ~2,600-word wall of prose | 8 accordions, closed by default |
| Schema | BreadcrumbList + Article + FAQPage | BreadcrumbList only |
| Markup | Classic HTML mangled by `wpautop` — stray `</p>`, `<br />` between buttons | shortcode output, unaffected by `wpautop` |

Structurally, before: hero → trust strip → 6 explainer cards → 7-step workflow →
5 resource cards → 2,600-word essay → 6-item FAQ. After: hero + finder →
results → illustration → library → 3 steps → 8 accordions + support → RUO
notice.

Screenshots: `screenshots/coa-before-desktop.png`, `-mobile.png`;
`screenshots/cl-empty-desktop.png`, `cl-empty-mobile.png` (**what will actually
ship**), `cl-populated-*.png` and `cl-result-*.png` (fixtures, showing the
layout once records exist), `cl-noresult-mobile.png`.

---

## 4. Certificate inventory preserved

**Zero certificates exist, so zero were preserved, and none were removed.**

Searched: both COA post types, both lookup REST endpoints, all 692 media
attachments, all 25 WooCommerce products, and the five linked documentation
pages (`/verify/`, `/coa-corrections/`, `/quality-standards/`,
`/batch-verification/`, `/documentation-downloads/`). No PDF, no certificate
file, no batch record.

The only certificate-like asset on the site is artwork: attachment **3544**
(`5113.png`, "coa-example-tirzepatide"), which is an illustration, not a record.

Existing public entry points still reachable and unchanged: `/verify/` (lot
lookup, feature-flagged off), `/coa-corrections/`, `/quality-standards/`,
`/batch-verification/`.

## 5. Certificates that cannot be matched to a batch

None — because there are none to match. There is no orphaned or ambiguous
document anywhere on the site.

Two adjacent problems worth fixing separately:

1. **`/documentation-downloads/` returns HTTP 502.** The current page's "Download
   Guides" button points at it. The new page does not link to it.
2. The old page linked five product records as "Retatrutide Record",
   "Tirzepatide Record" etc. Those are product pages, not documentation. The new
   page does not present them as records.

---

## 6. Test results

`node wordpress/coa-library-page.test.js` — **125/125 passing.**

**Search:** exact match; lowercase input; leading/trailing whitespace; invalid
batch; duplicate batch ID → both records listed, neither auto-selected; partial
match → candidate list with an explicit warning; `?batch=` deep link;
Enter-to-submit.

**Empty-library behaviour:** no fabricated cards; no invented filters; no mockup
batch IDs anywhere in the output; the no-result copy does not blame the customer
for typing when nothing could possibly match; Quality Support reachable from
both empty states.

**Status vocabulary:** only the five permitted labels render; a stored
`Verified - Passed` is downgraded; green appears only on Documents Available; a
record with no document reads Pending; status is never colour alone.

**Data safety:** a fixture carrying `internal_note` ("supplier margin 42%") and
`supplier_email` renders neither — the meta allowlist holds. A record with no
batch ID is dropped rather than published unmatched.

**Sorting:** active records precede Superseded and Archived.

**Library:** filters derive from real record categories only; filter narrows the
grid and sets `aria-pressed`; batch search filters; Load More pages 6 at a time
and hides itself when exhausted; no-match message.

**Accessibility:** exactly one H1; no heading level skipped; label bound to
input; `aria-live="polite"` on results; 8 accordions keyboard-operable via
Enter; filters keyboard-operable; visible focus ring; all decorative SVG
`aria-hidden`; alt text present; "View Report" links carry their batch ID so
they are distinguishable; **WCAG AA contrast verified across 115 text/background
pairs, lowest 8.42:1 against a 4.5:1 requirement** (translucent layers
composited down to the page ground, not read as opaque).

**Responsive** at 320 / 375 / 390 / 430 / 768 / 1440: no horizontal scroll at
any width; body text ≥16px at every width; search input full width on mobile;
Verify button full width and ≥48px tall; input ≥48px tall; certificate cards one
column; all tap targets ≥44px; H1 and finder above the fold; result metadata
stacks vertically.

**Motion:** `prefers-reduced-motion` leaves **0** animated or transitioning
elements.

**Console:** no JavaScript errors.

### Not tested

- **Core Web Vitals / Lighthouse.** Needs the deployed page on real hosting; an
  offline harness would give misleading figures. Run after deployment.
- **Cross-browser.** Chromium only — Firefox and WebKit are not installed here.
  The CSS and JS use long-established features (grid, flexbox, `:focus-visible`,
  `<details>`, `matchMedia`, `URLSearchParams`).
- **Screen readers.** Semantics and ARIA are verified structurally; no
  NVDA/VoiceOver pass was possible.
- **A live PDF open/download.** No PDF exists to test against. The buttons are
  built and covered by fixtures, but have never opened a real document.
- **The live REST response shape** — see §9.

---

## 7. Performance

No libraries. One inline `<style>`, one inline `<script>` (~130 lines), zero
extra requests.

The illustration is the only image. It is served through
`wp_get_attachment_image()` at the `large` derivative (1024×683) with a
`srcset`, **not** the 1,976,348-byte full-size original — a ~92% reduction for a
phone. It is `loading="lazy"` and `decoding="async"` (it sits below the fold;
nothing above the fold is lazy-loaded), and carries explicit `width`/`height` so
it cannot shift layout.

The library renders server-side and pages 6 at a time in the DOM. No PDF is
fetched on load — only when a customer clicks a specific document.

Uploading a WebP/AVIF derivative of attachment 3544 would improve this further;
the site already runs an image-optimizer plugin, so check whether it is already
serving WebP before adding a duplicate asset.

---

## 8. Rollback

Three independent levers, in increasing order of scope:

1. **Remove the figure only** — set `opl_cl_figure_id()` to `0`. One line.
2. **Disable the page build** — toggle the snippet Inactive in WPCode. The page
   falls back to whatever its content holds.
3. **Restore the old page** — paste
   `wordpress/backup/page-1652-research-peptides-with-coa.BEFORE.html` into page
   1652's Code editor, or restore the 2026-08-06 revision from WordPress
   revision history. That file is the exact pre-change content.

None of them touches anything else on the site. The URL, slug, canonical and the
`/coa/` redirect are never modified, so no rollback step can break a QR code.

---

## 9. Claims and records requiring owner confirmation

**These need answers before this goes live.**

### 9a. The verification graphic contradicts the brief's own constraints

The brief asks for the "MATCH. VERIFY. REVIEW." graphic below the batch result,
and it is placed there. But read the artwork itself (attachment 3544) — baked
into the pixels are:

- a specimen Certificate of Analysis with a batch number (**OP-250718-TIRZ**),
  purity figures, "Meets Specification" results, a report date (**07/21/2025**)
  and **a signature over "Quality Assurance"**;
- the badges **"TESTED 3RD-PARTY"**, **"VERIFIED FOR PURITY"**, **"TRUSTED FOR
  SAFETY"**;
- **"3RD-PARTY TESTED — Independently tested for purity and safety"**;
- **"All products are 3rd-party tested. All results are transparent. Every batch
  can be traced."**;
- **"View real test results anytime, anywhere."**

The same brief prohibits publishing invented batch numbers, dates, signatures,
purity results and certificates; prohibits claiming every product is
independently tested unless the records prove it; and prohibits safety claims.
With zero published records, none of the above is currently substantiated, and
"view real test results anytime" is not currently true.

Mitigations applied: the figure is not a link and is not presented as a result;
the caption disclaims the values **and** the statements inside the artwork; a
one-line switch (`opl_cl_figure_id() → 0`) removes it entirely.

**Decision needed:** ship as-is, ship a version of the artwork with the claim
strap lines removed, or drop the figure. A caption cannot fully neutralise a
claim rendered as an image.

### 9b. The graphic shows Tirzepatide, not BPC-157

The brief specifies BPC-157 alt text. Attachment 3544 depicts a **Tirzepatide
10 mg** vial (its own stored alt text says so, and the label in the artwork reads
Tirzepatide). Labelling it BPC-157 would be a factual error on a page whose whole
purpose is matching labels to documents, so the alt text describes it
generically: *"Example showing how to match a research vial batch ID with its
supporting Certificate of Analysis."* Upload a BPC-157 version and set
`opl_cl_figure_id()` to it if the mockup's version is wanted.

### 9c. The record schema is a best guess

`op_coa_record` is empty, so no live example could be inspected, and the snippet
that registers it is not in this repository. Each field is therefore read
through a list of candidate meta keys — `batch_id` / `batch` / `lot_number` /
`lot`, `report_date` / `issue_date` / `date_reported`, and so on (see
`opl_cl_meta_map()`).

**Confirm the actual meta key names before publishing the first record**, then
trim the map to the real ones. Same for the JSON keys returned by
`/wp-json/oligopoly/v1/coa` — the response shape could not be observed with zero
records.

### 9d. Filter categories

The brief suggests Metabolic Research / Recovery Research / Growth &
Performance. The site's real WooCommerce categories are **Cellular Research,
Cognitive Research, Longevity Research, Metabolic Research, Research Compounds,
Research Products** — "Recovery Research" and "Growth & Performance" do not
exist. Rather than invent filters, the library derives them from the categories
present on actual records. Confirm which taxonomy certificate records should
carry.

### 9e. Compliance wording

The RUO notice uses the brief's exact text. The brief says to confirm final
wording against approved compliance language — that check has not been done here.

### 9f. Trust indicator wording

The brief's third indicator was "Laboratory documentation", explicitly *not*
"Independent laboratory documents" unless every document is genuinely
independent. The build uses "Laboratory documentation" and the accordion on
Laboratory Information states plainly that an issuer may be an outside
laboratory or the supplier, and that these are not equivalent.

---

## 10. Deployment

Not done. When approved:

1. Paste `wordpress/coa-library-page.wpcode.txt` into a **new** WPCode PHP
   snippet, Auto Insert → Run Everywhere, minus the opening `<?php`. Save and
   activate.
2. Replace page 1652's content with the single shortcode `[opl_coa_library]`
   (Code editor view). The backup above is the undo.
3. Verify: one H1, no raw shortcode, `?batch=TEST` reaches the no-result state,
   `/coa/` still redirects here, canonical still
   `https://www.oligopolypeptides.com/research-peptides-with-coa/`.
4. Run Lighthouse on the deployed page.

Note that `?batch=` responses are marked `noindex,follow` with the canonical
pinned to the clean path, so batch lookups cannot spawn indexable duplicates.
Library filters never alter the URL at all.
