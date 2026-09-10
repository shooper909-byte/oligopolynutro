# Production technical SEO review — September 10, 2026

**Audit completed within discovered scope. Production repair remains incomplete. No production URL, setting, content, redirect or file was changed.**

Fresh automated Node crawl: 2026-09-10T15:45:39.748Z through 2026-09-10T15:57:41.478Z. This was a real public HTTP crawl after network permission was approved. Browser inspection separately verified authenticated Pressable/Rank Math and rendered homepage/Selank. Initial shell and browser failures are recorded; they are not counted as successful checks.

## Confirmed environment and recovery

Pressable site 1635271 (oligopoly) is enabled Production; WordPress environment is production. HTTPS www is the primary healthy domain. The shop subdomain is healthy and attached to this same site. Staging and sandbox counts are both zero. Edge/object cache are active; maintenance mode and basic authentication are inactive.

Automatic restore choices: filesystem September 10 00:00 UTC, 3.51 GB; database September 10 14:00 UTC, 11.54 MB. No on-demand backups listed. Availability was verified read-only; no restore or integrity test was performed. Before approved changes, create completed fresh filesystem/database restore points and export exact settings/source for surgical rollback without overwriting intervening orders.

## Fresh crawl results

| Measure | Before changes |
|---|---:|
| Discovered URLs | 2140 |
| HTTP responses | 1002 |
| Intentionally not fetched / other unfetched | 1138 |
| Sitemap URLs | 234 |
| Sitemap URLs returning 200 | 223 |
| Redirects in sitemap | 11 |
| Sitemap canonical-to-404 | 1 |
| HTTP redirects in inventory, including utility/share redirects | 410 |
| Redirect chains | 35 |
| Redirect loops | 0 |
| 404 responses | 34 |
| 410 responses | 7 |
| Broken internal targets, including redirects to 4xx | 22 |
| Soft-404 candidates, requiring review | 13 |
| Technically indexable URLs | 226 |
| Existing QA blocking findings | 151 |

The sitemap index and five child sitemaps returned 200. All 234 listed URLs were checked. Cart, checkout, accounts, internal search, previews, tracking parameters, private endpoints and noncanonical facets remain intentional exclusions. Raw QA includes candidates on excluded routes; these require explicit scope classification rather than blanket changes. No product-tag exclusion change is proposed. Raw indexability does not equal Google indexation. Orphan counts are crawl-relative candidates, not a complete CMS inventory.

## Exact proposed technical changes — not applied

1. **Rank Math:** exclude only these 11 currently redirected page URLs from XML generation; retain their existing redirects and all content:

- https://www.oligopolypeptides.com/peptide-finder/muscle-growth/
- https://www.oligopolypeptides.com/peptide-finder/immune-research/
- https://www.oligopolypeptides.com/peptide-finder/cellular-health/
- https://www.oligopolypeptides.com/research-products/
- https://www.oligopolypeptides.com/research-panels/
- https://www.oligopolypeptides.com/apply-institutional-account/
- https://www.oligopolypeptides.com/apply-individual-research-account/
- https://www.oligopolypeptides.com/individual-research-account/
- https://www.oligopolypeptides.com/request-account/
- https://www.oligopolypeptides.com/sign-in/
- https://www.oligopolypeptides.com/request-quote/

2. **Canonical:** on https://www.oligopolypeptides.com/category/research-stacks/, change the canonical from https://www.oligopolypeptides.com/category/research-collections/ (404) to the existing archive itself. Retain its established path and sitemap entry. This is preferable to inventing/renaming an archive to satisfy an erroneous canonical.

3. **Duplicate route:** direct 301 https://www.oligopolypeptides.com/product-category/research-blends-cat/recovery-research-blends/ to https://www.oligopolypeptides.com/product-category/multi-compound-research-formulations/recovery-research-blends/. Both currently expose the same main text and product set. Keep the preferred route self-canonical; do not rewrite its title or copy.

4. **Existing chains:** the 8 exact from/to mappings in redirect-review.csv marked “Propose direct 301” retain their verified destinations. The other mappings remain on hold where the destination is an empty template or product-to-article equivalence is unverified. Hosting-level HTTP host normalization must be corrected at the layer issuing the first hop; do not add another WordPress hop.

5. **Internal hrefs:** apply only the 14 exact mappings and source-page lists in proposed-href-changes.json. Preserve anchor wording, classes, visual design, images and product names. No global search/replace. Excluded from this list are redirects to the currently broken research-collections renderer and unverified product replacements.

6. **JSON-LD syntax:** on https://www.oligopolypeptides.com/epitalon-pinealon-research-comparison/, escape the embedded quotation marks around 10mg inside the existing JSON string. Preserve the decoded text; do not add claims, scientific values or markup content. Syntax repair alone does not validate the existing scientific claims.

7. **robots.txt and product tags:** no changes in this batch; robots.diff explicitly records this. Keep the current HTTPS www sitemap reference.

The machine-readable complete proposal is proposed-technical-changes.json. Implementation must identify and export the owning WordPress setting/snippet/template before applying these narrowly scoped edits. No production deploy is authorized by this report itself.

## Selank migration

HTTPS shop Selank already returns one 301 to the unchanged HTTPS www product, which returns 200, self-canonicalizes, permits indexing, appears only at www in the product sitemap, and includes Product/BreadcrumbList types. The browser independently shows a legitimate product and one primary H1. **Preserve the existing HTTPS shop redirect; do not replace it with a kit or rename the slug.** HTTP shop and HTTP bare-domain Selank each take two hops; their direct-to-www mappings are in the proposal. HTTP www and HTTPS bare-domain Selank take one hop.

## September 9 GSC reconciliation

The supplied coverage file contains aggregate reason counts, not a complete URL list for those buckets. They cannot establish that all 124 historical 404s or 438 redirects are currently defects. historical-url-reconciliation.csv rechecks 48 individually documented historical examples against the fresh crawl; those examples originate in earlier audit evidence, not a complete September 9 GSC URL export. Current noindex/robots/410 states are kept distinct from stale failures.

All three URLs in the September 9 performance Pages.csv were reconciled in gsc-performance-url-recheck.csv. Selank's historical 22 impressions and one click are preserved as baseline evidence. The page rows sum to 25 impressions (22 + 2 + 1), whereas the supplied headline was 24; do not silently equate those aggregates. No new GSC indexing count or validation status is claimed.

## Remaining blockers requiring investigation, not speculative edits

- /research-collections/ currently returns the literal [opl_research_collections] shortcode without a primary page H1. Restore the existing intended renderer only after identifying its source; no replacement design/copy is proposed. /institutional-research-exchange/ and /orii/ also expose unresolved shortcodes.
- broken-link-review.csv lists 22 confirmed broken targets with every discovered source page. No product-to-homepage/category fallback is approved. Resolve genuine equivalents from source/catalog records; otherwise retain real 404/410 and review source-link treatment under the content freeze.
- remaining-qa-review.json lists orphan, empty-template, metadata, heading and other candidates. Do not rewrite SEO copy or add content to pass these checks. JavaScript-rendered resource links and shared popup headings need DOM/source review before changing anything.
- No complete source ownership/deployment workflow inspection succeeded; later Chrome operations remained intermittent. Pressable production and backup access were verified, but existing redirect rules and snippet owners still require inspection before mutation.

## Tests and deployment

Existing crawler regression suite: 10/10 passed. Fresh crawl finished; SEO QA executed and failed with 151 raw blocking findings. One premature QA read collided with a crawl report write; the final QA result above was rerun after crawl completion. No site build, PHP lint, deployment, cache purge, sitemap submission, Search Console validation or post-change crawl occurred. comparison.json deliberately has postChange=null. All actual changed production URL lists are empty.

Approval is requested only for the exact technical proposal above, subject to source inspection, fresh backup and passing applicable pre-deployment QA. Unresolved blockers prevent a completion claim or a full release. The content freeze remains in force.
