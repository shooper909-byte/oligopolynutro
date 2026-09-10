# Manual Payment Checkout (ACH / Zelle / Venmo / Cash App)

**Status: NOT READY FOR PRODUCTION — not installed, not tested.**

The approved payment structure is implemented in code and verified as far as static analysis
allows. **It has not been installed or tested anywhere**, because there is currently no reachable
staging environment and no wp-admin or SFTP access from this session. Details in
[Staging is blocked](#staging-is-blocked).

Implementation: [`woocommerce/oligopoly-manual-payments/`](../woocommerce/oligopoly-manual-payments/)

---

## Staging is blocked

The instruction was to install and test on a Pressable staging copy, not production. That cannot
be done from here. Four independent checks:

| Check | Result |
|---|---|
| SSH / SFTP credentials | **None** — `~/.ssh` empty, no `wp-cli`, no Pressable API token |
| wp-admin login | **None** |
| Staging site MCP access | **Unavailable** — Jetpack reports `site_disconnected` for blog 253939063 |
| Staging site reachable | **No** — see below |

### `oligopoly.mystagingwebsite.com` is an obsolete domain, not a staging environment

**Correction to an earlier hypothesis in this document.** It first appeared that the active
*OligoPoly SEO Remediation* plugin was redirecting staging to production via canonical-www
enforcement. **That is not what is happening.** Header analysis shows WordPress does not run on
that hostname at all:

| Signal | `oligopoly.mystagingwebsite.com` | `www.oligopolypeptides.com` |
|---|---|---|
| `/cart/` | 301 | 200 |
| `server-timing` duration | **2 ms** | **2258 ms** |
| WooCommerce session cookie | none | set |
| Response body | 162 bytes, nginx default | full page |
| `/nonexistent-path/` | **301** (~0.2–0.5 s) | **404** (2.6 s) |

Every path redirects, including `/wp-content/uploads/`, `/xmlrpc.php`, and cache-busted query
strings. A 2 ms nginx response with no WordPress cookies and a blanket 301 on paths that would
otherwise 404 means **no WordPress install answers on that hostname**. It is an edge/vhost-level
redirect alias pointing at production.

Both hostnames resolve to the same Pressable IPs (`199.16.172.100`, `199.16.173.200`,
`fusion.mystagingwebsite.com`), so this is the same infrastructure with the staging domain
configured as a redirect.

**Answer to "is this current or obsolete staging": obsolete.** There is no staging environment to
repair, and no SEO-plugin bug to fix on it. A fresh clone from current production is required.

### The canonical-redirect risk is still real — on the *new* clone

Production's SEO Remediation plugin (v2026.08.13.3) genuinely does enforce canonical www URLs, and
it will be cloned along with everything else. On a fresh staging copy it very likely *will* redirect
staging → production. That is the point at which the problem this document originally described
becomes real.

[`woocommerce/staging-guard/`](../woocommerce/staging-guard/) contains a drop-in mu-plugin that
prevents it, without editing the SEO plugin:

- cancels any redirect whose destination host is the production host (plugin-agnostic — it catches
  the SEO plugin's enforcement and anything else attempting the same, without needing its source)
- disables WordPress core's own `redirect_canonical`
- rewrites `home`/`siteurl` onto the staging host so links and login redirects stay put
- shows a "Staging environment" banner in wp-admin

**It is inert on production by construction** — every hook is registered only after confirming the
request host is *not* the canonical host. Verified by simulating both hostnames: on
`www.oligopolypeptides.com` the guard registers nothing; on a staging host a redirect to production
is blocked. Install it in `wp-content/mu-plugins/` on staging only, before browsing the clone.

The site's Jetpack `isStaging` flag reports `false` for production, and the old staging blog's path
is recorded as `/1778113193-dedupe/` — consistent with WordPress.com having deduplicated a
duplicated Jetpack connection at some point.

### What is needed to proceed

Any one of:

- **A working Pressable staging copy** with canonical-URL enforcement disabled on it, plus
  wp-admin credentials, or
- **SFTP/SSH** to that staging copy, or
- **Pressable control-panel access** so a fresh staging copy can be created and a recoverable
  backup confirmed.

Until then, the pre-install checks below cannot be performed and no test orders can be run.

---

## REST `Unknown Token` — diagnosed

Investigated separately as instructed. **The site's REST API is healthy.** Public requests
succeed:

```
GET https://www.oligopolypeptides.com/wp-json/          200  application/json  (~2 MB)
GET https://www.oligopolypeptides.com/?rest_route=/     200  application/json  (~2 MB)
```

The fault is in the **Jetpack user connection**, not REST. `jetpack/v4/connection` reports:

```json
{"isActive":true,"isRegistered":true,"hasConnectedOwner":true,"isUserConnected":false,...}
```

`isUserConnected: false` is the cause. The site has a valid **blog** token, which is why
blog-scoped calls (`plugin.list`, backup status) work; but the WordPress.com account driving these
calls has **no linked user token** on the site. The `/wp/v2/*` proxy authenticates as a specific
WordPress user, so with no user token it fails with `Unknown Token`.

**Fix:** in wp-admin → Jetpack → Connection (or My Jetpack → Connection), use *Connect your user
account* to link the WordPress.com account to the site. `hasConnectedOwner: true` means some owner
is linked, but not the account this session authenticates as. No code change is required, and this
is independent of the checkout work.

---

## Approved payment structure

| Gateway | Action | How |
|---|---|---|
| Stripe | Disable | Gateway toggle, keep plugin + data |
| WooPayments | Disable | Gateway toggle, keep plugin + data |
| PayPal | Disable | Gateway toggle, keep plugin + data |
| Square | Disable | Gateway toggle, keep plugin + data |
| Amazon Pay | Disable | Gateway toggle, keep plugin + data |
| Zelle PRO | **Keep** | No change; add its gateway ID to the filter (below) |
| BACS | **Enable** as "ACH / Bank Transfer" | Native settings |
| Venmo | **Add** | New first-party offline gateway in this plugin |
| Cash App | **Add** | New first-party offline gateway in this plugin |

Disable the five online gateways from **WooCommerce → Settings → Payments** using the gateway's own
enable/disable toggle — not by deactivating the plugin. That preserves each gateway's
configuration and all historical order and payment data, and is reversible in one click.

---

## Venmo and Cash App — yes, and how

The question was whether they can be added *without automatically marking orders paid*. They can,
and the safest route is the one taken here: **implement them as first-party offline gateways in
this plugin** rather than rely on the third-party *Checkout with Cash App* plugin (v6.1.1,
currently inactive), whose source cannot be audited from here. Requirement 5 says never call
`payment_complete()` or `set_paid( true )` — that can only be *guaranteed* for code we control.

Both gateways extend a shared `OligoPoly_Offline_Gateway` base modelled on `WC_Gateway_BACS`:

```php
$order->update_status( 'on-hold', ... );   // the only transition this code performs
wc_reduce_stock_levels( $order_id );        // same reservation behaviour as BACS
WC()->cart->empty_cart();
```

One deliberate difference from BACS: **BACS calls `payment_complete()` when the order total is
zero.** These gateways do not — a manual method must never self-verify, so a zero-total order waits
for staff like any other.

Each gateway stores its own **payment handle** as a WooCommerce setting (Venmo username, Cash App
cashtag). Nothing is hard-coded, and the repository contains no account identifiers — only the
placeholder strings `@example-lab` and `$ExampleLab` in the field UI. A gateway with no handle
configured returns `false` from `is_available()`, so it cannot appear at checkout with nowhere to
send the money.

### Zelle PRO

Zelle PRO stays as-is. Its **gateway ID must be read off the site** before the customer-facing
messaging will treat it as a manual method — go to WooCommerce → Settings → Payments → Manage on
the Zelle row and read `section=` in the URL, then:

```php
add_filter( 'oligopoly_manual_payment_gateways', function ( $ids ) {
    $ids[] = 'the_zelle_gateway_id';
    return $ids;
} );
```

This affects presentation only — the payment-required panel, the email framing, the checkout
notice, and the staff note. The **"Awaiting Payment Verification" label is keyed off the `on-hold`
status itself**, so Zelle orders already display correctly without this filter.

Zelle PRO's own payment-completion behaviour is third-party code and **must be verified on staging**
— confirm a Zelle order lands in `on-hold` and is not marked paid.

---

## Requirements coverage

| # | Requirement | Where it is satisfied | Verified |
|---|---|---|---|
| 1 | Order created successfully | Gateway `process_payment()` returns success + redirect | Static only |
| 2 | Stays `on-hold` until staff verify | Only transition in the code is `update_status( 'on-hold' )` | **Confirmed** — no other transition exists |
| 3 | Shows "Awaiting Payment Verification" | `wc_order_statuses` filter, front end only | Static only |
| 4 | Reduces/reserves inventory | `wc_reduce_stock_levels()`, same as BACS | Static only |
| 5 | Never `payment_complete()` / `set_paid(true)` | — | **Confirmed** — 0 occurrences in executable code |
| 6 | Instructions on checkout, Order Received, My Account, email | Gateway description; `thankyou_page()`; `woocommerce_order_details_after_order_table`; `email_instructions()` | Static only |
| 7 | Real order number in the memo | `$order->get_order_number()` in panel, instructions, and email | Static only |
| 8 | Handles in settings, not in code | `payment_handle` gateway setting | **Confirmed** — no identifiers in the repo |

"Confirmed" means mechanically checked in this session. Everything marked "static only" needs the
staging test run.

**My Account was a genuine gap:** native BACS renders instructions on the Order Received page and
in the email, but *not* on the My Account order view — so a customer returning later had no way to
see where to send payment. The plugin now covers that for every manual gateway, scoped to the
`view-order` endpoint so it cannot duplicate the gateway's own Order Received output.

---

## Pre-install checks (all still outstanding)

None of these could be performed without site access.

1. **Confirm a recoverable Pressable backup.** Jetpack Backup is *not provisioned* for this site
   (`state: unavailable`, `no_site_found`). UpdraftPlus 1.26.2 is active but its history is not
   readable from here. **Use the Pressable control panel's own backup/restore point** and confirm
   it is restorable before installing anything.
2. **Create or verify a staging environment.** Blocked — see above. The existing one redirects to
   production.
3. **Audit Code Snippets and WPCode Lite.** Both are active. Any existing payment, checkout, or
   order-status code may conflict with this plugin. Requires wp-admin; not readable from here.
4. **Audit Cart Abandonment Recovery and exclude on-hold orders.** The plugin is active and
   captures email addresses on the checkout page. The specific risk: if it only treats
   `processing`/`completed` orders as converted, customers who ordered by ACH and are legitimately
   awaiting verification will receive "you left something behind" emails. **This needs to be
   confirmed against the plugin's own settings and source on the site** — no filter is applied here,
   because guessing at a third-party plugin's hook names would be worse than leaving it documented.
   Check its exclusion/settings screen first; if it offers no status exclusion, the fix is a
   targeted filter written against its actual source.
5. **Do not enable the 48-hour cancellation rule.** Confirmed — not implemented, not scheduled. See
   [Phase 9](#48-hour-unpaid-order-expiration--not-implemented).

---

## Installation (staging only)

### 1. Native BACS

WooCommerce → Settings → Payments → **Direct bank transfer** → Manage:

| Field | Value |
|---|---|
| Enable | ✔ |
| Title | `ACH / Bank Transfer` |
| Description | `Pay securely by bank transfer. Submit your order to receive payment instructions. Your order will be reserved and will remain awaiting payment verification until payment has cleared and been confirmed.` |
| Instructions | the block below |

```
Thank you for your order.

Please complete your bank transfer using the account information provided below.

IMPORTANT: Use your OligoPoly order number as the payment reference or memo.

Your order will remain Awaiting Payment Verification until the funds have cleared.

Orders are not packed or shipped before payment verification.

You will receive another email when payment has been verified.

Do not submit a duplicate payment if the original transaction is still pending.
```

Add the bank account details in the **Account details** table on the same screen. WooCommerce
renders them only in order and email contexts, never in crawlable content.

> The Instructions field is static text and cannot interpolate the order number. That is what the
> plugin's panel adds, with the real number.

### 2. Install the plugin

Zip `woocommerce/oligopoly-manual-payments/` and upload via Plugins → Add New → Upload. Activate.

### 3. Configure Venmo and Cash App

WooCommerce → Settings → Payments → **Venmo** / **Cash App** → Manage. Enable each, and enter the
receiving handle. Neither will appear at checkout until its handle is set.

### 4. Disable the five online gateways

Per the table above — gateway toggles, not plugin deactivation.

### 5. Add the Zelle gateway ID

Per the filter snippet above, once its ID is read off the site.

---

## Test matrix (to run on staging)

For **each** of ACH / Zelle / Venmo / Cash App:

| Check | Expected |
|---|---|
| Order created | Success, redirect to Order Received |
| Order status | `on-hold` (internal slug unchanged) |
| Customer-facing status | "Awaiting Payment Verification" |
| Inventory | Reduced/reserved at order placement |
| Not marked paid | No `payment_complete`, order total unpaid, no transition to Processing |
| Checkout notice | Visible above Place Order while that method is selected |
| Order Received | PAYMENT REQUIRED panel + instructions + correct order number |
| My Account → order | Instructions + handle + correct order number |
| Customer email | PAYMENT REQUIRED framing + instructions + order number |
| Staff email | New-order email received |
| Admin note | "Verify cleared payment before moving this order to Processing." |
| Manual transition | on-hold → Processing works; Processing email sends |
| Errors | No PHP notices, no JS console errors |
| Mobile | Readable at ~400px |
| RUO acknowledgment | Still functional |

Also confirm the five disabled gateways no longer render at checkout, and that Cart Abandonment
Recovery does not email customers holding on-hold orders.

Screenshots to capture: checkout with the four manual methods; each Order Received page; My Account
order view; admin order list showing on-hold; admin order detail showing the note.

---

## 48-hour unpaid-order expiration — NOT implemented

Confirmed not implemented and not scheduled, per instruction. **ACH commonly takes 3–5 business
days to settle**, so a 48-hour window measured from order creation would cancel orders that were
paid on time but have not cleared — and cancellation restores stock, so a later-arriving payment
would have no reserved inventory.

For reference, WooCommerce's built-in "Hold stock (minutes)" setting cancels **pending** orders
only and will never touch a BACS or offline-gateway `on-hold` order, so it poses no risk here.

If such a rule is ever wanted, it should measure from the date payment was *reported*, not order
creation, and warn the customer before cancelling. Leave unpaid orders on-hold for manual review.

---

## Not changed

**Nothing on production was modified.** Every call made against the live site in this session was
read-only: `plugin.list`, `backup.rewind_status`, operation listings, and unauthenticated HTTP
fetches of public pages and the Jetpack connection endpoint.

Product URLs, SEO, catalog, pricing, inventory, and the RUO compliance protections are untouched.
No gateway was enabled or disabled. No plugin was activated or deactivated. No order exists.
