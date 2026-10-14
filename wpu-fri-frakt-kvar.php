<?php
/**
 * Plugin Name:       WPU Fri frakt kvar
 * Plugin URI:        https://wordpressutveckling.se/kvar-till-fri-frakt-woocommerce/
 * Description:       Visar "X kr kvar till fri frakt" eller "Du har fri frakt" i varukorgen, minivarukorgen och kassan. Beloppet läses från butikens egen fraktmetod Fri frakt i kundens fraktzon. Fungerar i både blockvarukorgen/blockkassan och de klassiska.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * Author:            Pihlfelt & Vänner
 * Author URI:        https://pihlfelt.se
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpu-fri-frakt-kvar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Works out where the cart stands against the Free shipping method in the customer's zone.
 *
 * Returns state "none" (nothing to show), "free" or "remaining" plus the amounts.
 */
function wpu_ffk_status(): array {
	$none = array( 'state' => 'none' );
	$cart = WC()->cart;

	if ( ! $cart || $cart->is_empty() || ! $cart->needs_shipping() ) {
		return $none;
	}

	// The same package WooCommerce uses for shipping, so the zone follows the customer's address
	// (or the store's default customer location before an address is entered).
	$packages = $cart->get_shipping_packages();
	$package  = reset( $packages );
	if ( ! $package ) {
		return $none;
	}

	$zone       = WC_Shipping_Zones::get_zone_matching_package( $package );
	$best       = null;
	$has_coupon = wpu_ffk_has_free_shipping_coupon();

	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( ! $method instanceof WC_Shipping_Free_Shipping ) {
			continue;
		}

		// Let WooCommerce decide first. This also respects coupons and any
		// woocommerce_shipping_free_shipping_is_available filters in the store.
		if ( $method->is_available( $package ) ) {
			return array( 'state' => 'free' );
		}

		// Only a minimum amount can be reached by adding more to the cart.
		// "both" also needs a free shipping coupon, so it only counts once one is applied.
		$reachable = in_array( $method->requires, array( 'min_amount', 'either' ), true )
			|| ( 'both' === $method->requires && $has_coupon );
		if ( ! $reachable ) {
			continue;
		}

		$threshold = (float) $method->min_amount;
		$total     = wpu_ffk_cart_total( $method );
		$remaining = $threshold - $total;

		// Already over the amount but still not available: another rule or filter says no.
		if ( $remaining <= 0 ) {
			continue;
		}

		if ( null === $best || $remaining < $best['remaining'] ) {
			$best = array(
				'state'     => 'remaining',
				'remaining' => $remaining,
				'threshold' => $threshold,
				'total'     => $total,
			);
		}
	}

	return $best ?? $none;
}

/**
 * Mirrors the total WC_Shipping_Free_Shipping::is_available() compares with min_amount.
 *
 * The displayed subtotal follows "Display prices during cart and checkout" (incl. or excl. tax),
 * and discounts are subtracted unless the method applies the rule before coupon discounts.
 */
function wpu_ffk_cart_total( WC_Shipping_Free_Shipping $method ): float {
	$cart  = WC()->cart;
	$total = $cart->get_displayed_subtotal();

	if ( 'no' === $method->ignore_discounts ) {
		$total -= $cart->get_discount_total();
		if ( $cart->display_prices_including_tax() ) {
			$total -= $cart->get_discount_tax();
		}
	}

	return (float) \Automattic\WooCommerce\Utilities\NumberUtil::round( $total, wc_get_price_decimals() );
}

function wpu_ffk_has_free_shipping_coupon(): bool {
	foreach ( WC()->cart->get_coupons() as $coupon ) {
		if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
			return true;
		}
	}
	return false;
}

/**
 * The message as plain text, so the same string works in PHP templates and in the block script.
 */
function wpu_ffk_message( array $status ): string {
	if ( 'free' === $status['state'] ) {
		$message = __( 'Du har fri frakt.', 'wpu-fri-frakt-kvar' );
	} elseif ( 'remaining' === $status['state'] ) {
		// wc_price() returns HTML with &nbsp; and the currency symbol; we want plain text.
		$amount = html_entity_decode( wp_strip_all_tags( wc_price( $status['remaining'] ) ), ENT_QUOTES, 'UTF-8' );
		/* translators: %s: amount left, e.g. "120,00 kr" */
		$message = sprintf( __( '%s kvar till fri frakt.', 'wpu-fri-frakt-kvar' ), $amount );
	} else {
		$message = '';
	}

	return (string) apply_filters( 'wpu_fri_frakt_kvar_message', $message, $status );
}

function wpu_ffk_percent( array $status ): int {
	if ( 'free' === $status['state'] ) {
		return 100;
	}
	if ( empty( $status['threshold'] ) ) {
		return 0;
	}
	return (int) max( 0, min( 100, floor( $status['total'] / $status['threshold'] * 100 ) ) );
}

/*
 * Classic cart, mini-cart and classic checkout.
 * These are PHP templates that WooCommerce re-renders over AJAX, so the notice updates by itself.
 */
function wpu_ffk_html(): string {
	$status  = wpu_ffk_status();
	$message = wpu_ffk_message( $status );
	if ( '' === $message ) {
		return '';
	}

	// Inline styles keep the plugin free of an extra CSS request on every page.
	return sprintf(
		'<div class="wpu-ffk wpu-ffk--%1$s" style="margin:0 0 1em"><p style="margin:0 0 .4em">%2$s</p><span aria-hidden="true" style="display:block;height:4px;background:rgba(0,0,0,.1)"><span style="display:block;height:100%%;width:%3$d%%;background:currentColor"></span></span></div>',
		esc_attr( $status['state'] ),
		esc_html( $message ),
		wpu_ffk_percent( $status )
	);
}

// Classic cart: inside div.cart_totals, which cart.js replaces after quantity, coupon and shipping changes.
add_action( 'woocommerce_before_cart_totals', 'wpu_ffk_print' );
// Classic mini-cart (widget and themes using woocommerce_mini_cart()), refreshed through cart fragments.
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'wpu_ffk_print' );
function wpu_ffk_print() {
	echo wpu_ffk_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wpu_ffk_html().
}

// Classic checkout: the order review table is reloaded when the address changes, so the zone is re-read.
add_action( 'woocommerce_review_order_before_order_total', 'wpu_ffk_print_checkout_row' );
function wpu_ffk_print_checkout_row() {
	$html = wpu_ffk_html();
	if ( '' !== $html ) {
		echo '<tr class="wpu-ffk-row"><td colspan="2">' . $html . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/*
 * Block cart and block checkout.
 * The blocks render in the browser from the Store API, so we add our data to the cart response
 * and print it with a small script in the ExperimentalOrderMeta slot.
 */
add_action( 'woocommerce_blocks_loaded', 'wpu_ffk_register_store_api_data' );
function wpu_ffk_register_store_api_data() {
	if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
		return;
	}
	woocommerce_store_api_register_endpoint_data(
		array(
			'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
			'namespace'       => 'wpu-fri-frakt-kvar',
			'data_callback'   => 'wpu_ffk_store_api_data',
			'schema_callback' => 'wpu_ffk_store_api_schema',
			'schema_type'     => ARRAY_A,
		)
	);
}

function wpu_ffk_store_api_data(): array {
	$status = wpu_ffk_status();
	return array(
		'state'   => $status['state'],
		'message' => wpu_ffk_message( $status ),
		'percent' => wpu_ffk_percent( $status ),
	);
}

function wpu_ffk_store_api_schema(): array {
	return array(
		'state'   => array(
			'description' => 'none, free or remaining',
			'type'        => 'string',
			'readonly'    => true,
		),
		'message' => array(
			'description' => 'Text shown to the customer',
			'type'        => 'string',
			'readonly'    => true,
		),
		'percent' => array(
			'description' => 'Progress towards free shipping, 0-100',
			'type'        => 'integer',
			'readonly'    => true,
		),
	);
}

// Load the script only when the Cart or Checkout block is rendered.
add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', 'wpu_ffk_enqueue_block_script' );
add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', 'wpu_ffk_enqueue_block_script' );
function wpu_ffk_enqueue_block_script() {
	wp_enqueue_script(
		'wpu-fri-frakt-kvar',
		plugins_url( 'fri-frakt-kvar.js', __FILE__ ),
		array( 'wc-blocks-checkout', 'wp-element', 'wp-plugins' ),
		'1.0.0',
		array( 'in_footer' => true )
	);
}
