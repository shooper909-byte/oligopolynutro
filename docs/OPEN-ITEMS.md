# Open Items

**The pages are live as of 2026-08-20.** Everything below is follow-up work that still needs
a human. The first two items are the ones I would do first.

---

## 1. Legal — counsel must complete these fields (live now, so do this first)

The Program Terms page is **published with a visible `DRAFT — PENDING LEGAL REVIEW` banner**
and bracketed placeholders. This was published on your explicit approval, and the banner is
honest about the document's status — but the placeholders are publicly visible until counsel
fills them in. The package's own §2.12 says the same: counsel finalizes liability cap,
governing law, venue, and dispute process before publication.

The terms text itself now comes from the approved launch package (§2.1–2.13) rather than my
own drafting, so only these counsel-owned fields were **not invented**:

| Placeholder | Where | Needs |
|-------------|-------|-------|
| `[LEGAL ENTITY NAME]` | Terms, preamble | The contracting entity's registered name |
| `[TO BE SET AT PUBLICATION]` | Terms, effective date | Go-live date |
| `[LIABILITY CAP]` | Terms §12 | Counsel drafting |
| `[GOVERNING LAW]` | Terms §12 | Jurisdiction |
| `[VENUE]` | Terms §12 | Counsel drafting |
| `[DISPUTE-RESOLUTION PROCESS]` | Terms §12 | Including any arbitration clause |
| `[MAILING ADDRESS]` | Terms, Contact | Address for formal legal notices |

Warranty disclaimers, limitation of liability, and indemnity are now **present in full**
from the package — they no longer need drafting, only counsel's sign-off.

**Remove both `DRAFT — PENDING LEGAL REVIEW` banners** (one on each document page) once
counsel signs off — they are the first paragraph of each page.

The commercial terms *were* carried through from the approved launch package and should not
be changed without a decision: **10% commission**, **60-day attribution**, **30-day
validation hold**, **$100 minimum payout**, **90-day pilot**, **10–20 partner cohort**.

## 2. Application delivery — CONFIRMED

The form sends to **`labs@oligopolypeptides.com`** — confirmed by the site owner on
2026-08-20 as the correct destination for partner applications. No change needed.

Still worth a live check:

- **Submission storage.** Jetpack Forms stores every submission in wp-admin under
  **Feedback**, in addition to emailing it. That is the site's existing approved form
  storage, so no new storage system was introduced.
- **Email deliverability.** WP Mail SMTP is installed but **inactive**, and Site Mailer is
  **inactive**. Mail currently goes out through the host's default PHP mail, which is the
  same path the existing contact form uses. If contact-form emails are arriving reliably
  today, applications will too — worth a live test either way.
- **No auto-approval.** Confirmed: the form only sends an email and stores the submission.
  It creates no user account, issues no referral link or partner code, and triggers no
  automation. Approval is entirely manual, as specified.

## 3. Analytics — GTM container work

The page pushes six events into the existing `dataLayer`, but **GTM container
`GTM-PFX8385C` has no triggers for them yet**, so nothing reaches GA4 until someone adds
them. Editing the container was out of scope (the brief said not to change existing
analytics configuration).

Someone with container access needs to create a Custom Event trigger and a GA4 event tag
for each of: `affiliate_page_view`, `affiliate_cta_click`, `affiliate_application_started`,
`affiliate_application_submitted`, `affiliate_terms_click`, `affiliate_compliance_click`.
All six carry `page_area: "research_partner_program"` for filtering.

## 4. Navigation — needs a snippet edit I cannot reach

You asked for the program in the nav bar. **I could not do this one**, because the visible
header is not a WordPress menu — it is hardcoded HTML injected by a snippet that this
connection does not expose. Full explanation and the exact copy-paste edits are in
**[NAVIGATION.md](NAVIGATION.md)**.

Short version: the theme's real header is hidden by `display:none !important`, so editing
Appearance → Menus changes nothing. The link has to go into the snippet containing
`opl-shared-nav-20260815`, findable in Code Snippets or WPCode Lite.

The two program documents are already linked from the program page in three places
(compliance section, above the form, below the form) plus from each other, which satisfies
the "link near the application checkbox and in the program-resource area" requirement.

## 4b. SEO title — one field to set by hand

`rank_math_title` is not writable through this connection (verified twice — the value is
dropped silently), so Rank Math falls back to its title template. Live result is 106
characters, which Google will truncate:

> OligoPoly Research Partner Program | Research Peptides & Laboratory Compounds | OligoPoly Laboratories

The two trailing phrases are the **site tagline** and the **site name**, appended by the
template. Step-by-step fix in **[RANK-MATH-TITLE.md](RANK-MATH-TITLE.md)**.

Cosmetic only — the meta description is correct (177 chars, from the page excerpt) and
nothing is broken. Lower priority than the legal fields and the nav link.

## 5. Post-publish checklist

1. Counsel completes §12 and the entity / address / effective-date placeholders.
2. Remove the two `DRAFT — PENDING LEGAL REVIEW` banners.
3. Submit a real test application; confirm the email arrives at
   `labs@oligopolypeptides.com` and the confirmation message renders.
4. Confirm `affiliate_application_submitted` fires on the post-submit page load.
5. Add the six GTM triggers/tags.
6. Add the navigation link (see NAVIGATION.md).
7. Set the Rank Math title (see RANK-MATH-TITLE.md).

Done already: all three pages published and verified live, links resolve, canonical correct,
FAQ schema renders with no conflict, real theme chrome confirmed.

## 6. Not delivered — dependencies I do not have

- **The launch package** was supplied mid-session and the pages were **reconciled against it
  before publishing** — see the reconciliation notes in CHANGELOG.md. This is no longer open.
- **Logo.png** never reached this session, so the hero uses the official logo already in the
  site's media library (`/wp-content/uploads/2026/07/cropped-Logo3-3.png`) — the same asset
  the live header uses, matching the logo in the brief. Nothing was invented, but confirm it
  is the intended asset.
- **Naming.** The package says "OligoPoly Labs"; your brief's brand requirements say
  "OligoPoly Laboratories", and the brief's own final-CTA wording uses "Laboratories". I
  followed the brief. Say the word if you want the package's shorter form instead.
- **The reference design** at `oligopoly-research-partners.shelby-ernie-8807.chatgpt.site`
  returns **HTTP 401** to this session, so I could not view the approved layout. The page
  was built to the written specification and to the live site's existing brand tokens
  (black/charcoal foundation, metallic silver, electric violet — no teal or navy anywhere).
  Section order follows the brief exactly. Compare against the reference and tell me what to
  adjust.
- **The launch email** (package §6) was not implemented — outside the scope of the page
  build, and the brief said not to touch existing email automations. The copy is ready in the
  package whenever you want it built.
- **The internal review rubric** (package §3) is an internal scoring tool, not public page
  content, so it was deliberately not published.
