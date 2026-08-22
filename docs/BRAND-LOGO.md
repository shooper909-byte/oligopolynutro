# Brand logo assets

Prepared from the supplied artwork (`Logo.png`, 1536×1024). The original has a
near-black backing baked in; every asset here has that keyed to transparency,
because the footer is `#030712` and the header `#05040c` — a baked-in black
panel would show as a lighter rectangle on both.

## Assets

| File | Size | Use |
|---|---|---|
| `assets/logo/oligopoly-footer-logo.webp` | 279×192, 31KB | **Footer.** Hexagon + wordmark, no tagline. Displays at 140×96 |
| `assets/logo/oligopoly-footer-logo.png` | 279×192 | PNG fallback of the above |
| `assets/logo/header-lockup.png` | 473×88 | Horizontal lockup for the header. Displays at 236×44 |
| `assets/logo/oligopoly-header-logo.webp` | 473×88, 16KB | WebP of the above |
| `assets/logo/oligopoly-hexagon-mark.png` | 130×76 | Hexagon only, for a mark-plus-text header |
| `assets/logo/opt-a-stacked.png` | 1173×807 | Full-res stacked lockup, no tagline |
| `assets/logo/opt-b-wordmark.png` | 1165×210 | Full-res wordmark only |
| `assets/logo/opt-c-full.png` | 1173×902 | Full-res including the tagline |

**The tagline is dropped from every display asset.** At footer or header scale it
renders about 8px tall and cannot be read — see
`screenshots/footer-logo-options.png`.

## Footer — built, awaiting install

`wordpress/footer-logo.php` (paste copy: `footer-logo.wpcode.txt`, 169 lines).

The footer is rendered by another snippet whose markup hard-codes
`/wp-content/uploads/oligopoly/logo.webp` — a 512×512 image forced into a 42px
slot, so it rendered as an illegible square. This rewrites the `<img>` in the
finished page rather than editing that snippet.

The image is resolved from the media library **by name**
(`oligopoly-footer-logo`), with a fallback for the `-1` suffix WordPress adds to
duplicate filenames. No URL is hard-coded. If the attachment is missing the
whole thing is a no-op, so the image and the snippet can be installed in either
order.

Install:
1. Media → Add New → upload `oligopoly-footer-logo.webp`, keeping the filename
2. New WPCode snippet: PHP Snippet, Run Everywhere, paste, Active, Save

Verified both paths — see `wordpress/footer-logo.test.php`.

## Header — not built, awaiting a decision

**There is no header logo to swap.** The visible header brand is plain text:

```html
<a class="opl-shared-brand"><span class="opl-shared-wordmark">OligoPoly Laboratories</span></a>
```

Measured live: 180×28, 16px/400, in a 76px bar. The one `img.custom-logo` on the
page (`cropped-Logo3-3.png`) renders at **0×0** — it sits in a hidden legacy
theme header and is not what visitors see.

So putting the logo in the header means *replacing text with an image* — a
design change, not a like-for-like swap. Options rendered in
`screenshots/header-logo-options.png`:

1. **Full horizontal lockup** — image replaces the text, 44px tall. Most brand
   presence, matches the footer. Costs a 16KB request; the brand name stops
   being selectable text.
2. **Hexagon mark + existing text** — decorative mark (`aria-hidden`) before the
   real text. Lighter, degrades gracefully.
3. **Leave it** — text wordmark as-is.

Paused at the owner's request. Assets for options 1 and 2 are ready.

`screenshots/header-brand-current.png` is the header as it stands today.
