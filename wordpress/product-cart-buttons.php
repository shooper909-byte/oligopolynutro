<?php
/**
 * OligoPoly - purchase controls on product cards
 *
 * Adds a cart control to every product card on the site: the homepage
 * `op9-product-card` grid and the `oprc-card` grid on /research-catalog/.
 *
 * WHAT THE STORE ACTUALLY ALLOWS
 * ------------------------------
 * "Add to cart on all products" is not achievable as stated, and the reason is
 * a deliberate setting rather than missing data. Audited 2026-08-23:
 *
 *   10 individual compounds (Tirzepatide 10 mg, Selank 5 mg, NAD+ 500 mg, ...)
 *      carry Mix and Match's `wc-mnm-not-sold-separately` flag. They have
 *      prices ($74.99 - $123.49) but WooCommerce refuses to sell them alone;
 *      their own product pages render no add-to-cart form at all. Verified by
 *      posting `?add-to-cart=447` - the cart stayed empty.
 *
 *    8 single-compound kits (1 child, min = max = 6) have exactly one valid
 *      configuration, so they can be added in one click. Verified: posting
 *      add-to-cart=3454 with mnm_quantity[39]=6 put "Tirzepatide 10 mg - 6 Vial
 *      Research Kit, $413.94" in the cart.
 *
 *    4 curated stacks and 3 build-your-own bundles have several children and a
 *      fixed container size, so more than one valid selection exists. A card
 *      cannot choose for the customer, so these link to the product page.
 *
 * The rule below is therefore: sell it from the card when exactly one valid
 * configuration exists, send the customer to configure when it does not, and
 * never render a control that would fail.
 *
 * A disabled or decorative "Add to Cart" is not an option. On a research-supply
 * catalogue a button that silently does nothing is worse than no button.
 *
 * NO PRICES OR PRODUCT DATA ARE INVENTED. Every price, name, stock state and
 * child quantity is read from WooCommerce at render time.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Configuration
 * ---------------------------------------------------------------------- */

/** Where "Available in kits and bundles" sends a not-sold-separately compound. */
function opl_pcb_catalog_url() {
	return '/research-catalog/';
}

/**
 * Feature kits rather than compounds in the homepage grid.
 *
 * The grid was built from the 10 individual compounds, none of which can be
 * bought on their own. Every card was therefore a dead end: a name, a price and
 * no way to buy it.
 *
 * With this on, each compound card is retargeted to that compound's dedicated
 * kit - the heading, both links and the price become the kit's, so the card
 * advertises something a customer can actually purchase, at the price they will
 * actually pay. The vial image and the fact bullets are kept: they describe the
 * compound, and the kit is six vials of exactly that compound.
 *
 * Return false to leave the grid on compounds; the cards then fall back to the
 * "Add N-Vial Kit - $price" button, which states the swap in the button instead.
 */
function opl_pcb_feature_kits() {
	return true;
}

/** Labels, in one place. */
function opl_pcb_label( $key ) {
	$labels = array(
		'add'       => 'Add to Cart',
		'configure' => 'Select Options',
		'bundled'   => 'Available in Kits',
		'view'      => 'View Product',
		'out'       => 'Out of Stock',
	);

	return isset( $labels[ $key ] ) ? $labels[ $key ] : '';
}

/* -------------------------------------------------------------------------
 * Product resolution
 * ---------------------------------------------------------------------- */

/**
 * Resolve a product from a permalink found in card markup.
 *
 * Cards carry absolute product URLs, so `url_to_postid()` is exact - no name
 * matching, no guessing which product a card refers to.
 */
function opl_pcb_product_from_url( $url ) {
	static $cache = array();

	$url = trim( (string) $url );

	if ( '' === $url ) {
		return null;
	}

	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}

	$cache[ $url ] = null;

	if ( ! function_exists( 'url_to_postid' ) || ! function_exists( 'wc_get_product' ) ) {
		return $cache[ $url ];
	}

	$id = url_to_postid( $url );

	if ( ! $id ) {
		return $cache[ $url ];
	}

	$product = wc_get_product( $id );

	if ( $product && 'publish' === get_post_status( $id ) ) {
		$cache[ $url ] = $product;
	}

	return $cache[ $url ];
}

/**
 * Is this product flagged "not sold separately" by Mix and Match?
 *
 * Such a product is only ever bought inside a container. It is not an error
 * state and not out of stock - it is how the catalogue is merchandised.
 */
function opl_pcb_is_bundled_only( $product ) {
	if ( ! $product ) {
		return false;
	}

	// MNM exposes this directly on newer versions.
	if ( is_callable( array( $product, 'is_not_sold_individually' ) ) ) {
		return (bool) $product->is_not_sold_individually();
	}

	$flag = get_post_meta( $product->get_id(), '_mnm_not_sold_separately', true );

	if ( '' !== $flag ) {
		return in_array( $flag, array( 'yes', '1', 1, true ), true );
	}

	// Fall back to the observable symptom: it has a price but WooCommerce will
	// not sell it. That combination only arises from this flag on this store.
	return ( ! $product->is_purchasable() && '' !== (string) $product->get_price() );
}

/**
 * The single valid child selection for a container, or null if more than one
 * selection would be valid.
 *
 * Returns array( child_product_id => quantity ).
 *
 * A container is "fixed" when the total of what its children are allowed to
 * contribute equals the required container size - there is then no choice left
 * to make. Anything else is a genuine choice and belongs on the product page.
 */
function opl_pcb_fixed_selection( $product ) {
	if ( ! $product || ! is_callable( array( $product, 'get_child_items' ) ) ) {
		return null;
	}

	$min = is_callable( array( $product, 'get_min_container_size' ) ) ? (int) $product->get_min_container_size() : 0;
	$max = is_callable( array( $product, 'get_max_container_size' ) ) ? (int) $product->get_max_container_size() : 0;

	// An open-ended container is always a choice.
	if ( $min < 1 || $min !== $max ) {
		return null;
	}

	$items = $product->get_child_items();

	if ( empty( $items ) ) {
		return null;
	}

	$selection = array();
	$ceiling   = 0;

	foreach ( $items as $item ) {
		$child = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;

		if ( ! $child || ! $child->is_in_stock() ) {
			return null;
		}

		$child_max = is_callable( array( $item, 'get_quantity' ) ) ? (int) $item->get_quantity( 'max' ) : 0;

		if ( $child_max < 1 ) {
			$child_max = $min;
		}

		$ceiling += $child_max;

		$selection[ (int) $child->get_id() ] = $child_max;
	}

	// More capacity than the container needs means the customer picks. Only an
	// exact match leaves exactly one possible basket.
	if ( $ceiling !== $min ) {
		return null;
	}

	return $selection;
}

/**
 * The dedicated single-compound kit for a not-sold-separately product.
 *
 * Prefers a container built solely from this compound (the "6 Vial Research
 * Kit" pattern); falls back to any container that includes it. Returns null if
 * nothing does, in which case the caller links to the catalogue.
 *
 * The container-to-children map is cached for an hour. Without it this would
 * load every Mix and Match product on every page render, which is exactly the
 * kind of query that makes a catalogue page slow.
 */
function opl_pcb_kit_containing( $product ) {
	if ( ! $product ) {
		return null;
	}

	$map = get_transient( 'opl_pcb_kit_map' );

	if ( false === $map ) {
		$map = array();

		$containers = get_posts(
			array(
				'post_type'        => 'product',
				'post_status'      => 'publish',
				'numberposts'      => 200,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'tax_query'        => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => 'mix-and-match',
					),
				),
			)
		);

		foreach ( (array) $containers as $cid ) {
			$container = wc_get_product( $cid );

			if ( ! $container || ! is_callable( array( $container, 'get_child_items' ) ) ) {
				continue;
			}

			$items = $container->get_child_items();
			$count = count( $items );

			foreach ( $items as $item ) {
				$child = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : null;

				if ( ! $child ) {
					continue;
				}

				$child_id = (int) $child->get_id();

				// A one-child container is the dedicated kit; it wins outright.
				if ( 1 === $count || ! isset( $map[ $child_id ] ) ) {
					$map[ $child_id ] = ( 1 === $count )
						? (int) $cid
						: ( isset( $map[ $child_id ] ) ? $map[ $child_id ] : (int) $cid );
				}
			}
		}

		set_transient( 'opl_pcb_kit_map', $map, HOUR_IN_SECONDS );
	}

	$id = (int) $product->get_id();

	if ( empty( $map[ $id ] ) ) {
		return null;
	}

	$kit = wc_get_product( $map[ $id ] );

	return ( $kit && $kit->is_in_stock() ) ? $kit : null;
}

/* -------------------------------------------------------------------------
 * The control
 * ---------------------------------------------------------------------- */

/**
 * Decide what control a product gets.
 *
 * Returns array( 'kind' => ..., 'html' => ... ). `kind` is one of:
 *   add       - a real add-to-cart form, one click
 *   configure - link to the product page to choose contents
 *   bundled   - not sold separately, link to where it can be bought
 *   out       - in the catalogue but not currently purchasable
 */
function opl_pcb_control( $product ) {
	if ( ! $product ) {
		return null;
	}

	$url = $product->get_permalink();

	if ( opl_pcb_is_bundled_only( $product ) ) {
		$kit = opl_pcb_kit_containing( $product );

		// A compound cannot be bought alone, but its dedicated kit can. When
		// that kit has exactly one valid selection, the card can sell it in one
		// click - PROVIDED the button says so.
		//
		// The button therefore names the kit size and its own price, because
		// the card shows the per-vial price and adding a $413.94 kit from a
		// button that just read "Add to Cart" next to "$74.99" would be a trap.
		// Nobody should be surprised by what lands in the cart.
		if ( $kit ) {
			$fixed = opl_pcb_fixed_selection( $kit );

			if ( null !== $fixed ) {
				return array(
					'kind' => 'add',
					'html' => opl_pcb_form( $kit, $fixed, opl_pcb_kit_label( $kit ) ),
				);
			}
		}

		return array(
			'kind' => 'bundled',
			'html' => '<a class="opl-pcb-btn opl-pcb-bundled" href="'
				. esc_url( $kit ? $kit->get_permalink() : opl_pcb_catalog_url() ) . '">'
				. opl_pcb_icon( 'box' ) . esc_html( opl_pcb_label( 'bundled' ) )
				. '<span class="opl-pcb-sr"> &mdash; ' . esc_html( $product->get_name() )
				. ' is supplied inside a kit or bundle, not on its own'
				. ( $kit ? '. Opens ' . esc_html( $kit->get_name() ) : '' )
				. '</span></a>',
		);
	}

	if ( ! $product->is_in_stock() ) {
		return array(
			'kind' => 'out',
			'html' => '<span class="opl-pcb-btn opl-pcb-out">' . esc_html( opl_pcb_label( 'out' ) ) . '</span>',
		);
	}

	$fixed = opl_pcb_fixed_selection( $product );

	if ( null !== $fixed ) {
		return array(
			'kind' => 'add',
			'html' => opl_pcb_form( $product, $fixed ),
		);
	}

	if ( $product->is_purchasable() && $product->is_type( 'simple' ) ) {
		return array(
			'kind' => 'add',
			'html' => opl_pcb_form( $product, array() ),
		);
	}

	return array(
		'kind' => 'configure',
		'html' => '<a class="opl-pcb-btn opl-pcb-configure" href="' . esc_url( $url ) . '">'
			. opl_pcb_icon( 'cog' ) . esc_html( opl_pcb_label( 'configure' ) )
			. '<span class="opl-pcb-sr"> for ' . esc_html( $product->get_name() ) . '</span></a>',
	);
}

/**
 * Button text for a kit sold from a compound's card.
 *
 * Reads "Add 6-Vial Kit &middot; $413.94" - the size and the price the customer
 * will actually be charged, both taken from the kit itself.
 */
function opl_pcb_kit_label( $kit ) {
	$size  = is_callable( array( $kit, 'get_min_container_size' ) ) ? (int) $kit->get_min_container_size() : 0;
	$label = $size ? esc_html( 'Add ' . $size . '-Vial Kit' ) : esc_html( 'Add Kit to Cart' );

	$price = $kit->get_price();

	if ( '' !== (string) $price && function_exists( 'wc_price' ) ) {
		// wc_price() returns markup; strip it to plain text, then escape. The
		// separator stays literal so it renders as a middot rather than text.
		$label .= ' &middot; ' . esc_html( wp_strip_all_tags( wc_price( $price ) ) );
	}

	return $label;
}

/**
 * A real add-to-cart form.
 *
 * A plain POST to the product permalink, exactly what the product page itself
 * submits. It needs no JavaScript, so it works on a slow phone and cannot be
 * left half-wired; WooCommerce handles validation, stock and redirect.
 *
 * `$label` overrides the button text when the thing being added is not the
 * product whose card this is - see opl_pcb_kit_label().
 */
function opl_pcb_form( $product, $children, $label = '' ) {
	$id = (int) $product->get_id();

	$out = '<form class="opl-pcb-form" method="post" action="' . esc_url( $product->get_permalink() ) . '">';
	$out .= '<input type="hidden" name="add-to-cart" value="' . $id . '">';
	$out .= '<input type="hidden" name="quantity" value="1">';

	foreach ( $children as $child_id => $qty ) {
		$out .= '<input type="hidden" name="mnm_quantity[' . (int) $child_id . ']" value="' . (int) $qty . '">';
	}

	$text = ( '' !== $label ) ? $label : esc_html( opl_pcb_label( 'add' ) );

	$out .= '<button type="submit" class="opl-pcb-btn opl-pcb-add">'
		. opl_pcb_icon( 'cart' ) . $text
		. '<span class="opl-pcb-sr"> &mdash; ' . esc_html( $product->get_name() ) . '</span></button>';
	$out .= '</form>';

	return $out;
}

/** Decorative icons. */
function opl_pcb_icon( $name ) {
	$paths = array(
		'cart' => 'M2 3h3.3l.9 4H21l-2.4 8H8.1L5.6 5H2V3Zm5.6 6 1.4 4h7.7l1.2-4H7.6ZM9 21a1.7 1.7 0 1 1 0-3.4A1.7 1.7 0 0 1 9 21Zm8 0a1.7 1.7 0 1 1 0-3.4A1.7 1.7 0 0 1 17 21Z',
		'cog'  => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm9 4a9 9 0 0 1-.1 1.3l2 1.6-2 3.4-2.4-1a9 9 0 0 1-2.2 1.3L16 21H8l-.3-2.4a9 9 0 0 1-2.2-1.3l-2.4 1-2-3.4 2-1.6a9 9 0 0 1 0-2.6l-2-1.6 2-3.4 2.4 1a9 9 0 0 1 2.2-1.3L8 3h8l.3 2.4a9 9 0 0 1 2.2 1.3l2.4-1 2 3.4-2 1.6c.1.4.1.9.1 1.3Z',
		'box'  => 'M3 5h18v4H3V5Zm1 6h16v8H4v-8Zm5 2v2h6v-2H9Z',
	);

	$d = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['cart'];

	return '<svg class="opl-pcb-i" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . $d . '"/></svg>';
}

/* -------------------------------------------------------------------------
 * Styles
 * ---------------------------------------------------------------------- */

function opl_pcb_css() {
	return '<style id="opl-pcb-css">'
	. '.opl-pcb-form{margin:0;display:block;width:100%}'
	. '.opl-pcb-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;'
	. 'width:100%;min-height:48px;padding:0 16px;border-radius:10px;border:1px solid transparent;'
	. 'font-family:inherit;font-size:15px;font-weight:800;letter-spacing:.04em;'
	. 'text-transform:uppercase;text-decoration:none;cursor:pointer;text-align:center;'
	. 'transition:transform .16s ease,box-shadow .16s ease,background .16s ease}'
	. '.opl-pcb-add{background:linear-gradient(135deg,#9A4DFF,#6E2ABF);color:#fff}'
	. '.opl-pcb-add:hover{transform:translateY(-1px);box-shadow:0 10px 24px rgba(154,77,255,.34)}'
	. '.opl-pcb-configure{background:rgba(154,77,255,.12);color:#E7DBFF;border-color:rgba(154,77,255,.4)}'
	. '.opl-pcb-configure:hover{background:rgba(154,77,255,.2)}'
	. '.opl-pcb-bundled{background:rgba(190,198,214,.07);color:#C3CBDA;border-color:rgba(190,198,214,.22)}'
	. '.opl-pcb-bundled:hover{background:rgba(190,198,214,.13)}'
	. '.opl-pcb-out{background:rgba(190,198,214,.05);color:#94A0B4;border-color:rgba(190,198,214,.16);cursor:not-allowed}'
	. '.opl-pcb-i{width:19px;height:19px;flex:none;fill:currentColor}'
	. '.opl-pcb-unit{font-size:.72em;font-weight:600;opacity:.72;letter-spacing:.02em}'
	// A kit button carries size and price, so it needs room for two lines on a
	// narrow card without the text being clipped.
	. '.opl-pcb-add{line-height:1.25;padding-top:8px;padding-bottom:8px;height:auto}'
	. '.opl-pcb-sr{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;'
	. 'overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}'
	. '.opl-pcb-btn:focus-visible{outline:3px solid #C9A8FF;outline-offset:3px}'

	// Card-specific placement.
	. '.oprc-card-actions .opl-pcb-form,.oprc-card-actions .opl-pcb-btn{flex:1 1 100%;order:-1}'
	. '.op9-product-card .opl-pcb-form,.op9-product-card>.op9-product-body>.opl-pcb-btn{margin-top:12px}'

	. '@media(prefers-reduced-motion:reduce){'
	. '.opl-pcb-btn{transition:none!important}'
	. '.opl-pcb-add:hover{transform:none}}'
	. '</style>';
}

/* -------------------------------------------------------------------------
 * Injection
 * ---------------------------------------------------------------------- */

/**
 * Retarget each homepage compound card to that compound's dedicated kit.
 *
 * Operates on whole `<article class="op9-product-card">` blocks so a card is
 * only ever rewritten as a unit - there is no way for a heading to end up
 * describing one product while a link points at another.
 *
 * A card is retargeted only when ALL of these hold:
 *   - it resolves to a published product
 *   - that product cannot be sold on its own
 *   - it has a dedicated kit with exactly one valid selection
 *   - the kit is in stock and priced
 *
 * Anything else - the curated stack in the same grid, a compound with no kit -
 * is returned untouched.
 */
function opl_pcb_feature_kits_in_grid( $html ) {
	if ( ! opl_pcb_feature_kits() || false === strpos( $html, 'op9-product-card' ) ) {
		return $html;
	}

	return preg_replace_callback(
		'#<article class="op9-product-card">.*?</article>#s',
		'opl_pcb_swap_card',
		$html
	);
}

/** Rewrite one card from its compound to its kit. */
function opl_pcb_swap_card( $m ) {
	$card = $m[0];

	if ( ! preg_match( '#<a class="op9-product-media" href="([^"]+)"#', $card, $href ) ) {
		return $card;
	}

	$compound = opl_pcb_product_from_url( html_entity_decode( $href[1] ) );

	if ( ! $compound || ! opl_pcb_is_bundled_only( $compound ) ) {
		return $card;
	}

	$kit = opl_pcb_kit_containing( $compound );

	if ( ! $kit || null === opl_pcb_fixed_selection( $kit ) ) {
		return $card;
	}

	$old_url = $href[1];
	$new_url = esc_url( $kit->get_permalink() );
	$name    = esc_html( $kit->get_name() );

	// Every link in the card moves together.
	$card = str_replace( $old_url, $new_url, $card );

	// Heading text.
	$card = preg_replace(
		'#(<h3><a href="[^"]*">).*?(</a></h3>)#s',
		'${1}' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $name ) . '${2}',
		$card,
		1
	);

	// Accessible name on the image link.
	$card = preg_replace(
		'#(<a class="op9-product-media"[^>]*aria-label=")[^"]*(")#',
		'${1}View ' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $name ) . '${2}',
		$card,
		1
	);

	// Price becomes the kit's. Falls through silently if the card has no price
	// block, rather than inventing one.
	$price = $kit->get_price();

	if ( '' !== (string) $price && function_exists( 'wc_price' ) ) {
		$card = preg_replace(
			'#(<div class="op9-product-price">).*?(</div>)#s',
			'${1}' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), wc_price( $price ) ) . '${2}',
			$card,
			1
		);
	}

	return $card;
}

/**
 * Mark the homepage card price as per-vial.
 *
 * Those cards show a compound's own price - $74.99 for Tirzepatide - while the
 * button beside it adds a six-vial kit for $413.94. Both numbers are correct,
 * but side by side and unlabelled they read as a contradiction. Saying "per
 * vial" makes the relationship obvious, and it happens to flatter the kit:
 * $413.94 for six is $68.99 each, cheaper than the single-vial price.
 *
 * Only annotates a card that received a kit button in this same pass, and only
 * once - the marker class makes it idempotent.
 */
function opl_pcb_annotate_per_vial( $html ) {
	if ( false === strpos( $html, 'op9-product-price' ) ) {
		return $html;
	}

	return preg_replace_callback(
		'#<div class="op9-product-price">(.*?)</div>(\s*<form class="opl-pcb-form")#s',
		function ( $m ) {
			if ( false !== strpos( $m[1], 'opl-pcb-unit' ) ) {
				return $m[0];
			}

			return '<div class="op9-product-price">' . $m[1]
				. '<span class="opl-pcb-unit"> per vial</span></div>' . $m[2];
		},
		$html
	);
}

/**
 * Rewrite raw `?post_type=product&p=N` hrefs to the product's real permalink.
 *
 * One homepage card links to `?post_type=product&#038;p=447` instead of
 * `/products/selank-5mg-research-peptide/`. It resolves, but it is inconsistent
 * with the other nine cards, bypasses the canonical URL, and is the kind of
 * link that breaks if permalink settings change.
 *
 * The snippet that renders the card is not in this repository, so the link is
 * corrected in the finished HTML. Only URLs that resolve to a published product
 * are touched; anything else is left exactly as it is.
 */
function opl_pcb_fix_raw_product_links( $html ) {
	if ( false === strpos( $html, 'post_type=product' ) ) {
		return $html;
	}

	// `~` delimits the pattern: the ampersand in these URLs is HTML-encoded as
	// `&#038;`, and a `#` delimiter would end the pattern inside it.
	return preg_replace_callback(
		'~href="([^"]*post_type=product[^"]*)"~',
		function ( $m ) {
			// The id arrives as p=447 after either a literal `&`, `&amp;` or
			// `&#038;`, so match the parameter rather than the separator.
			if ( ! preg_match( '~(?:^|[?&;])p=(\d+)~', html_entity_decode( $m[1] ), $p ) ) {
				return $m[0];
			}

			$id      = (int) $p[1];
			$product = wc_get_product( $id );

			if ( ! $product || 'publish' !== get_post_status( $id ) ) {
				return $m[0];
			}

			return 'href="' . esc_url( $product->get_permalink() ) . '"';
		},
		$html
	);
}

/**
 * Insert a control into every product card in the finished page.
 *
 * Both card grids are produced by other snippets that are not in this
 * repository, so the finished HTML is rewritten rather than their templates.
 * Each card is matched on its own product permalink, so a card can only ever
 * receive the control for the product it actually links to.
 */
function opl_pcb_rewrite( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'product-card' ) && false === strpos( $html, 'oprc-card' ) ) {
		return $html;
	}

	// Idempotent - a second pass must not double the buttons.
	if ( false !== strpos( $html, 'opl-pcb-btn' ) ) {
		return $html;
	}

	$added = 0;

	// Order matters. Repair the links first so every card resolves to a
	// product, then retarget compound cards to their kits, and only then add
	// controls - so a retargeted card gets a plain "Add to Cart" for the kit it
	// now advertises, not a "buy the kit instead" button.
	$html = opl_pcb_fix_raw_product_links( $html );
	$html = opl_pcb_feature_kits_in_grid( $html );

	// --- /research-catalog/ cards: prepend inside the existing action row.
	$html = preg_replace_callback(
		'#<div class="oprc-card-actions">\s*<a class="oprc-card-cta" href="([^"]+)"#',
		function ( $m ) use ( &$added ) {
			$product = opl_pcb_product_from_url( html_entity_decode( $m[1] ) );
			$control = opl_pcb_control( $product );

			if ( ! $control ) {
				return $m[0];
			}

			$added++;

			return '<div class="oprc-card-actions">' . $control['html']
				. '<a class="oprc-card-cta" href="' . $m[1] . '"';
		},
		$html
	);

	// --- homepage cards: replace the trailing "View Product" link.
	$html = preg_replace_callback(
		'#<a class="op9-product-link" href="([^"]+)">.*?</a>#s',
		function ( $m ) use ( &$added ) {
			$product = opl_pcb_product_from_url( html_entity_decode( $m[1] ) );
			$control = opl_pcb_control( $product );

			if ( ! $control ) {
				return $m[0];
			}

			$added++;

			// Keep the original link as the secondary action underneath.
			return $control['html'] . $m[0];
		},
		$html
	);

	// Only meaningful when the grid still shows compounds - a retargeted card
	// already prints the kit's own price, which is not per vial.
	if ( ! opl_pcb_feature_kits() ) {
		$html = opl_pcb_annotate_per_vial( $html );
	}

	if ( ! $added ) {
		return $html;
	}

	$patched = preg_replace( '#</head>#i', opl_pcb_css() . '</head>', $html, 1 );

	return ( null === $patched ? $html : $patched )
		. '<!-- opl-pcb controls=' . (int) $added . ' -->';
}

add_action( 'template_redirect', 'opl_pcb_buffer_start', PHP_INT_MAX );

function opl_pcb_buffer_start() {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	ob_start( 'opl_pcb_rewrite' );
}
