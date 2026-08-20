# Rollback

Site: https://www.oligopolypeptides.com · Pages: **3500** (program), **3498** (terms), **3499** (compliance rules)

## The short version

The three pages are **published** as of 2026-08-20. No existing content was edited, no plugin
was installed or activated, and no theme or server file was touched. Unpublishing or trashing
the three pages removes every trace of this work.

## Reverting

Pick whichever fits the situation:

**1. Unpublish (fastest, keeps the work).**
Edit the page → change status from Published back to **Draft** → Update. The URL returns a
404 and the page leaves the sitemap. Do this for all three pages if you are pulling the
whole program.

**2. Revert one bad edit (keeps the page live).**
Every save creates a WordPress revision, and this work produced several on 2026-08-20 (the
initial drafts, then the reconciliation against the launch package, then publication). Edit
the page → in the sidebar open **Revisions** → pick the timestamp you want → **Restore This
Revision**. The newest 2026-08-20 revision is the published state described in
`docs/CHANGELOG.md`.

**3. Restore from this repository.**
`wordpress/research-partner-program.page.html` is the full block markup for the program
page (regenerate it with `node build.js`). To restore: edit the page, open the block
editor's **⋮ → Code editor**, select all, paste the file's contents, then switch back to
the visual editor and Update. The terms and compliance pages restore the same way from
`wordpress/research-partner-program-terms.blocks.html` and
`wordpress/research-partner-compliance-rules.blocks.html`.

**4. Full-site restore (only if something unrelated breaks).**
UpdraftPlus and Jetpack Backup are both active on this site. A full restore is heavy-handed
for a change this contained — options 1–3 are almost certainly what you want. Prefer a
full restore only if a broader problem is suspected.

## Removing the pieces individually

If you want to keep the page but drop one capability:

| To remove | Do this |
|-----------|---------|
| Analytics events | Delete the last HTML block on page 3500 (contains `<script id="opp-analytics">`). The FAQ schema lives in the same block — keep the JSON-LD `<script>` if you want to keep the schema. |
| FAQ rich-result schema | Delete the `<script type="application/ld+json">` from that same last block. The visible FAQ is unaffected. |
| Application form | Delete the `jetpack/contact-form` block (index 10). Also delete the opening `<div class="opp opp-form-zone">` at the end of block 9 and the matching `</div>` at the start of block 11, or the page will have an unclosed div. |
| All custom styling | Delete the first HTML block (contains `<style id="opp-styles">`). The page keeps its content and semantics but loses the dark/violet design entirely. |

## What rollback does *not* need to touch

Confirmed unchanged throughout this work, so none of it needs reverting:

- Product prices, descriptions, images, inventory, SKUs, URLs, slugs, categories, tags
- Cart, checkout, payment, shipping, and tax settings
- WooCommerce core settings and customer-account rules
- Existing canonical URLs and existing sitewide schema
- Existing analytics configuration (GTM container `GTM-PFX8385C` was read, never edited)
- Existing email automations (Brevo, Klaviyo, cart-abandonment)
- Plugin activation states — no plugin was installed, activated, deactivated, or updated
- Theme files — no child theme was created and no theme file was edited
