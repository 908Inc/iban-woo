<?php
/**
 * Uninstall cleanup for Opendatabot IBAN Invoice.
 *
 * @package OpendatabotIban
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'woocommerce_opendatabot_iban_settings' );
