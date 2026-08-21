#!/usr/bin/env node
/**
 * Builds the OligoPoly homepage deliverables from their source files.
 *
 *   wordpress/homepage.style.css      ->  minified, inlined into both outputs
 *   wordpress/homepage.sections.html  ->  section markup
 *
 * Outputs:
 *   wordpress/homepage.page.html      page-381 content, single line, wpautop-safe
 *   preview/homepage-preview.html     standalone preview using preview/assets/*
 *
 * Why single-line output: page 381 runs through wpautop, which turns newlines in
 * the stored content into <p> and <br> tags. That is what broke the live stack
 * cards, the access cards and the bundle column (stray </p> and an extra </div>
 * closed the grid early and left an empty container). Emitting the whole content
 * as one line removes the newlines wpautop keys on.
 */
const fs = require('fs');
const path = require('path');

const ROOT = __dirname;
const SITE = 'https://www.oligopolypeptides.com';
const HERO = SITE + '/wp-content/uploads/2026/07/oligopoly-luxury-dna-vial-hero-20260706.webp';
const STYLE_ID = 'op9-home-direct-20260821';

const read = (p) => fs.readFileSync(path.join(ROOT, p), 'utf8');
const write = (p, s) => { fs.writeFileSync(path.join(ROOT, p), s); console.log(`  wrote ${p} (${s.length} bytes)`); };

function minifyCss(css) {
  return css
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/\s*([{}:;,>])\s*/g, '$1')
    .replace(/;}/g, '}')
    .replace(/\s+/g, ' ')
    .trim();
}

function stripComments(html) {
  return html.replace(/<!--[\s\S]*?-->/g, '');
}

/** Collapse to a single line without gluing words together. */
function oneLine(html) {
  return html
    .replace(/>\s*\n\s*</g, '><')
    .replace(/\s*\n\s*/g, ' ')
    .replace(/\s{2,}/g, ' ')
    .trim();
}

// ---------------------------------------------------------------- build
console.log('Building OligoPoly homepage...');

const css = read('wordpress/homepage.style.css').replace('HERO_IMAGE_URL', HERO);
const sections = stripComments(read('wordpress/homepage.sections.html'));

const cssMin = minifyCss(css);
const sectionsMin = oneLine(sections);

// 1. WordPress page content -------------------------------------------------
const page = `<style id="${STYLE_ID}">${cssMin}</style>${sectionsMin}`;
write('wordpress/homepage.page.html', page + '\n');

// 2. Local preview ----------------------------------------------------------
const localAssets = (s) =>
  s.replace(/https:\/\/www\.oligopolypeptides\.com\/wp-content\/uploads\/\d{4}\/\d{2}\//g, 'assets/');

const footer = localAssets(stripComments(read('wordpress/footer.snippet.html')));

const preview = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>OligoPoly Laboratories homepage preview</title>
<style>html,body{margin:0;padding:0;background:#050a14}body{-webkit-font-smoothing:antialiased}</style>
<style id="${STYLE_ID}">${localAssets(cssMin)}</style>
</head>
<body>
${localAssets(sectionsMin)}
${footer}
</body>
</html>
`;
write('preview/homepage-preview.html', preview);

// ---------------------------------------------------------------- validate
console.log('Validating...');
const errors = [];

// a) no empty / placeholder destinations anywhere
const hrefs = [...page.matchAll(/href="([^"]*)"/g)].map((m) => m[1]);
hrefs.forEach((h) => {
  if (!h || h === '#' || h === 'javascript:void(0)' || /^#\s*$/.test(h)) errors.push(`placeholder href: "${h}"`);
});

// b) every image has a src and an alt attribute
[...page.matchAll(/<img\b[^>]*>/g)].forEach((m) => {
  if (!/src="[^"]+"/.test(m[0])) errors.push(`img without src: ${m[0].slice(0, 80)}`);
  if (!/alt="[^"]*"/.test(m[0])) errors.push(`img without alt: ${m[0].slice(0, 80)}`);
});

// c) exactly one h1, and no heading level is skipped
const heads = [...page.matchAll(/<h([1-6])\b/g)].map((m) => Number(m[1]));
if (heads.filter((h) => h === 1).length !== 1) errors.push(`expected exactly one <h1>, found ${heads.filter((h) => h === 1).length}`);
heads.reduce((prev, h) => { if (h > prev + 1) errors.push(`heading jump h${prev} -> h${h}`); return h; }, 1);

// d) tag balance
const stack = [];
const VOID = new Set(['img', 'br', 'hr', 'input', 'meta', 'link', 'source', 'path', 'circle', 'use']);
for (const m of page.matchAll(/<(\/?)([a-zA-Z0-9]+)([^>]*?)(\/?)>/g)) {
  const [, close, tag, attrs, selfClose] = m;
  const t = tag.toLowerCase();
  if (t === 'style') continue;
  if (VOID.has(t) || selfClose) continue;
  if (close) {
    const open = stack.pop();
    if (open !== t) errors.push(`tag mismatch: </${t}> closes <${open}>`);
  } else stack.push(t);
}
if (stack.length) errors.push(`unclosed tags: ${stack.join(', ')}`);

// e) the output really is one line (wpautop safety)
if (page.includes('\n')) errors.push('page content contains a newline; wpautop will inject <p>/<br>');

// f) no banned claim vocabulary
const BANNED = /\b(dose|dosage|dosing|treat|treatment|cure|therapy|therapeutic|patient|weight loss|anti-aging|heal(?:s|ing)?|clinically proven|FDA[- ]approved)\b/i;
const visible = page.replace(/<style[\s\S]*?<\/style>/g, '').replace(/<[^>]+>/g, ' ');
const hit = visible.match(BANNED);
if (hit && !/therapeutic use|not for human/i.test(visible.slice(Math.max(0, visible.indexOf(hit[0]) - 60), visible.indexOf(hit[0]) + 60))) {
  errors.push(`banned claim vocabulary: "${hit[0]}"`);
}

// g) RUO statement present
if (!/For Research Use Only/.test(page)) errors.push('RUO statement missing');

if (errors.length) {
  console.error('\nFAILED:');
  errors.forEach((e) => console.error('  - ' + e));
  process.exit(1);
}
console.log(`  ${hrefs.length} links, ${[...page.matchAll(/<img\b/g)].length} images, ${heads.length} headings — all checks passed.`);
