# P0 fix — remove the internal build note from 5 live posts

Copy-paste remediation for the **P0** finding in [AUDIT-2026-09-05.md](AUDIT-2026-09-05.md):
five published posts carry an internal note stating the content was *"sanitized for public
research-use-only deployment."*

## Why this is not already applied

The WordPress.com MCP connection for this site can read settings but **cannot write content**.
Jetpack reports:

```json
{ "isActive": true, "isRegistered": true, "hasConnectedOwner": true, "isUserConnected": false }
```

The site token is valid — that is why site-level reads succeed — but **no user account is
authorized**, so every `posts.update` is rejected with `Unknown Token`. The site itself is
healthy and publicly reachable (`/wp-json/` returns 200; the REST API serves these posts fine).

To let the edits be applied through this connection, reconnect the Jetpack **user** account:
**wp-admin → Jetpack → My Jetpack → Connection → Connect your WordPress.com account**. Once
`isUserConnected` is `true`, these five edits take about a minute to apply programmatically.

Until then, apply them by hand as below.

## How to apply by hand

For each post: **wp-admin → Posts → edit → Code editor** (⋮ menu, or `Ctrl+Shift+Alt+M`),
find the block quoted below, delete it, update.

Each post needs **two** changes:

1. **Delete the whole `<section id="introduction">` block** — heading and paragraph together.
   It contains no reader-facing information, so nothing replaces it.
2. **Fix the literal `#` markdown heading** left by the same import. It currently renders as
   body text with a visible `#` character. Convert it to a real `<h2>` (or delete it, since
   the post title already says the same thing).

Optionally also remove the **"Source-derived comparison guide"** entry from the
`<nav class="opl-table-of-contents">` list and rename that section heading to something
reader-facing (e.g. *"Comparison guide"*) — "source-derived" is internal vocabulary.

---

## Post 2077 — `/bpc-157-kpv-research-comparison/`

**1. Delete this block:**

```html
<section id="introduction">
<h2>Introduction</h2>
<p>This Phase 3 blend comparison article uses the Claude package as source material and was sanitized for public research-use-only deployment, with emphasis on product documentation, COA verification, and internal links to relevant products.</p>
</section>
```

**2. Replace this literal markdown heading:**

```html
<p># BPC-157 + KPV Blend Research Peptide: Recovery and Immune Pathway Laboratory Guide
```

with:

```html
<h2>BPC-157 + KPV Blend Research Peptide: Recovery and Immune Pathway Laboratory Guide</h2>
```

---

## Post 2079 — `/epitalon-pinealon-research-comparison/`

**1. Delete this block:**

```html
<section id="introduction">
<h2>Introduction</h2>
<p>This Phase 3 blend comparison article uses the Claude package as source material and was sanitized for public research-use-only deployment, with emphasis on product documentation, COA verification, and internal links to relevant products.</p>
</section>
```

**2. Replace this literal markdown heading:**

```html
<p># Epitalon + Pinealon Blend Research Peptide: Cellular Longevity and Neuropeptide Pathway Laboratory Guide
```

with:

```html
<h2>Epitalon + Pinealon Blend Research Peptide: Cellular Longevity and Neuropeptide Pathway Laboratory Guide</h2>
```

---

## Post 2078 — `/mots-c-ss-31-research-comparison/`

**1. Delete this block:**

```html
<section id="introduction">
<h2>Introduction</h2>
<p>This Phase 3 blend comparison article uses the Claude package as source material and was sanitized for public research-use-only deployment, with emphasis on product documentation, COA verification, and internal links to relevant products.</p>
</section>
```

**2. Replace this literal markdown heading:**

```html
<p># MOTS-c + SS-31 Blend Research Peptide: Mitochondrial Pathway Laboratory Guide
```

with:

```html
<h2>MOTS-c + SS-31 Blend Research Peptide: Mitochondrial Pathway Laboratory Guide</h2>
```

---

## Post 2075 — `/retatrutide-cagrilintide-research-comparison/`

**1. Delete this block:**

```html
<section id="introduction">
<h2>Introduction</h2>
<p>This Phase 3 blend comparison article uses the Claude package as source material and was sanitized for public research-use-only deployment, with emphasis on product documentation, COA verification, and internal links to relevant products.</p>
</section>
```

**2. Replace this literal markdown heading:**

```html
<p># Retatrutide + Cagrilintide Blend Research Peptide: Triple Agonist and Amylin Pathway Laboratory Guide
```

with:

```html
<h2>Retatrutide + Cagrilintide Blend Research Peptide: Triple Agonist and Amylin Pathway Laboratory Guide</h2>
```

---

## Post 2074 — `/semaglutide-cagrilintide-research-comparison/`

**1. Delete this block:**

```html
<section id="introduction">
<h2>Introduction</h2>
<p>This Phase 3 blend comparison article uses the Claude package as source material and was sanitized for public research-use-only deployment, with emphasis on product documentation, COA verification, and internal links to relevant products.</p>
</section>
```

**2. Replace this literal markdown heading:**

```html
<p># Semaglutide + Cagrilintide Blend Research Peptide: GLP-1 and Amylin Pathway Laboratory Guide
```

with:

```html
<h2>Semaglutide + Cagrilintide Blend Research Peptide: GLP-1 and Amylin Pathway Laboratory Guide</h2>
```

---

## Verify

```sh
python3 compliance/scan.py --refresh
```

The `[P0] Internal build note published live` line should read **clean**. The P1 findings
will still be listed — those are the next tier of work, with replacement copy in the audit.

