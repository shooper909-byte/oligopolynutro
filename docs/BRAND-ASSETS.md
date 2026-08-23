# Brand assets on the live site

## Footer logo — live

The footer logo was `/wp-content/uploads/oligopoly/logo.webp`, a 512x512 image
forced into a 42px-tall slot, so it rendered as an unreadable square.

Replaced with the current brand lockup: hexagon mark plus OLIGOPOLY /
LABORATORIES, tagline removed because it renders about 8px tall at footer scale.
The near-black backing was keyed to transparency — the footer is `#030712`, not
pure black, so a baked-in panel would have shown as a lighter rectangle.

| | |
|---|---|
| Source art | `assets/logo/opt-a-stacked.png` |
| Published file | `assets/logo/oligopoly-footer-logo.webp`, 279x192, 31KB |
| Media library | ID 3542, `oligopolyfooterlogo-1.webp` |
| Displayed | 140x96 (75px tall under 640px) |
| Applied by | `wordpress/footer-logo.headerfooter.txt` in WPCode → Header & Footer |

**Why CSS rather than PHP.** `wordpress/footer-logo.php` does the same swap
server-side and is the better option — it puts the real URL in the HTML for
crawlers. It was pasted three times without ever activating: its diagnostic
marker never appeared on any page, while another output-buffer snippet's marker
appeared on four, and every page reported cache MISS. The hook works on this
host; that snippet simply never ran. The CSS route uses `content:url()` on the
existing `<img>`, which swaps the rendered image while leaving the tag and its
alt text in place. The PHP version is kept in the repo if the cause is ever
found.

**Verified live:** renders 140x96 — the new logo's ratio at 96px, where the old
square measured 96x96. Alt text intact. Screenshot:
`screenshots/footer-logo-live.png`.

**Rollback:** remove the `<style id="opl-footer-logo-css">` block from the
Header & Footer box.

## Header brand — unchanged

The header brand is **plain text**, not an image: `.opl-shared-wordmark`
containing "OligoPoly Laboratories". The only `custom-logo` image on the page
renders at 0x0 and is not what visitors see. Turning the header into a logo is a
design change rather than an image swap, and was left alone.

A horizontal lockup was prepared for it if ever wanted:
`assets/logo/header-lockup.png` (473x88, displays 236x44 in the 76px bar).

## COA example figure — live on /research/

Full width directly below "Research Documentation and Traceability", above the
three verification cards. Applied by `wordpress/research-coa-figure.php`.

The certificate shown is an illustration of the documentation format, not a
record for a live lot, so it is captioned and alt-described as an example. It is
deliberately not placed in `.oplhub-coa-slot`, whose own comment reserves that
slot for a verified passing COA with a real product, lot, laboratory, method and
report date. That slot remains hidden and untouched.

That section's grid is two columns whose second track holds the hidden slot, so
the right-hand track had been rendering as dead space — the same failure as the
homepage bundle section. Collapsing it to one column gives the figure the full
shell and closes the gap.

| | |
|---|---|
| Media library | ID 3544, `5113.png`, 1536x1024 |
| Delivered size | 334KB via the CDN, despite a 1.98MB original |
| Alt text | set post-upload; the file arrived with none |

**Verified live:** heading → intro → figure → cards, one figure, CSS injected,
compliance slot unchanged, no PHP errors.
