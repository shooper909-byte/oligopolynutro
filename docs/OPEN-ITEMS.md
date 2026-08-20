# Open Items — must be resolved before publishing

Grouped by who needs to act. Nothing here blocks reviewing the drafts; everything here
blocks going live.

---

## 1. Legal — counsel must complete these fields

The Program Terms page carries a visible `DRAFT — PENDING LEGAL REVIEW` banner. These
placeholders were deliberately **not invented**:

| Placeholder | Where | Needs |
|-------------|-------|-------|
| `[LEGAL ENTITY NAME]` | Terms, preamble | The contracting entity's registered name |
| `[TO BE SET AT PUBLICATION]` | Terms, effective date | Go-live date |
| Warranty disclaimers | Terms §10 | Counsel drafting |
| Limitation of liability | Terms §10 | Counsel drafting |
| Indemnification | Terms §10 | Counsel drafting |
| Governing law | Terms §10 | Jurisdiction |
| Venue / dispute resolution | Terms §10 | Including any arbitration clause |
| `[MAILING ADDRESS]` | Terms §11 | Address for formal legal notices |

**Remove both `DRAFT — PENDING LEGAL REVIEW` banners** (one on each document page) once
counsel signs off — they are the first paragraph of each page.

The commercial terms *were* carried through from the approved launch package and should not
be changed without a decision: **10% commission**, **60-day attribution**, **30-day
validation hold**, **$100 minimum payout**, **90-day pilot**, **10–20 partner cohort**.

## 2. Application delivery — confirm the destination address

The form currently sends to **`labs@oligopolypeptides.com`**.

That address was **not invented** — it is the address the live `/contact/` page already
posts to. But it is a general support inbox, and partner applications may belong somewhere
else. **Confirm this is right, or give me the address to use instead.** Changing it is a
one-field edit on the form block.

Also worth confirming:

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

## 4. Navigation — decide on placement

The page is not linked from anywhere yet. Nothing in the brief asked for menu changes, and
the site's header/footer are Elementor templates, so I left them alone. Decide whether the
program should appear in the footer, the main menu, or stay unlinked and be reached only by
direct link during the closed pilot.

The two program documents are linked from the program page in three places (compliance
section, above the form, and below the form) plus from each other, which satisfies the
"link near the application checkbox and in the program-resource area" requirement without
touching the global footer.

## 5. Pre-publish checklist

1. Counsel completes §10 and the entity/address/date placeholders.
2. Remove the two `DRAFT — PENDING LEGAL REVIEW` banners.
3. Confirm or change the application delivery address.
4. Publish **all three pages together** (the document links 404 otherwise).
5. Submit a real test application; confirm the email arrives and the confirmation message
   renders.
6. Confirm `affiliate_application_submitted` fires on the post-submit page load.
7. Add the six GTM triggers/tags.
8. Confirm the page renders correctly inside the live header and footer.
9. Decide on navigation placement.

## 6. Not delivered — dependencies I do not have

- **The two attached files.** `Logo.png` and
  `OligoPoly_Affiliate_Program_Launch_Package.docx` were referenced in the brief but did not
  reach this session's filesystem, so I could not read the approved copy, application
  questions, compliance rules, or launch email verbatim. Two consequences worth reviewing:
  - **Logo:** I used the official logo already in the site's media library
    (`/wp-content/uploads/2026/07/cropped-Logo3-3.png`) — the same asset the live site
    header uses. It matches the logo shown in the brief. No new upload was needed, so
    nothing was invented, but confirm it is the intended asset.
  - **Copy:** page and document wording was written from the detailed specification in the
    brief itself, and every commercial term matches the numbers you specified. But it is
    **not** verbatim from the approved launch package. **Please diff the drafts against
    that document.** If you can share the file, I will reconcile the wording exactly.
- **The reference design** at `oligopoly-research-partners.shelby-ernie-8807.chatgpt.site`
  returns **HTTP 401** to this session, so I could not view the approved layout. The page
  was built to the written specification and to the live site's existing brand tokens
  (black/charcoal foundation, metallic silver, electric violet — no teal or navy anywhere).
  Section order follows the brief exactly. Compare against the reference and tell me what to
  adjust.
- **The launch email** in the package was not implemented — it is outside the scope of the
  page build, and the brief said not to touch existing email automations.
