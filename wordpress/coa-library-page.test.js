/**
 * Functional / accessibility / responsive suite for the certificate library.
 *
 * Renders wordpress/coa-library-page.php through the offline stubs and drives
 * it in Chromium. Fixture records live in coa-library-page.stub.php and are
 * never published - see docs/COA-LIBRARY.md.
 *
 *   node wordpress/coa-library-page.test.js
 *
 * Requires the fixtures to have been rendered into <scratch>/cl/ first; the
 * runner below does that itself via `php`.
 */

const { chromium } = require('/opt/node22/lib/node_modules/playwright');
const { execFileSync } = require('child_process');
const http = require('http');
const fs = require('fs');
const path = require('path');
const os = require('os');

const REPO = path.resolve(__dirname, '..');
const DIR = fs.mkdtempSync(path.join(os.tmpdir(), 'oplcl-'));
const PORT = 8901;

let pass = 0;
const failures = [];

function ok(name, cond, detail) {
  if (cond) { pass++; return; }
  failures.push(`${name}${detail ? ' -> ' + detail : ''}`);
}

/* ---------- render fixtures ---------- */

const RENDER = path.join(DIR, 'render.php');
fs.writeFileSync(RENDER, `<?php
require ${JSON.stringify(path.join(REPO, 'wordpress/coa-library-page.stub.php'))};
require ${JSON.stringify(path.join(REPO, 'wordpress/coa-library-page.php'))};
$_GET = array();
$b = getenv('BATCH');
if ($b !== false && $b !== '') { $_GET['batch'] = $b; }
echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
  . '<meta name="viewport" content="width=device-width,initial-scale=1">'
  . '<title>' . opl_cl_title('') . '</title>'
  . '<style>body{margin:0;background:#03040A;font-family:system-ui,sans-serif}</style>'
  . '</head><body>' . opl_cl_render() . '</body></html>';
`);

function render(file, { batch = '', fixtures = true } = {}) {
  const out = execFileSync('php', [RENDER], {
    env: { ...process.env, BATCH: batch, OPL_CL_FIXTURES: fixtures ? '1' : '0' },
    maxBuffer: 32 * 1024 * 1024,
  });
  fs.writeFileSync(path.join(DIR, file), out);
}

render('idle.html');
render('found.html', { batch: 'test-000005-eee' });           // lowercase + exact
render('spaced.html', { batch: '  TEST-000005-EEE  ' });      // leading/trailing space
render('multi.html', { batch: 'TEST-000001-AAA' });           // duplicate batch ID
render('near.html', { batch: 'TEST-000005' });                // partial -> candidates
render('none.html', { batch: 'ZZZ-999-NOPE' });               // invalid
render('empty.html', { fixtures: false });                    // production reality
render('emptysearch.html', { batch: 'OP-250718-BPC', fixtures: false });

/* ---------- serve ---------- */

const TYPES = { '.html': 'text/html; charset=utf-8' };
const server = http.createServer((req, res) => {
  const name = path.basename((req.url || '/').split('?')[0]) || 'idle.html';
  const file = path.join(DIR, name);
  if (!fs.existsSync(file)) { res.writeHead(404); res.end('nope'); return; }
  res.writeHead(200, { 'Content-Type': TYPES[path.extname(file)] || 'text/plain' });
  res.end(fs.readFileSync(file));
});

/* ---------- helpers ---------- */

const contrast = (a, b) => {
  const lum = (c) => {
    const [r, g, bl] = c.match(/\d+(\.\d+)?/g).slice(0, 3).map(Number).map((v) => {
      const s = v / 255;
      return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * bl;
  };
  const [x, y] = [lum(a), lum(b)].sort((m, n) => n - m);
  return (x + 0.05) / (y + 0.05);
};

(async () => {
  await new Promise((r) => server.listen(PORT, r));
  const browser = await chromium.launch();
  const base = `http://127.0.0.1:${PORT}/`;
  const local = (r) => (r.request().url().startsWith(base) ? r.continue() : r.abort());

  async function open(file, width = 1440, height = 900) {
    const page = await browser.newPage({ viewport: { width, height } });
    await page.route('**/*', local);
    await page.goto(base + file, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(250);
    return page;
  }

  /* ===== 1. Structure and headings ===== */
  {
    const page = await open('idle.html');

    ok('one H1', (await page.locator('h1').count()) === 1);
    ok('H1 text', (await page.locator('h1').innerText()) === 'Find Your Batch Certificate');
    ok('eyebrow', (await page.locator('.oplcl-eyebrow').innerText()).toUpperCase() === 'QUALITY DOCUMENTATION');
    ok('lede', (await page.locator('.oplcl-lede').innerText())
      === 'Enter the batch ID printed on your vial or verification card.');

    const order = await page.evaluate(() =>
      [...document.querySelectorAll('h1,h2,h3')].map((h) => +h.tagName[1]));
    let jump = null;
    for (let i = 1; i < order.length; i++) {
      if (order[i] > order[i - 1] + 1) jump = `${order[i - 1]}->${order[i]}`;
    }
    ok('no heading level skipped', jump === null, jump);

    ok('label bound to input', await page.evaluate(() => {
      const l = document.querySelector('label[for="oplcl-batch"]');
      return !!l && l.textContent.trim() === 'Batch ID' && !!document.getElementById('oplcl-batch');
    }));
    ok('placeholder', await page.getAttribute('#oplcl-batch', 'placeholder') === 'Example: OP-250718-BPC');
    ok('helper text', (await page.locator('#oplcl-help').innerText())
      === 'Use the complete batch ID, including letters and hyphens.');
    ok('verify button', (await page.locator('.oplcl-verify').innerText()).trim().toUpperCase() === 'VERIFY BATCH');

    const signals = await page.locator('.oplcl-signals li b').allInnerTexts();
    ok('three signals', signals.length === 3, signals.join('|'));
    ok('no independence claim', !signals.join(' ').toLowerCase().includes('independent'), signals.join('|'));

    ok('idle state copy', (await page.locator('#oplcl-state').innerText()).includes(
      'Enter the batch ID from your vial or verification card.'));

    ok('live region', await page.getAttribute('#oplcl-state', 'aria-live') === 'polite');

    ok('three how-it-works steps', (await page.locator('.oplcl-steps li').count()) === 3);
    ok('eight accordions', (await page.locator('.oplcl-item').count()) === 8);
    const acc = await page.locator('.oplcl-item summary').allInnerTexts();
    ok('accordion titles', ['Identity', 'Purity', 'Peptide Content', 'Testing Methods',
      'Chromatography', 'Batch Numbers', 'Document Dates', 'Laboratory Information']
      .every((t) => acc.includes(t)), acc.join('|'));

    ok('support heading', (await page.locator('.oplcl-support h2').innerText()).includes("find your batch"));
    ok('support asks for 4 items', (await page.locator('.oplcl-support-ul li').count()) === 4);
    ok('no health info requested', !(await page.locator('.oplcl-support').innerText())
      .match(/medical|health condition|diagnos/i));

    ok('RUO notice', (await page.locator('.oplcl-ruo-head').innerText())
      === 'For Research Use Only — Not for Human Consumption');

    ok('breadcrumb schema only', await page.evaluate(() => {
      const s = [...document.querySelectorAll('script[type="application/ld+json"]')]
        .map((n) => JSON.parse(n.textContent));
      return s.length === 1 && s[0]['@type'] === 'BreadcrumbList';
    }));
    ok('no product/review schema', !(await page.content()).match(/"@type"\s*:\s*"(Product|Review|AggregateRating|Certification)"/));

    ok('title tag', (await page.title())
      === 'Certificate of Analysis Library | Verify Research Peptide Batches | OligoPoly Laboratories');

    /* body text size */
    const small = await page.evaluate(() => {
      const bad = [];
      document.querySelectorAll('#oplcl p,#oplcl li,#oplcl dd,#oplcl dt,#oplcl .oplcl-input')
        .forEach((el) => {
          if (!el.offsetParent && el.tagName !== 'INPUT') return;
          const s = parseFloat(getComputedStyle(el).fontSize);
          if (s < 16) bad.push(el.className + '=' + s);
        });
      return bad;
    });
    ok('all body text >= 16px (desktop)', small.length === 0, small.slice(0, 5).join(', '));

    /* contrast */
    const pairs = await page.evaluate(() => {
      const out = [];
      /* Composite every translucent layer down onto the page ground. Treating
         an rgba() layer as opaque reports the layer's own colour as the
         background, which reads as 1:1 against text of the same hue. */
      const parse = (c) => (c.match(/[\d.]+/g) || [0, 0, 0, 0]).map(Number);
      const bgOf = (el) => {
        const layers = [];
        let n = el;
        while (n && n !== document.documentElement) {
          const [r, g, b, a = 1] = parse(getComputedStyle(n).backgroundColor);
          if (a > 0) layers.push([r, g, b, a]);
          if (a === 1) break;
          n = n.parentElement;
        }
        let out = [3, 4, 10];
        for (let i = layers.length - 1; i >= 0; i--) {
          const [r, g, b, a] = layers[i];
          out = [r * a + out[0] * (1 - a), g * a + out[1] * (1 - a), b * a + out[2] * (1 - a)];
        }
        return `rgb(${out.map(Math.round).join(', ')})`;
      };
      document.querySelectorAll('#oplcl p,#oplcl h1,#oplcl h2,#oplcl h3,#oplcl b,#oplcl span,#oplcl a,#oplcl li')
        .forEach((el) => {
          if (!el.offsetParent || !el.textContent.trim()) return;
          const cs = getComputedStyle(el);
          out.push({ sel: el.className || el.tagName, fg: cs.color, bg: bgOf(el), size: parseFloat(cs.fontSize) });
        });
      return out;
    });
    let worst = { r: 99, sel: '' };
    pairs.forEach((p) => {
      const r = contrast(p.fg, p.bg);
      if (r < worst.r) worst = { r, sel: p.sel };
    });
    ok('WCAG AA contrast on all text', worst.r >= 4.5,
      `worst ${worst.r.toFixed(2)}:1 on ${worst.sel}`);
    console.log(`   contrast: ${pairs.length} pairs, lowest ${worst.r.toFixed(2)}:1`);

    await page.close();
  }

  /* ===== 2. Search states ===== */
  {
    const page = await open('found.html');
    const txt = await page.locator('#oplcl-state').innerText();
    ok('lowercase batch resolves', txt.includes('TEST-000005-EEE'), txt.slice(0, 90));
    ok('exact match -> one card', (await page.locator('.oplcl-result').count()) === 1);
    ok('shows product', txt.includes('Fixture Compound Epsilon'));
    ok('shows report date', txt.includes('June 30, 2026'), txt);
    ok('shows confirm reminder', txt.includes(
      'Confirm that the batch ID on the document matches the batch ID on your vial.'));
    ok('archived result is not green', (await page.locator('#oplcl-state .oplcl-badge-past').count()) === 1
      && (await page.locator('#oplcl-state .oplcl-badge-ok').count()) === 0);
    await page.close();
  }
  {
    const page = await open('spaced.html');
    ok('leading/trailing spaces trimmed', (await page.locator('.oplcl-result').count()) === 1);
    await page.close();
  }
  {
    const page = await open('multi.html');
    ok('duplicate batch -> both listed', (await page.locator('.oplcl-result').count()) === 2);
    ok('multiple-match notice', (await page.locator('.oplcl-multi').innerText())
      .includes('records share that batch ID'));
    await page.close();
  }
  {
    const page = await open('near.html');
    const note = await page.locator('.oplcl-multi').innerText();
    ok('near match is not auto-selected', note.includes('exactly'), note.slice(0, 80));
    ok('near match lists candidates', (await page.locator('.oplcl-result').count()) >= 1);
    await page.close();
  }
  {
    const page = await open('none.html');
    const t = await page.locator('.oplcl-empty').innerText();
    ok('no-result copy', t.includes("couldn’t locate documentation for that batch ID"), t.slice(0, 90));
    ok('no-result offers support', (await page.locator('.oplcl-empty a').innerText())
      .toUpperCase().includes('CONTACT QUALITY SUPPORT'));
    ok('support visible after failed search', await page.locator('.oplcl-empty a').isVisible());
    await page.close();
  }

  /* ===== 3. Production reality: zero records ===== */
  {
    const page = await open('empty.html');
    ok('empty library states records are being published',
      (await page.locator('.oplcl-lib').innerText()).includes('Records are being published'));
    ok('no fabricated cards', (await page.locator('.oplcl-card').count()) === 0);
    ok('no filters invented', (await page.locator('.oplcl-chip').count()) === 0);
    const body = await page.locator('#oplcl').innerText();
    ok('no mockup batch IDs shipped', !/OP-2507\d\d-[A-Z]/.test(body));
    ok('no invented lab names', !/OligoPoly Laboratories\b.*(tested|assayed)/i.test(body));
    ok('page still routes to support', (await page.locator('a[href="/contact/"]').count()) >= 2);
    await page.close();
  }
  {
    const page = await open('emptysearch.html');
    const t = await page.locator('.oplcl-empty').first().innerText();
    ok('empty-library search does not blame the customer',
      t.includes('not published yet') && !t.includes('Check the characters'), t.slice(0, 110));
    await page.close();
  }

  /* ===== 4. Library filter / search / load more ===== */
  {
    const page = await open('idle.html');
    ok('record with no batch ID dropped', (await page.locator('.oplcl-card').count()) === 8);
    ok('first page shows 6', (await page.locator('.oplcl-card:visible').count()) === 6);
    ok('load more present', await page.locator('#oplcl-more').isVisible());

    await page.click('#oplcl-more');
    await page.waitForTimeout(120);
    ok('load more reveals the rest', (await page.locator('.oplcl-card:visible').count()) === 8);
    ok('load more hides when exhausted', !(await page.locator('#oplcl-more').isVisible()));

    const chips = await page.locator('.oplcl-chip').allInnerTexts();
    ok('filters come from real record categories',
      chips.join('|') === 'All|Metabolic Research|Recovery Research', chips.join('|'));

    await page.click('.oplcl-chip[data-filter="Recovery Research"]');
    await page.waitForTimeout(120);
    ok('filter narrows the grid', (await page.locator('.oplcl-card:visible').count()) === 3);
    ok('filter sets aria-pressed', await page.getAttribute('.oplcl-chip[data-filter="Recovery Research"]', 'aria-pressed') === 'true');
    ok('count announced', (await page.locator('#oplcl-count').innerText()) === '3 records');

    await page.click('.oplcl-chip[data-filter="all"]');
    await page.fill('#oplcl-libsearch', 'test-000003');
    await page.waitForTimeout(150);
    ok('library batch search filters', (await page.locator('.oplcl-card:visible').count()) === 1);

    await page.fill('#oplcl-libsearch', 'nothing-here');
    await page.waitForTimeout(150);
    ok('library no-match message', await page.locator('#oplcl-nomatch').isVisible());
    await page.close();
  }

  /* ===== 5. Status vocabulary ===== */
  {
    const page = await open('idle.html');
    const badges = await page.locator('.oplcl-badge span').allInnerTexts();
    const allowed = ['Documents Available', 'Partial Documentation', 'Pending', 'Archived', 'Superseded'];
    ok('only permitted statuses render', badges.every((b) => allowed.includes(b)), badges.join('|'));
    ok('"Verified - Passed" downgraded', !badges.some((b) => /verif|pass|approv/i.test(b)));
    ok('green used only for Documents Available', await page.evaluate(() => {
      return [...document.querySelectorAll('.oplcl-badge-ok span')]
        .every((s) => s.textContent.trim() === 'Documents Available');
    }));
    ok('status never colour alone', await page.evaluate(() =>
      [...document.querySelectorAll('.oplcl-badge')].every((b) => b.innerText.trim().length > 3)));

    /* a record with no document must never read as available */
    const delta = await page.evaluate(() => {
      const c = [...document.querySelectorAll('.oplcl-card')]
        .find((x) => x.textContent.includes('TEST-000004-DDD'));
      return c ? c.querySelector('.oplcl-badge span').textContent.trim() : null;
    });
    ok('no document -> Pending', delta === 'Pending', String(delta));
    await page.close();
  }

  /* ===== 6. Sorting ===== */
  {
    const page = await open('idle.html');
    await page.click('#oplcl-more');
    await page.waitForTimeout(120);
    const statuses = await page.evaluate(() =>
      [...document.querySelectorAll('.oplcl-card')].map((c) => c.querySelector('.oplcl-badge span').textContent.trim()));
    const rank = { 'Documents Available': 0, 'Partial Documentation': 1, Pending: 2, Superseded: 3, Archived: 4 };
    let sorted = true;
    for (let i = 1; i < statuses.length; i++) {
      if (rank[statuses[i]] < rank[statuses[i - 1]]) sorted = false;
    }
    ok('active records sort before archived/superseded', sorted, statuses.join('|'));
    await page.close();
  }

  /* ===== 7. Keyboard and accessibility ===== */
  {
    const page = await open('idle.html');
    await page.keyboard.press('Tab');
    const reach = await page.evaluate(() => {
      const sel = 'a[href],button:not([disabled]),input:not([type=hidden]),summary,[tabindex]:not([tabindex="-1"])';
      return [...document.querySelectorAll('#oplcl ' + sel)].filter((e) => e.offsetParent).length;
    });
    ok('controls are keyboard reachable', reach > 15, String(reach));

    await page.focus('#oplcl-batch');
    const ring = await page.evaluate(() => {
      const el = document.querySelector('#oplcl-batch');
      el.focus();
      const s = getComputedStyle(el);
      return s.outlineStyle !== 'none' || getComputedStyle(el.parentElement).boxShadow !== 'none';
    });
    ok('visible focus indicator on the input', ring);

    /* Enter submits */
    await page.fill('#oplcl-batch', 'TEST-000005-EEE');
    const navigated = page.waitForNavigation({ timeout: 3000 }).then(() => true).catch(() => false);
    await page.keyboard.press('Enter');
    ok('Enter submits the search', await navigated);

    /* accordions operate from the keyboard */
    const p2 = await open('idle.html');
    await p2.focus('.oplcl-item summary');
    await p2.keyboard.press('Enter');
    await p2.waitForTimeout(120);
    ok('accordion toggles with Enter', await p2.evaluate(() =>
      document.querySelector('.oplcl-item').open));

    /* filters operate from the keyboard */
    await p2.focus('.oplcl-chip[data-filter="Metabolic Research"]');
    await p2.keyboard.press('Enter');
    await p2.waitForTimeout(120);
    ok('filter toggles with Enter', await p2.evaluate(() =>
      document.querySelector('.oplcl-chip[data-filter="Metabolic Research"]').getAttribute('aria-pressed') === 'true'));

    ok('images carry alt text', await p2.evaluate(() =>
      [...document.querySelectorAll('#oplcl img')].every((i) => (i.getAttribute('alt') || '').length > 20)));
    ok('decorative svg hidden from AT', await p2.evaluate(() =>
      [...document.querySelectorAll('#oplcl svg')].every((s) => s.getAttribute('aria-hidden') === 'true')));
    ok('View Report links are distinguishable', await p2.evaluate(() =>
      [...document.querySelectorAll('.oplcl-card-link')].every((a) => /batch/i.test(a.innerText))));

    await page.close();
    await p2.close();
  }

  /* ===== 8. Figure ===== */
  {
    const page = await open('idle.html');
    ok('figure is lazy below the fold', await page.getAttribute('.oplcl-fig-img', 'loading') === 'lazy');
    ok('figure has explicit dimensions', await page.evaluate(() => {
      const i = document.querySelector('.oplcl-fig-img');
      return !!i.getAttribute('width') && !!i.getAttribute('height');
    }));
    ok('figure uses a responsive derivative, not the 1.9MB original',
      !(await page.getAttribute('.oplcl-fig-img', 'src')).match(/5113\.png$/));
    ok('figure has srcset', !!(await page.getAttribute('.oplcl-fig-img', 'srcset')));
    const cap = await page.locator('.oplcl-fig figcaption').innerText();
    ok('figure captioned as illustrative', /illustrative/i.test(cap) && /not a laboratory record/i.test(cap));
    ok('figure is not a link', await page.evaluate(() =>
      !document.querySelector('.oplcl-fig-img').closest('a')));
    ok('alt text does not name a compound the artwork may not show', await page.evaluate(() =>
      !/BPC-157/i.test(document.querySelector('.oplcl-fig-img').getAttribute('alt'))));
    await page.close();
  }

  /* ===== 9. Responsive ===== */
  for (const w of [320, 375, 390, 430, 768, 1440]) {
    const page = await open('idle.html', w, 800);
    const scroll = await page.evaluate(() =>
      document.documentElement.scrollWidth - document.documentElement.clientWidth);
    ok(`no horizontal scroll at ${w}px`, scroll <= 1, `overflow ${scroll}px`);

    const tiny = await page.evaluate(() => {
      const bad = [];
      document.querySelectorAll('#oplcl p,#oplcl li,#oplcl dd').forEach((el) => {
        if (!el.offsetParent) return;
        if (parseFloat(getComputedStyle(el).fontSize) < 16) bad.push(el.className);
      });
      return bad;
    });
    ok(`body text >= 16px at ${w}px`, tiny.length === 0, tiny.slice(0, 3).join(','));

    if (w <= 680) {
      const btn = await page.locator('.oplcl-verify').boundingBox();
      ok(`verify button is full width at ${w}px`, btn.width > w - 80, `${Math.round(btn.width)}px`);
      ok(`verify button >= 48px tall at ${w}px`, btn.height >= 48, `${Math.round(btn.height)}px`);

      /* The control is the input wrapper: it carries the border, the focus
         ring and the search icon. "Full width" is a property of that box; the
         bare <input> is the wrapper minus icon, gap and padding. */
      const wrap = await page.locator('.oplcl-finder .oplcl-input-wrap').boundingBox();
      const inp = await page.locator('#oplcl-batch').boundingBox();
      ok(`search input is full width at ${w}px`, wrap.width > w - 70, `${Math.round(wrap.width)}px of ${w}`);
      ok(`input fills its control at ${w}px`, inp.width > wrap.width - 70,
        `${Math.round(inp.width)} in ${Math.round(wrap.width)}`);
      ok(`input >= 48px tall at ${w}px`, inp.height >= 48, `${Math.round(inp.height)}px`);

      const cols = await page.evaluate(() => {
        const g = document.getElementById('oplcl-grid');
        return g ? getComputedStyle(g).gridTemplateColumns.split(' ').length : 1;
      });
      ok(`certificate cards are one column at ${w}px`, cols === 1, `${cols} cols`);

      const small = await page.evaluate(() => {
        const bad = [];
        document.querySelectorAll('#oplcl a.oplcl-btn,#oplcl button,#oplcl summary').forEach((el) => {
          if (!el.offsetParent) return;
          const r = el.getBoundingClientRect();
          if (r.height < 44) bad.push(el.className + '=' + Math.round(r.height));
        });
        return bad;
      });
      ok(`tap targets >= 44px at ${w}px`, small.length === 0, small.slice(0, 3).join(','));

      /* hero must be reachable without scrolling */
      const h1 = await page.locator('h1').boundingBox();
      const finder = await page.locator('.oplcl-finder').boundingBox();
      ok(`H1 and finder near the top at ${w}px`, h1.y < 160 && finder.y < 400,
        `h1 ${Math.round(h1.y)} finder ${Math.round(finder.y)}`);
    }
    await page.close();
  }

  /* result card stacks on mobile */
  {
    const page = await open('found.html', 375, 800);
    const stacked = await page.evaluate(() => {
      const a = [...document.querySelectorAll('.oplcl-facts > div')];
      if (a.length < 2) return true;
      return a[0].getBoundingClientRect().top !== a[1].getBoundingClientRect().top;
    });
    ok('certificate metadata stacks vertically on mobile', stacked);
    await page.close();
  }

  /* ===== 10. Reduced motion ===== */
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.route('**/*', local);
    await page.goto(base + 'found.html', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(250);
    const moving = await page.evaluate(() => {
      let n = 0;
      document.querySelectorAll('#oplcl *').forEach((el) => {
        const s = getComputedStyle(el);
        if ((s.animationName && s.animationName !== 'none')
          || (s.transitionDuration && parseFloat(s.transitionDuration) > 0)) n++;
      });
      return n;
    });
    ok('prefers-reduced-motion removes all motion', moving === 0, `${moving} animated elements`);
    await page.close();
  }

  /* ===== 11. Console cleanliness ===== */
  {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    const errs = [];
    page.on('console', (m) => { if (m.type() === 'error') errs.push(m.text()); });
    page.on('pageerror', (e) => errs.push(String(e)));
    await page.route('**/*', local);
    await page.goto(base + 'found.html', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(600);
    // The fetch to the live REST endpoint is blocked by the router; that abort
    // is expected offline and is not a script error.
    const real = errs.filter((e) => !/Failed to fetch|ERR_FAILED|net::/i.test(e));
    ok('no JavaScript errors', real.length === 0, real.slice(0, 2).join(' | '));
    await page.close();
  }

  await browser.close();
  server.close();
  fs.rmSync(DIR, { recursive: true, force: true });

  console.log(`\n${pass}/${pass + failures.length} passed`);
  if (failures.length) {
    console.log('\nFAILED:');
    failures.forEach((f) => console.log('  - ' + f));
    process.exit(1);
  }
})();
