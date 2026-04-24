<?php
/**
 * Plugin Name: Opendatabot IBAN Invoice
 * Plugin URI:  https://iban.opendatabot.ua/create-invoice
 * Description: WooCommerce payment gateway that creates an IBAN invoice via Opendatabot and redirects the customer to pay it. Optional automatic payment confirmation via Opendatabot Autoclient.
 * Version:     0.1.0
 * Author:      Opendatabot
 * Author URI:  https://opendatabot.ua
 * Text Domain: opendatabot-iban
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 3.0
 * WC tested up to: 9.5
 *
 * @package OpendatabotIban
 */

defined( 'ABSPATH' ) || exit;

define( 'OPENDATABOT_IBAN_VERSION', '0.1.0' );
define( 'OPENDATABOT_IBAN_PLUGIN_FILE', __FILE__ );
define( 'OPENDATABOT_IBAN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OPENDATABOT_IBAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OPENDATABOT_IBAN_GATEWAY_ID', 'opendatabot_iban' );

add_action( 'plugins_loaded', 'opendatabot_iban_init', 11 );

function opendatabot_iban_init() {
	load_plugin_textdomain( 'opendatabot-iban', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'opendatabot_iban_woocommerce_missing_notice' );
		return;
	}

	require_once OPENDATABOT_IBAN_PLUGIN_DIR . 'includes/class-opendatabot-iban-gateway.php';

	add_filter( 'woocommerce_payment_gateways', 'opendatabot_iban_add_gateway' );

	add_action( 'woocommerce_api_' . OPENDATABOT_IBAN_GATEWAY_ID, 'opendatabot_iban_handle_callback' );
}

function opendatabot_iban_add_gateway( $gateways ) {
	$gateways[] = 'Opendatabot_IBAN_Gateway';
	return $gateways;
}

function opendatabot_iban_handle_callback() {
	$gateways = WC()->payment_gateways()->payment_gateways();

	if ( isset( $gateways[ OPENDATABOT_IBAN_GATEWAY_ID ] ) ) {
		$gateways[ OPENDATABOT_IBAN_GATEWAY_ID ]->handle_callback();
	}
}

function opendatabot_iban_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Opendatabot IBAN Invoice requires WooCommerce to be installed and active.', 'opendatabot-iban' );
	echo '</p></div>';
}

add_action( 'before_woocommerce_init', 'opendatabot_iban_declare_hpos_compatibility' );

function opendatabot_iban_declare_hpos_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}

add_action( 'woocommerce_blocks_loaded', 'opendatabot_iban_register_blocks_support' );

function opendatabot_iban_register_blocks_support() {
	if ( ! class_exists( 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once OPENDATABOT_IBAN_PLUGIN_DIR . 'includes/class-opendatabot-iban-blocks.php';

	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( $registry ) {
			$registry->register( new Opendatabot_IBAN_Blocks_Support() );
		}
	);
}

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . OPENDATABOT_IBAN_GATEWAY_ID );
		array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'opendatabot-iban' ) . '</a>' );
		return $links;
	}
);
