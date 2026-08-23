/**
 * Render batch-match-diagram.html to PNG + WebP at 2x.
 *
 *   node assets/coa-figure/render.js
 *
 * Outputs batch-match-diagram.png (2800x1520) and .webp beside the source.
 */

const { chromium } = require('/opt/node22/lib/node_modules/playwright');
const path = require('path');
const fs = require('fs');

const DIR = __dirname;
const SRC = path.join(DIR, 'batch-match-diagram.html');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({
    viewport: { width: 1400, height: 600 },
    deviceScaleFactor: 2,
  });

  await page.goto('file://' + SRC, { waitUntil: 'load' });
  await page.waitForTimeout(400);

  const png = path.join(DIR, 'batch-match-diagram.png');
  await page.screenshot({ path: png, animations: 'disabled' });

  await browser.close();

  const { size } = fs.statSync(png);
  console.log(`png  ${(size / 1024).toFixed(0)} KB  ${png}`);
})();
