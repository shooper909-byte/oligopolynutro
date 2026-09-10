<?php
/**
 * Plugin Name: OligoPoly Staging Guard — disable canonical redirects off-production
 * Description: On any hostname that is not the production canonical host, cancels redirects that would bounce the request to production, and stops WordPress core canonical redirection. Inert on production. Install as an mu-plugin on STAGING ONLY.
 * Version:     1.0.0
 * Author:      OligoPoly Laboratories
 * License:     GPL-2.0-or-later
 *
 * WHY THIS EXISTS
 * ---------------
 * The production site runs "OligoPoly SEO Remediation", which enforces canonical www URLs. When
 * production is cloned to a staging environment, that enforcement is cloned with it — so staging
 * redirects itself to production and becomes unusable (worse: anyone who believes they are testing
 * on staging is silently operating on the live site).
 *
 * Rather than editing the SEO plugin — which would then differ between environments and risk being
 * carried back to production — this drop-in neutralises the behaviour from the outside, and only
 * ever on a non-production hostname.
 *
 * WHY IT IS SAFE ON PRODUCTION
 * ----------------------------
 * Every hook below is registered only after confirming the current host is NOT the production
 * canonical host. On production the file loads, matches the host, and returns having done nothing.
 * It cannot disable canonical enforcement on the live site even if installed there by mistake.
 *
 * INSTALL
 * -------
 * Copy to wp-content/mu-plugins/ on the STAGING site. mu-plugins load before regular plugins, so
 * this registers its filters before the SEO plugin registers its redirects.
 *
 * @package OligoPoly_Staging_Guard
 */

defined( 'ABSPATH' ) || exit;

/**
 * The one hostname that is allowed to perform canonical redirection.
 *
 * Override in wp-config.php if the production hostname ever changes:
 *   define( 'OLIGOPOLY_CANONICAL_HOST', 'www.example.com' );
 */
if ( ! defined( 'OLIGOPOLY_CANONICAL_HOST' ) ) {
	define( 'OLIGOPOLY_CANONICAL_HOST', 'www.oligopolypeptides.com' );
}

/**
 * Guard against canonical redirects on non-production hostnames.
 */
final class OligoPoly_Staging_Guard {

	/**
	 * Current request host, lowercased, port stripped.
	 *
	 * @var string
	 */
	private static $host = '';

	/**
	 * Register the guard, but only off-production.
	 */
	public static function init() {
		self::$host = self::current_host();

		// No host (WP-CLI, cron) or we ARE production: do nothing at all.
		if ( '' === self::$host || self::is_production() ) {
			return;
		}

		// 1. Cancel any redirect whose destination is the production host.
		//    This is deliberately plugin-agnostic: it catches the SEO plugin's canonical
		//    enforcement, and anything else that tries the same, without knowing their hooks.
		add_filter( 'wp_redirect', array( __CLASS__, 'block_redirect_to_production' ), 1, 2 );

		// 2. Stop WordPress core's own canonical redirect, which would otherwise bounce the
		//    request to whatever home/siteurl says.
		remove_filter( 'template_redirect', 'redirect_canonical' );
		add_filter( 'redirect_canonical', '__return_false', 1 );

		// 3. Keep generated URLs on the staging host, so links, assets, and login redirects do
		//    not point at production even when the database still holds production URLs.
		add_filter( 'option_home', array( __CLASS__, 'force_staging_host' ), 1 );
		add_filter( 'option_siteurl', array( __CLASS__, 'force_staging_host' ), 1 );

		// 4. Make it obvious in wp-admin which environment this is.
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notice' ) );
	}

	/**
	 * The current request hostname, lowercased and without a port.
	 *
	 * @return string
	 */
	private static function current_host() {
		if ( empty( $_SERVER['HTTP_HOST'] ) ) {
			return '';
		}

		$host = strtolower( (string) wp_unslash( $_SERVER['HTTP_HOST'] ) );
		$host = preg_replace( '/:\d+$/', '', $host );

		// Only ever trust characters legal in a hostname.
		return (string) preg_replace( '/[^a-z0-9.\-]/', '', $host );
	}

	/**
	 * Whether this request is being served on the production canonical host.
	 *
	 * @return bool
	 */
	private static function is_production() {
		return self::$host === strtolower( OLIGOPOLY_CANONICAL_HOST );
	}

	/**
	 * Cancel a redirect that points at the production host.
	 *
	 * Returning false from the wp_redirect filter aborts the redirect entirely, so the request
	 * continues to render on the staging host.
	 *
	 * @param string $location Proposed redirect target.
	 * @param int    $status   HTTP status code.
	 * @return string|false
	 */
	public static function block_redirect_to_production( $location, $status = 302 ) {
		unset( $status );

		$target_host = strtolower( (string) wp_parse_url( (string) $location, PHP_URL_HOST ) );

		if ( '' === $target_host ) {
			// Relative redirect — stays on this host, so it is fine.
			return $location;
		}

		if ( $target_host === strtolower( OLIGOPOLY_CANONICAL_HOST ) ) {
			return false;
		}

		return $location;
	}

	/**
	 * Rewrite a stored home/siteurl option onto the current staging host.
	 *
	 * @param mixed $value Stored option value.
	 * @return mixed
	 */
	public static function force_staging_host( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		$parts = wp_parse_url( $value );

		if ( empty( $parts['host'] ) ) {
			return $value;
		}

		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
		$path   = isset( $parts['path'] ) ? $parts['path'] : '';

		return $scheme . '://' . self::$host . $path;
	}

	/**
	 * Banner so nobody mistakes staging for production.
	 */
	public static function render_admin_notice() {
		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Staging environment.', 'oligopoly-staging-guard' ),
			esc_html(
				sprintf(
					/* translators: 1: staging host, 2: production host. */
					__( 'Serving %1$s. Canonical redirects to %2$s are disabled by the staging guard mu-plugin.', 'oligopoly-staging-guard' ),
					self::$host,
					OLIGOPOLY_CANONICAL_HOST
				)
			)
		);
	}
}

OligoPoly_Staging_Guard::init();
