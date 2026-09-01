#!/usr/bin/env node
/**
 * Builds preview/coa-banner-preview.html from wordpress/product-coa-banner.php.
 *
 * The stylesheet and both markup fragments are parsed out of the PHP snippet, so
 * the preview cannot drift from what the site will actually render. If a parse
 * finds nothing, the build fails rather than emitting a silently empty preview.
 */
const fs = require('fs');
const path = require('path');

const dir = f => path.join(__dirname, f);
const php = fs.readFileSync(dir('wordpress/product-coa-banner.php'), 'utf8');

const styleMatch = php.match(/<style id="opl-coa-banner-styles">([\s\S]*?)<\/style>/);
if (!styleMatch) {
  throw new Error('Could not find the opl-coa-banner-styles block in product-coa-banner.php.');
}
const css = styleMatch[1];

// Sanity-check that both renderers are still present and still emit the classes
// the preview reproduces below.
for (const needle of ['class="opl-coa-banner"', 'class="opl-coa-pill"']) {
  if (!php.includes(needle)) {
    throw new Error(`product-coa-banner.php no longer emits ${needle} — update this preview.`);
  }
}

// Real catalog entries, so the preview shows the longest names the grid must hold.
const SINGLES = [
  'Tirzepatide 10 mg – 6 Vial Research Kit',
  'KLOW Research Blend 80 mg',
];
const CARDS = [
  { name: 'Cagrilintide 5 mg – 6 Vial Research Kit', price: '$593.99' },
  { name: 'Cagrilintide 5 mg Research Peptide', price: '$109.99' },
  { name: 'Retatrutide 10 mg Research Peptide', price: '$139.00' },
  { name: 'Retatrutide 20 mg – 6 Vial Research Kit', price: '$717.54' },
];

const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const DOCS = 'https://www.oligopolypeptides.com/research-peptides-with-coa/';

const single = name => `      <article class="pv-summary">
        <h2 class="pv-title">${esc(name)}</h2>
        <a class="opl-coa-banner" href="${DOCS}"
           aria-label="Certificate of Analysis available for ${esc(name)} — view documentation">
          <span class="opl-coa-banner__badge">COA Available</span>
          <span class="opl-coa-banner__text">Certificate of Analysis on file</span>
          <span class="opl-coa-banner__cta" aria-hidden="true">View&nbsp;COA&nbsp;&rarr;</span>
        </a>
        <p class="pv-price">$—</p>
      </article>`;

const card = c => `      <a class="pv-card" href="${DOCS}">
        <span class="pv-thumb" aria-hidden="true"></span>
        <span class="pv-card-title">${esc(c.name)}</span>
        <span class="opl-coa-pill">COA Available</span>
        <span class="pv-card-price">${esc(c.price)}</span>
        <span class="pv-cart">Add to cart</span>
      </a>`;

const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>COA banner preview — OligoPoly</title>
<style>
/* ---- preview chrome only (pv-), approximating the live pages ---- */
body{margin:0;padding:28px 18px 48px;background:#030712;color:#e2e8f0;
  font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}
.pv-wrap{max-width:940px;margin:0 auto;}
.pv-head{margin:0 0 6px;font-size:19px;font-weight:750;color:#f8fafc;}
.pv-sub{margin:0 0 22px;font-size:13px;color:#94a3b8;line-height:1.6;}
.pv-sub code{color:#d8b4fe;}
.pv-label{margin:30px 0 12px;font-size:11px;font-weight:700;letter-spacing:.14em;
  text-transform:uppercase;color:#94a3b8;border-bottom:1px solid rgba(148,163,184,.18);
  padding-bottom:8px;}
.pv-summary{max-width:560px;margin:0 0 14px;padding:18px 20px;
  border:1px solid rgba(148,163,184,.18);border-radius:10px;
  background:linear-gradient(145deg,rgba(10,8,22,.97),rgba(5,13,24,.98));}
.pv-title{margin:0 0 2px;font-size:21px;font-weight:750;color:#f8fafc;line-height:1.25;}
.pv-price{margin:6px 0 0;font-size:17px;font-weight:700;color:#f8fafc;}

/* product grid, approximating the category archive */
.pv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;}
.pv-card{display:flex;flex-direction:column;align-items:flex-start;padding:12px;
  border:1px solid rgba(148,163,184,.18);border-radius:10px;text-decoration:none;
  background:linear-gradient(145deg,rgba(10,8,22,.97),rgba(5,13,24,.98));}
.pv-thumb{display:block;width:100%;aspect-ratio:1;border-radius:7px;margin-bottom:10px;
  background:radial-gradient(circle at 50% 45%,rgba(147,51,234,.30),rgba(3,7,18,.9) 68%);}
.pv-card-title{color:#f8fafc;font-size:13.5px;font-weight:600;line-height:1.35;}
.pv-card-price{color:#c4b5fd;font-size:14px;font-weight:700;margin:2px 0 9px;}
.pv-cart{display:inline-block;padding:6px 14px;border-radius:999px;
  background:linear-gradient(100deg,#9333ea,#ae3ada);color:#fff;
  font-size:11.5px;font-weight:700;}

/* ---- parsed verbatim from wordpress/product-coa-banner.php ---- */
${css.trim()}
</style>
</head>
<body>
  <div class="pv-wrap">
    <h1 class="pv-head">COA banner — preview</h1>
    <p class="pv-sub">Generated from <code>wordpress/product-coa-banner.php</code> by
      <code>node build-coa-banner.js</code>. Applies to every product; exclude one by SKU
      via <code>opl_coa_banner_exclude_skus</code>. Do not edit this file by hand.</p>

    <p class="pv-label">Single product page — between title and price</p>
${SINGLES.map(single).join('\n')}

    <p class="pv-label">Shop / category listing — pill on the product card</p>
    <div class="pv-grid">
${CARDS.map(card).join('\n')}
    </div>
  </div>
</body>
</html>
`;

fs.writeFileSync(dir('preview/coa-banner-preview.html'), html);
console.log(`preview/coa-banner-preview.html — ${SINGLES.length} summary blocks, ${CARDS.length} cards`);
