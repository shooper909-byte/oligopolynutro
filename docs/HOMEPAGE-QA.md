# Homepage QA results

Rendered from `preview/homepage-preview.html` (the built page content plus the corrected
footer, using the site's real approved images) in headless Chromium. Live-site URLs and images
were verified against production with HTTP requests on **2026-08-21**.

The published homepage could not be rendered in this environment — the browser has no route to
the site through the session proxy — so the layout results below come from the built markup,
and the destination results come from live HTTP checks.

## Destinations — 35 URLs, all HTTP 200, no redirects

Homepage: `/research-catalog/` ×3, `/build-your-research-bundle/` ×2,
`/products/tirzepatide-10mg-research-peptide/`, `/products/cagrilintide-5mg-research-peptide/`,
`/products/nad-500mg-research-compound/`, `/products/ghk-cu-50mg-research-peptide/`,
`/products/selank-5mg-research-peptide/`, `/products/semaglutide-5mg-research-peptide/`,
`/products/retatrutide-5mg-research-peptide/`, `/products/retatrutide-20mg-research-peptide/`,
`/products/build-your-research-bundle-3-vials/`, `…-6-vials/`, `…-9-vials/`,
`/research-stacks/`, `/products/metabolic-pathways-stack/`, `/products/cellular-energy-stack/`,
`/products/neurocognitive-pathways-stack/`, `/products/regenerative-biology-stack/`,
`/research-peptides-with-coa/`, `/institutional-purchasing/`.

Footer: the four `/product-category/*-research/` pages, `/research-catalog/`,
`/research-stacks/`, `/build-your-research-bundle/`, `/research-peptides-with-coa/`,
`/quality-standards/`, `/peptide-storage-handling-guide/`, `/peptide-finder/`,
`/research-library/`, `/research-use-only/`, `/about/`, `/contact/`,
`/institutional-purchasing/`, `/shipping/`, `/privacy-policy/`, `/terms-of-use/`,
`/refund-policy/`, plus the `mailto:` and `tel:` links.

No CTA uses an empty, `#`, or placeholder destination — the build fails if one appears.

Removed because they were dead: `/product-category/vitamins/`, `/longevity/`, `/performance/`,
`/cognitive-support/`, `/wellness/` — **HTTP 410 Gone**, all five.

## Images — 14 of 14 load

Eight product images, four stack images, the bundle-builder image and the hero background all
return HTTP 200 from production, and all render with `naturalWidth > 0` in the preview. Zero
blank or broken containers at any viewport. Every `<img>` has an explicit `width`/`height` and
descriptive alt text.

## Layout

| Viewport | Horizontal scroll | Broken images | Empty blocks ≥40px | Console errors |
|---|---|---|---|---|
| Desktop 1440×900 | none | 0 | 0 | 0 |
| Tablet 834×1112 | none | 0 | 0 | 0 |
| Mobile 390×844 | none | 0 | 0 | 0 |
| Mobile 320×700 | none | 0 | 0 | 0 |

`scrollWidth === clientWidth` at every width, and no element inside `#op9-home` extends past
the viewport's right edge.

**Card alignment.** Desktop card heights are identical within each grid and every CTA sits at
the same offset: product cards 616px with the CTA at 560px (×8), collection cards 496px with
the CTA at 438px (×4), access cards 312px (×3), trust cards 208px (×4). At narrower widths the
grid equalises per row, and cards within each row match.

**Breakpoints.** 4 columns → 2 at 1180px → 1 at 560px for the product, collection and trust
grids; the bundle and access grids collapse to one column at 980px; buttons go full-width at
560px.

## Accessibility

- One `<h1>`; sections run `h1 → h2 → h3` with no skipped level (asserted by the build).
- Every `<section>` is labelled with `aria-labelledby` pointing at its own heading, or
  `aria-label` where it has no heading.
- 45 focusable elements, all reachable by keyboard in document order, each with a visible
  3px focus ring at a 4px offset.
- One link per product and collection card — the card is clickable through a stretched CTA
  link, so there are no duplicate or nested links. Link labels are descriptive:
  "View Product: Tirzepatide 10 mg", "Explore Collection: Metabolic Pathways Stack".
- Contrast: 33 unique colour/size combinations checked, **all pass WCAG AA**. Tightest is the
  primary button, `#0a0614` on the darkest end of the violet gradient, at **4.73:1**. Body
  copy was lifted from `#b8c3d5` to `#c3cddd` (11.2:1 on panel) and the footer body from
  `#97a6ba` to `#a7b3c4` (9.5:1).
- `prefers-reduced-motion: reduce` disables every transition and hover transform.
- Decorative SVGs are `aria-hidden`; the `→` glyphs are `aria-hidden` with the real label in
  visually hidden text.

## Content compliance

The build script fails the build on any of: dose/dosing, treat/treatment, cure, therapy,
therapeutic, patient, weight loss, anti-aging, heal, clinically proven, FDA-approved (outside
the approved RUO disclaimer), a missing RUO statement, a placeholder href, an image without
alt, or unbalanced tags. The current build passes all of them.

## Not verified here

- Live rendering of the published page (browser has no route to the site from this session).
- WordPress, CDN and page-builder cache purges — these need wp-admin access; steps are in
  `HOMEPAGE-DEPLOY.md` §4.
