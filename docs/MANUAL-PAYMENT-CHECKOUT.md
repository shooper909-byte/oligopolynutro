# Manual Payment Checkout (ACH / Bank Transfer)

**Status: NOT READY FOR PRODUCTION — not deployed, not tested.**

Phase 1 (audit) was completed against the live site. Phases 2–14 were **not executed**, because
this session has no WooCommerce control surface and no code-deployment path to
`www.oligopolypeptides.com`. See [Blockers](#blockers) for the specifics.

What this directory *does* contain is the implementation itself, written and syntax-checked,
ready to install: [`woocommerce/oligopoly-manual-payments/`](../woocommerce/oligopoly-manual-payments/).

---

## Blockers

The execution brief assumed access this connection does not have.

| Needed for | Required access | Available? |
|---|---|---|
| Phase 2 (enable/configure BACS) | WooCommerce payment-gateway settings | **No** |
| Phases 3–6, 8 (hooks, messaging, labels) | Write PHP to the server | **No** |
| Phase 7 (status progression) | Order read + status transitions | **No** |
| Phase 10 (disable Stripe) | Gateway enable/disable | Partial — plugin-level only |
| Phase 13 (test orders) | Order creation + admin order view | **No** |
| Safety precondition (backup) | Verified restorable backup | **No** |

Three separate limits are in play:

1. **No WooCommerce operations exist in this connection.** The WordPress.com MCP surface covers
   site settings (general/writing/reading/media/discussion/permalink/privacy), plugins, users,
   activity log, themes, backup/scan/monitor, and content (posts, pages, media, comments,
   taxonomies, patterns, templates, navigation, global styles). There is no operation for
   payment gateways, orders, order status, products, stock, or WooCommerce email settings.
   `woocommerce_bacs_settings` is not reachable.

2. **No code-deployment path.** No SSH or SFTP to the Pressable host, no filesystem access, and
   no API for the two snippet managers that are active on the site (Code Snippets 3.9.6 and
   WPCode Lite 2.3.6). Every hook-based phase therefore has no delivery mechanism from here.

3. **The site's REST proxy is currently failing.** Every `/wp/v2/*` call returns
   `Unknown Token` — `pages.get`, `pages.list`, and `theme.active` all failed this way. Only the
   non-REST Jetpack paths (`plugin.list`, backup status) succeeded. **This is worth investigating
   independently of the checkout work**: it means the content-authoring tooling cannot currently
   read or write this site at all.

Phase 13 would also mean placing real orders against a live production store — real inventory,
real customer email, live Beacon compliance monitoring, and six live payment gateways. That
needs a staging environment, not the production site.

---

## Phase 1 — audit findings

Gathered from `plugin.list`, the live cart/checkout HTML, and Jetpack backup status.

### Platform

| | |
|---|---|
| Site | `https://www.oligopolypeptides.com` (blog ID 254585378) |
| Platform | Self-hosted, Jetpack-connected; Pressable (OnePress Login plugin present) |
| WooCommerce | **11.1.0** (current, no update pending) |
| Theme | `hello-elementor` — **no child theme assets load on the front end** |
| Checkout type | **Classic (shortcode)**, not Checkout Blocks |
| Plugins | 85 installed, 42 active, 45 with updates pending |

The classic checkout is the single most useful finding: the standard PHP hooks
(`woocommerce_review_order_before_submit`, `woocommerce_before_thankyou`) work directly. Under
Checkout Blocks this work would instead need a JS block integration against the Store API.

### Payment gateways

**Six gateway plugins are active at once:**

| Gateway | Version | Plugin status |
|---|---|---|
| WooCommerce Stripe Gateway | 10.9.0 | **active** (11.0.0 available) |
| WooPayments | 10.6.0 | **active** (11.1.0 available) |
| WooCommerce PayPal Payments | 4.0.4 | **active** (4.1.3 available) |
| WooCommerce Square | 5.4.1 | **active** (5.5.0 available) |
| Amazon Pay for WooCommerce | 2.6.1 | **active** (2.6.2 available) |
| Checkout with Zelle on WooCommerce **PRO** | 4.1.1 | **active** |
| Checkout with Cash App on WooCommerce | 6.1.1 | inactive |
| Checkout with Zelle (free) | 4.1.1 | inactive |
| SureCart | 4.2.1 | inactive |

Of these, PayPal, WooPayments, and Amazon Pay were confirmed **loading assets on the live
cart page**, so they are enabled gateways, not merely installed plugins.

Three things here need a decision before any of the brief's later phases make sense:

- **The brief's Phase 10 names only Stripe, but WooPayments also processes cards** — disabling
  Stripe alone will not remove card payment from checkout. Square, PayPal, and Amazon Pay are
  live too. The stated objective (customers pay by ACH/bank transfer) is undercut while six
  online gateways remain enabled.
- **Zelle PRO is already active** — an existing manual-payment gateway, presumably already
  running its own offline flow. Phase 11 assumed no manual gateway existed yet. Its current
  configuration and order-status behaviour should be reviewed before BACS is added alongside it,
  so the two do not present customers with two competing bank-transfer options.
- **Cash App is already installed** (inactive). Phase 11 says not to activate it. Noted, not touched.

**Whether native BACS (Direct Bank Transfer) is currently enabled could not be determined** —
that lives in gateway settings, which this connection cannot read. Check
WooCommerce → Settings → Payments.

### Custom-code surface

- **Code Snippets 3.9.6 (active)** and **WPCode Lite 2.3.6 (active)** — two PHP snippet managers
  running simultaneously. Any existing custom checkout or payment code most likely lives in one
  of them, and **neither is readable from here**. The site's hardcoded header snippet
  (`opl-shared-nav-20260815`, see [NAVIGATION.md](NAVIGATION.md)) is already known to live there.
  **Audit both before installing anything**, in case a gateway or order status is already being
  filtered.
- Checkout-affecting plugins currently **inactive**: CartFlows 3.1.1, Checkout Field Editor 2.1.8,
  One Page Checkout 4.1.4, Quick Checkout 1.6.0, Modern Cart Starter 1.0.8.
- **Cart Abandonment Recovery (active)** captures email addresses on the checkout page and sends
  follow-ups. It will treat an unpaid on-hold order as a completed purchase or an abandoned cart
  depending on its configuration — **verify this before go-live**, or customers awaiting payment
  verification may receive "you left something behind" emails.
- **Beacon / compliance-monitor 1.0.17 (active)** — the RUO compliance monitoring. Untouched.
- **Brevo WooCommerce 4.0.58 (active)** and **PDF Invoices & Packing Slips 5.9.2 (active)** both
  hook order status. An invoice generated at on-hold would document an unpaid order — worth checking.

### Plugin hygiene

The plugin directory has accumulated duplicates that should be cleaned up separately:

- **Four** installs of *OligoPoly SEO Remediation* (one active: `oligopoly-seo-remediation-v2-active`, version 2026.08.13.3)
- **Two** of *OligoPoly COA Records* (one active), **two** of *Phase 4B Deployer*, **three** permalink fixers
- Several directories suffixed `-disabled` that are still present on disk
- Both **Rank Math (active)** and **Yoast (inactive)** installed
- **Wordfence inactive**; **UpdraftPlus active**

### Backups

**Jetpack Backup: `state: unavailable`, `state_reason: no_site_found`** — not provisioned for this
site. UpdraftPlus 1.26.2 is active, but its backup history is not readable through this connection.

**The brief's own precondition — "create a backup or confirm a recent restorable backup exists" —
is therefore unverified.** Confirm a restorable UpdraftPlus backup by hand before installing anything.

---

## What native WooCommerce already does

Worth stating plainly, because it removes most of the perceived work in Phases 2, 3, 7, and 12.

`WC_Gateway_BACS::process_payment()` already:

- sets the order to **`on-hold`** with the note "Awaiting BACS payment",
- calls `wc_reduce_stock_levels()` so inventory is reserved,
- empties the cart,
- and **never calls `set_paid( true )`**.

It also renders the gateway's **Instructions** field on the Order Received page
(`thankyou_page()`) and in the customer email (`email_instructions()`), plus the configured bank
account table — in order contexts only, never in publicly crawlable content.

So **Phases 2, 3, 7, and 12 are native behaviour**, configured in
WooCommerce → Settings → Payments → Direct Bank Transfer. No code required. The plugin in this
repo covers only what native does not: the dynamic order-number framing, the pre-submit notice,
the customer-facing status wording, and the internal staff note.

---

## Deployment

### Step 1 — preconditions

1. Confirm a restorable UpdraftPlus backup exists (see above — Jetpack Backup is not available).
2. Audit **Code Snippets** and **WPCode Lite** for existing payment/checkout/order-status code.
3. Decide the Stripe/WooPayments/Square/PayPal/Amazon Pay question (see below).
4. Review how Zelle PRO is currently configured, so it and BACS do not collide.

### Step 2 — native BACS settings

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

Then add the bank account details in the **Account details** table on the same screen. WooCommerce
renders them only in order and email contexts.

> The Instructions field is static text — it cannot interpolate the order number. That is exactly
> what the plugin's Order Received panel adds, with the real number.

### Step 3 — install the plugin

Zip `woocommerce/oligopoly-manual-payments/` and upload via Plugins → Add New → Upload, or drop the
folder in `wp-content/plugins/`. Activate.

It adds, and nothing else:

| Phase | Behaviour | Hook |
|---|---|---|
| 4 | "PAYMENT REQUIRED" panel with live order number on Order Received | `woocommerce_before_thankyou` |
| 12 | Same framing in the customer email (HTML + plain text) | `woocommerce_email_before_order_table` |
| 6 | Pre-submit notice, shown only while a manual method is selected | `woocommerce_review_order_before_submit` |
| 5 | On-hold reads "Awaiting Payment Verification" **on the front end only** | `wc_order_statuses` |
| 8 | Private staff note: "Verify cleared payment before moving this order to Processing." | `woocommerce_checkout_order_processed` |

It never calls `set_paid( true )`, never transitions an order, never registers a custom status,
and contains no account numbers or payment handles.

### Step 4 — Phase 10, Stripe

Prefer the gateway toggle over deactivating the plugin: WooCommerce → Settings → Payments →
Stripe → **disable**. That keeps the plugin, its configuration, and all historical order and
payment data intact, and is reversible in one click.

Do the same for whichever of WooPayments / Square / PayPal / Amazon Pay should come off checkout —
**this needs a decision that the brief did not cover**, since Phase 10 named only Stripe while
five other online gateways are live.

### Step 5 — testing

On staging, not production. Minimum: desktop and mobile-width guest checkout, ACH order created
as on-hold, stock reduced, Order Received panel correct, customer email received, admin note
present, manual on-hold → processing transition, processing email sent, no PHP notices, no JS
errors, RUO acknowledgment still functional.

---

## Phase 9 — 48-hour unpaid-order expiration (documented, NOT enabled)

WooCommerce's built-in "Hold stock (minutes)" setting cancels **pending** orders only. BACS orders
are **on-hold**, so it will never touch them. Automatic expiry needs a scheduled job.

Approach, when approved:

```php
// Schedule on activation, clear on deactivation.
if ( ! wp_next_scheduled( 'oligopoly_expire_unpaid_orders' ) ) {
    wp_schedule_event( time(), 'hourly', 'oligopoly_expire_unpaid_orders' );
}

add_action( 'oligopoly_expire_unpaid_orders', function () {
    $cutoff = gmdate( 'Y-m-d H:i:s', time() - 48 * HOUR_IN_SECONDS );

    $orders = wc_get_orders( array(
        'status'       => 'on-hold',
        'date_created' => '<' . $cutoff,
        'limit'        => 25,          // batch, so a backlog cannot time out the request
        'return'       => 'objects',
    ) );

    foreach ( $orders as $order ) {
        if ( ! in_array( $order->get_payment_method(), array( 'bacs' ), true ) ) {
            continue;                   // never touch online-gateway orders
        }
        $order->update_status( 'cancelled', __( 'Cancelled automatically: no payment verified within 48 hours.', 'oligopoly-manual-payments' ) );
    }
} );
```

Before enabling it, be deliberate about these:

- **ACH takes 3–5 business days to settle.** A 48-hour window will cancel orders that were paid
  on time but have not cleared yet. Either measure from the payment-reported date rather than
  order creation, or make the window longer than ACH settlement.
- Cancelling **restores stock**, so a later-arriving payment has no reserved inventory.
- A weekend or holiday consumes the window with no banking days in it.
- Customers should get a warning email before cancellation, not only after.

Given the ACH settlement math, a longer window — or manual review, as now — is the safer default.
Leave unpaid orders on-hold until reviewed by hand.

---

## Not changed

Nothing on the live site was modified in this session. No settings written, no plugins
activated or deactivated, no content edited, no orders touched. Every call made was read-only:
`plugin.list`, `backup.rewind_status`, operation listings, and one public HTTP fetch of the cart page.

Product URLs, SEO, catalog, pricing, inventory, and the RUO compliance protections are
untouched — by virtue of nothing having been written at all.

---

## To finish this properly

Whoever picks this up needs one of:

- **wp-admin access** for the settings work (Phases 2, 10) plus plugin upload (3–6, 8), or
- **SSH/SFTP** to the Pressable host for deployment, or
- **a staging environment** where test orders are safe (Phase 13).

Also worth resolving independently: the `Unknown Token` REST failure, which currently blocks all
content-authoring tooling against this site.
