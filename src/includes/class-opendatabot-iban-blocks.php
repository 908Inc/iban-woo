<?php
/**
 * Blocks checkout integration for Opendatabot IBAN Invoice.
 *
 * @package OpendatabotIban
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Opendatabot_IBAN_Blocks_Support extends AbstractPaymentMethodType {

	protected $name = 'opendatabot_iban';

	private $gateway;

	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . OPENDATABOT_IBAN_GATEWAY_ID . '_settings', array() );

		$gateways = WC()->payment_gateways()->payment_gateways();
		$this->gateway = $gateways[ OPENDATABOT_IBAN_GATEWAY_ID ] ?? null;
	}

	public function is_active() {
		return $this->gateway && $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {
		$script_handle = 'opendatabot-iban-blocks';
		$script_path   = '/assets/js/blocks.js';
		$script_url    = OPENDATABOT_IBAN_PLUGIN_URL . 'assets/js/blocks.js';

		$script_asset = array(
			'dependencies' => array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n' ),
			'version'      => OPENDATABOT_IBAN_VERSION,
		);

		wp_register_script(
			$script_handle,
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $script_handle, 'opendatabot-iban', OPENDATABOT_IBAN_PLUGIN_DIR . 'languages' );
		}

		return array( $script_handle );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway ? $this->gateway->title : __( 'IBAN invoice (Opendatabot)', 'opendatabot-iban' ),
			'description' => $this->gateway ? $this->gateway->description : '',
			'icon'        => $this->gateway ? $this->gateway->icon : '',
			'supports'    => array( 'products' ),
		);
	}
}
