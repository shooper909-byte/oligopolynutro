#!/usr/bin/env node
/**
 * Builds two artifacts from the block-markup sources in wordpress/:
 *   1. wordpress/research-partner-program.page.html — the full page content to paste
 *      into the WordPress page (or send via the REST/MCP `content` field).
 *   2. preview/local-preview.html — a standalone approximation for visual QA.
 *
 * The preview simulates the site header/footer and the server-rendered output of the
 * Jetpack Forms field blocks, which only exist once WordPress renders the page.
 */
const fs = require('fs');
const path = require('path');

const dir = f => path.join(__dirname, f);
const read = f => fs.readFileSync(dir(f), 'utf8');

const style = read('wordpress/research-partner-program.style.html');
const sections = read('wordpress/research-partner-program.sections.html');
const form = read('wordpress/research-partner-program.form.html');
const analytics = read('wordpress/research-partner-program.analytics.html');

/**
 * Build the FAQPage JSON-LD from the visible FAQ markup, so the structured data can
 * never drift from what a reader actually sees on the page.
 */
function buildFaqSchema(sectionsHtml) {
  const re = /<details><summary>([\s\S]*?)<\/summary><div>([\s\S]*?)<\/div><\/details>/g;
  const stripTags = h => h.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  const decode = t => t
    .replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&nbsp;/g, ' ');
  const entities = [];
  let m;
  while ((m = re.exec(sectionsHtml)) !== null) {
    entities.push({
      '@type': 'Question',
      name: decode(stripTags(m[1])),
      acceptedAnswer: { '@type': 'Answer', text: decode(stripTags(m[2])) }
    });
  }
  if (!entities.length) throw new Error('No FAQ items found in sections markup');
  const schema = { '@context': 'https://schema.org', '@type': 'FAQPage', mainEntity: entities };
  return '<!-- wp:html -->\n<script type="application/ld+json">\n'
    + JSON.stringify(schema, null, 0) + '\n</script>\n<!-- /wp:html -->\n';
}

const faqSchema = buildFaqSchema(sections);
const tracking = faqSchema + '\n' + analytics;

// ---- 1. Concatenated page content for WordPress -------------------------------
const pageContent = [style, sections, form, tracking].join('\n\n');
fs.writeFileSync(dir('wordpress/research-partner-program.page.html'), pageContent);

// ---- 2. Local preview ---------------------------------------------------------
const stripBlockComments = s => s.replace(/<!-- \/?wp:[^>]*-->/g, '');

// Render the Jetpack field blocks the way WordPress would, so the preview shows a
// real form rather than an empty container.
function renderJetpackFields(src) {
  const re = /<!-- wp:jetpack\/(field-[a-z-]+|button) (\{[\s\S]*?\}) \/-->/g;
  let out = '';
  let m;
  while ((m = re.exec(src)) !== null) {
    const type = m[1];
    const a = JSON.parse(m[2]);
    const id = a.id || 'f' + Math.random().toString(36).slice(2, 7);
    const star = a.required ? ' <span style="color:#c24fef">*</span>' : '';
    const req = a.required ? ' required' : '';
    const label = a.label || a.text || '';
    const field = inner =>
      `<div class="jetpack-field" style="margin-bottom:1.15rem">` +
      `<label class="jetpack-field__label" for="${id}" style="display:block;margin-bottom:.35rem">${label}${star}</label>` +
      `${inner}</div>`;

    if (type === 'field-text') out += field(`<input type="text" id="${id}"${req}>`);
    else if (type === 'field-email') out += field(`<input type="email" id="${id}"${req}>`);
    else if (type === 'field-telephone') out += field(`<input type="tel" id="${id}"${req}>`);
    else if (type === 'field-url') out += field(`<input type="url" id="${id}"${req}>`);
    else if (type === 'field-textarea') out += field(`<textarea id="${id}" rows="4"${req}></textarea>`);
    else if (type === 'field-select')
      out += field(`<select id="${id}"${req}>${a.options.map(o => `<option>${o}</option>`).join('')}</select>`);
    else if (type === 'field-checkbox-multiple')
      out +=
        `<fieldset style="border:none;padding:0;margin:0 0 1.15rem">` +
        `<legend class="jetpack-field__label" style="margin-bottom:.35rem">${label}</legend>` +
        a.options
          .map(o => `<label style="display:flex;gap:.5rem;align-items:center;font-weight:400;margin-bottom:.3rem"><input type="checkbox">${o}</label>`)
          .join('') +
        `</fieldset>`;
    else if (type === 'field-checkbox')
      out +=
        `<div style="margin-bottom:1.15rem"><label style="display:flex;gap:.6rem;align-items:flex-start;font-weight:400">` +
        `<input type="checkbox" id="${id}"${req} style="margin-top:.3rem">${label}${star}</label></div>`;
    else if (type === 'button')
      out += `<div class="wp-block-jetpack-button" style="margin-top:1.5rem"><button type="submit">${a.text || 'Submit'}</button></div>`;
  }
  return out;
}

const formIntro = stripBlockComments(form.split('<!-- wp:jetpack/contact-form')[0]);
const formFooter = stripBlockComments(form.split('<!-- /wp:jetpack/contact-form -->')[1] || '');
const renderedForm =
  `<div class="wp-block-jetpack-contact-form-container">` +
  `<div class="wp-block-jetpack-contact-form opp-jetpack-form"><form>${renderJetpackFields(form)}</form></div></div>`;

const navItems = ['Shop', 'Research Hubs', 'Quality', 'Resources', 'About', 'Contact'];
const siteHeader =
  `<header style="background:#04060d;border-bottom:1px solid rgba(148,163,184,.2);padding:.8rem 1.5rem;display:flex;align-items:center;gap:1.5rem;font-family:Inter,system-ui,sans-serif;position:sticky;top:0;z-index:50">` +
  `<img src="logo.png" alt="OligoPoly Laboratories" style="height:52px;width:auto">` +
  `<nav style="margin-left:auto;display:flex;gap:1.3rem;font-size:.9rem">` +
  navItems.map(x => `<a href="#" style="color:#cbd5e1;text-decoration:none">${x}</a>`).join('') +
  `</nav></header>`;
const siteFooter =
  `<footer style="background:#04060d;border-top:1px solid rgba(148,163,184,.2);color:#94a3b8;font-family:Inter,system-ui,sans-serif;font-size:.85rem;padding:2.5rem 1.5rem;text-align:center">` +
  `© 2026 OligoPoly Laboratories — Research Use Only. ` +
  `<a href="#" style="color:#cbd5e1">Privacy Policy</a> · <a href="#" style="color:#cbd5e1">Research Partner Program Terms</a> · <a href="#" style="color:#cbd5e1">Partner Compliance Rules</a> ` +
  `<em>(simulated site chrome)</em></footer>`;

// Point the hero emblem at the local copy so the preview works offline.
const useLocalLogo = s =>
  s.replace(/https:\/\/www\.oligopolypeptides\.com\/wp-content\/uploads\/2026\/07\/cropped-Logo3-3\.png/g, 'logo.png');

const previewBody = useLocalLogo(
  stripBlockComments(sections) + formIntro + renderedForm + formFooter + stripBlockComments(tracking)
);

const preview =
  `<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">` +
  `<meta name="viewport" content="width=device-width, initial-scale=1">` +
  `<title>Research Partner Program | OligoPoly Laboratories (LOCAL PREVIEW)</title>` +
  useLocalLogo(stripBlockComments(style)) +
  // The real site header has its own mobile menu; collapse the simulated one instead.
  `<style>@media (max-width:900px){header nav{display:none !important}}</style>` +
  `</head><body style="margin:0;background:#030712">${siteHeader}${previewBody}${siteFooter}</body></html>`;

fs.writeFileSync(dir('preview/local-preview.html'), preview);

// ---- Validate what we just built ----------------------------------------------
new Function(analytics.match(/<script id="opp-analytics">([\s\S]*?)<\/script>/)[1]); // throws on a syntax error
const faq = JSON.parse(tracking.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/)[1]);
const visibleFaqCount = (sections.match(/<details>/g) || []).length;
if (faq.mainEntity.length !== visibleFaqCount) {
  throw new Error(`FAQ schema has ${faq.mainEntity.length} questions but the page shows ${visibleFaqCount}`);
}
for (const q of faq.mainEntity) {
  if (!q.name || !q.acceptedAnswer.text) throw new Error('Empty FAQ entry: ' + JSON.stringify(q));
}

console.log(`page content: ${pageContent.length} bytes -> wordpress/research-partner-program.page.html`);
console.log(`preview:      ${preview.length} bytes -> preview/local-preview.html`);
console.log(`analytics JS syntax OK; FAQ schema generated from ${visibleFaqCount} visible questions`);
