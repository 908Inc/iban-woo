<?php
/**
 * Opendatabot IBAN Invoice — WooCommerce payment gateway.
 *
 * @package OpendatabotIban
 */

defined( 'ABSPATH' ) || exit;

class Opendatabot_IBAN_Gateway extends WC_Payment_Gateway {

	const OPENDATABOT_ENDPOINT            = 'https://iban.opendatabot.ua/api/invoice';
	const OPENDATABOT_INVOICE_URL_PREFIX  = 'https://iban.opendatabot.ua/invoice/';

	public $iban;
	public $code;
	public $x_client_key;
	public $x_client_name;
	public $purpose;
	public $order_status;
	public $autoclient;
	public $paid_order_status;

	public function __construct() {
		$this->id                 = OPENDATABOT_IBAN_GATEWAY_ID;
		$this->icon               = apply_filters( 'opendatabot_iban_icon', OPENDATABOT_IBAN_PLUGIN_URL . 'assets/images/icon.png' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Opendatabot IBAN Invoice', 'opendatabot-iban' );
		$this->method_description = __( 'Creates an IBAN invoice via Opendatabot and redirects the customer to pay it. Optionally confirms payment automatically via Opendatabot Autoclient.', 'opendatabot-iban' );
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->iban              = strtoupper( preg_replace( '/\s+/', '', (string) $this->get_option( 'iban' ) ) );
		$this->code              = preg_replace( '/\s+/', '', (string) $this->get_option( 'code' ) );
		$this->x_client_key      = trim( (string) $this->get_option( 'x_client_key' ) );
		$this->x_client_name     = trim( (string) $this->get_option( 'x_client_name' ) );
		$this->purpose           = (string) $this->get_option( 'purpose' );
		$this->order_status      = $this->get_option( 'order_status', 'pending' );
		$this->autoclient        = 'yes' === $this->get_option( 'autoclient' );
		$this->paid_order_status = $this->get_option( 'paid_order_status', 'processing' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_availability' ) );
	}

	public function init_form_fields() {
		$order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		$statuses       = array();
		foreach ( $order_statuses as $key => $label ) {
			$statuses[ str_replace( 'wc-', '', $key ) ] = $label;
		}

		$callback_url = add_query_arg( 'wc-api', $this->id, home_url( '/' ) );

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable / Disable', 'opendatabot-iban' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Opendatabot IBAN Invoice', 'opendatabot-iban' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'opendatabot-iban' ),
				'type'        => 'text',
				'description' => __( 'Title shown to the customer at checkout.', 'opendatabot-iban' ),
				'default'     => __( 'IBAN invoice (Opendatabot)', 'opendatabot-iban' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'opendatabot-iban' ),
				'type'        => 'textarea',
				'description' => __( 'Description shown to the customer at checkout.', 'opendatabot-iban' ),
				'default'     => __( 'Pay via IBAN invoice. You will be redirected to the Opendatabot payment page.', 'opendatabot-iban' ),
				'desc_tip'    => true,
			),
			'iban' => array(
				'title'       => __( 'IBAN', 'opendatabot-iban' ),
				'type'        => 'text',
				'description' => __( 'Ukrainian IBAN (UA + 27 digits).', 'opendatabot-iban' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'code' => array(
				'title'       => __( 'Code (EDRPOU / Tax ID)', 'opendatabot-iban' ),
				'type'        => 'text',
				'description' => __( 'Your EDRPOU or individual tax ID (8 or 10 digits).', 'opendatabot-iban' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'x_client_key' => array(
				'title'       => __( 'x-client-key', 'opendatabot-iban' ),
				'type'        => 'text',
				'description' => __( 'Opendatabot API client key (required for creating invoices).', 'opendatabot-iban' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'x_client_name' => array(
				'title'       => __( 'x-client-name', 'opendatabot-iban' ),
				'type'        => 'text',
				'description' => __( 'Opendatabot API client name (e.g. "public" or your app name).', 'opendatabot-iban' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'purpose' => array(
				'title'       => __( 'Payment purpose', 'opendatabot-iban' ),
				'type'        => 'text',
				'description' => __( 'Use {order_id} placeholder. If omitted, the order ID is appended automatically.', 'opendatabot-iban' ),
				'default'     => __( 'Payment for order #{order_id}', 'opendatabot-iban' ),
				'desc_tip'    => true,
			),
			'order_status' => array(
				'title'       => __( 'Initial order status', 'opendatabot-iban' ),
				'type'        => 'select',
				'description' => __( 'Status set when the order is created and customer is redirected to the invoice.', 'opendatabot-iban' ),
				'default'     => 'pending',
				'options'     => $statuses ? $statuses : array( 'pending' => __( 'Pending payment', 'opendatabot-iban' ) ),
				'desc_tip'    => true,
			),
			'autoclient_section' => array(
				'title'       => __( 'Autoclient (automatic payment confirmation)', 'opendatabot-iban' ),
				'type'        => 'title',
				'description' => __( 'Optional. Enable only if you have Autoclient configured on iban.opendatabot.ua.', 'opendatabot-iban' ),
			),
			'autoclient' => array(
				'title'       => __( 'Autoclient', 'opendatabot-iban' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable automatic payment confirmation via Autoclient callback', 'opendatabot-iban' ),
				'default'     => 'no',
				'description' => __( 'Requires a configured Autoclient on iban.opendatabot.ua. The customer will see a payment-waiting page while the autoclient polls the bank.', 'opendatabot-iban' ),
			),
			'paid_order_status' => array(
				'title'       => __( 'Paid order status', 'opendatabot-iban' ),
				'type'        => 'select',
				'description' => __( 'Status set when payment is confirmed via Autoclient callback.', 'opendatabot-iban' ),
				'default'     => 'processing',
				'options'     => $statuses ? $statuses : array( 'processing' => __( 'Processing', 'opendatabot-iban' ) ),
				'desc_tip'    => true,
			),
			'callback_url' => array(
				'title'       => __( 'Callback URL', 'opendatabot-iban' ),
				'type'        => 'text',
				'description' => __( 'Copy this URL to the "Webhook URL" / "Callback URL" field in your Autoclient settings on iban.opendatabot.ua.', 'opendatabot-iban' ),
				'default'     => $callback_url,
				'css'         => 'width: 100%;',
				'custom_attributes' => array( 'readonly' => 'readonly' ),
			),
		);
	}

	public function process_admin_options() {
		$post_data = $this->get_post_data();
		$errors    = array();

		$iban = isset( $post_data['woocommerce_' . $this->id . '_iban'] )
			? strtoupper( preg_replace( '/\s+/', '', (string) $post_data['woocommerce_' . $this->id . '_iban'] ) )
			: '';

		if ( 'yes' === ( $post_data['woocommerce_' . $this->id . '_enabled'] ?? 'no' ) ) {
			if ( '' === $iban ) {
				$errors[] = __( 'IBAN is required.', 'opendatabot-iban' );
			} elseif ( ! preg_match( '/^UA\d{27}$/', $iban ) ) {
				$errors[] = __( 'Please enter a valid UA IBAN (UA + 27 digits).', 'opendatabot-iban' );
			} elseif ( 1 !== self::iban_mod97( $iban ) ) {
				$errors[] = __( 'IBAN checksum is invalid.', 'opendatabot-iban' );
			}

			$code = isset( $post_data['woocommerce_' . $this->id . '_code'] )
				? preg_replace( '/\s+/', '', (string) $post_data['woocommerce_' . $this->id . '_code'] )
				: '';
			$length = strlen( $code );
			if ( '' === $code ) {
				$errors[] = __( 'Code (EDRPOU / Tax ID) is required.', 'opendatabot-iban' );
			} elseif ( ! ctype_digit( $code ) || ( 8 !== $length && 10 !== $length ) ) {
				$errors[] = __( 'Please enter a valid code (8 or 10 digits).', 'opendatabot-iban' );
			}

			if ( '' === trim( (string) ( $post_data['woocommerce_' . $this->id . '_x_client_key'] ?? '' ) ) ) {
				$errors[] = __( 'x-client-key is required.', 'opendatabot-iban' );
			}
			if ( '' === trim( (string) ( $post_data['woocommerce_' . $this->id . '_x_client_name'] ?? '' ) ) ) {
				$errors[] = __( 'x-client-name is required.', 'opendatabot-iban' );
			}
		}

		parent::process_admin_options();

		foreach ( $errors as $message ) {
			WC_Admin_Settings::add_error( $message );
		}
	}

	public function filter_availability( $available_gateways ) {
		if ( ! isset( $available_gateways[ $this->id ] ) ) {
			return $available_gateways;
		}

		$currency = get_woocommerce_currency();

		if ( 'UAH' !== strtoupper( (string) $currency ) ) {
			unset( $available_gateways[ $this->id ] );
			return $available_gateways;
		}

		if ( '' === $this->iban || '' === $this->code || '' === $this->x_client_key || '' === $this->x_client_name ) {
			unset( $available_gateways[ $this->id ] );
			return $available_gateways;
		}

		return $available_gateways;
	}

	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		if ( 'UAH' !== strtoupper( (string) get_woocommerce_currency() ) ) {
			return false;
		}

		if ( '' === $this->iban || '' === $this->code || '' === $this->x_client_key || '' === $this->x_client_name ) {
			return false;
		}

		return parent::is_available();
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Order not found. Please try again.', 'opendatabot-iban' ), 'error' );
			return array( 'result' => 'failure' );
		}

		if ( 'UAH' !== strtoupper( (string) $order->get_currency() ) ) {
			wc_add_notice( __( 'This payment method is available only for UAH.', 'opendatabot-iban' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$amount = number_format( (float) $order->get_total(), 2, '.', '' );
		$purpose_template = trim( (string) $this->purpose );

		if ( '' === $purpose_template ) {
			$purpose = sprintf( __( 'Payment for order #%s', 'opendatabot-iban' ), $order_id );
		} elseif ( false !== strpos( $purpose_template, '{order_id}' ) ) {
			$purpose = str_replace( '{order_id}', (string) $order_id, $purpose_template );
		} else {
			$prefix    = rtrim( $purpose_template );
			$separator = preg_match( '/[\pL\pN]$/u', $prefix ) ? ' ' : '';
			$purpose   = $prefix . $separator . $order_id;
		}

		$payload = array(
			'code'          => $this->code,
			'iban'          => $this->iban,
			'amount'        => $amount,
			'purpose'       => $purpose,
			'x-client-key'  => $this->x_client_key,
			'x-client-name' => $this->x_client_name,
		);

		if ( $this->autoclient ) {
			$payload['redirectUrl'] = $this->get_return_url( $order );
		}

		$invoice_id = $this->create_invoice( $payload, $order_id );

		if ( ! $invoice_id ) {
			wc_add_notice( __( 'Could not create the invoice. Your cart was not changed. Please try again or contact the store.', 'opendatabot-iban' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_status(
			$this->order_status ? $this->order_status : 'pending',
			__( 'Customer was redirected to Opendatabot IBAN invoice.', 'opendatabot-iban' )
		);

		$order->update_meta_data( '_opendatabot_iban_invoice_id', $invoice_id );
		$order->save();

		if ( function_exists( 'WC' ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => self::OPENDATABOT_INVOICE_URL_PREFIX . $invoice_id,
		);
	}

	private function create_invoice( array $payload, $order_id = 0 ) {
		$response = wp_remote_post(
			self::OPENDATABOT_ENDPOINT,
			array(
				'timeout'     => 20,
				'redirection' => 3,
				'body'        => $payload,
				'headers'     => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'Opendatabot IBAN: HTTP error: ' . $response->get_error_message() . ( $order_id ? ' (order_id=' . $order_id . ')' : '' ) );
			return null;
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$body      = (string) wp_remote_retrieve_body( $response );

		if ( $http_code < 200 || $http_code >= 300 ) {
			$snippet = trim( preg_replace( '/\s+/', ' ', $body ) );
			if ( strlen( $snippet ) > 500 ) {
				$snippet = substr( $snippet, 0, 500 ) . '...';
			}
			$this->log( 'Opendatabot IBAN: HTTP ' . $http_code . '; response: ' . $snippet . ( $order_id ? ' (order_id=' . $order_id . ')' : '' ) );
			return null;
		}

		$decoded = json_decode( $body, true );

		if ( ! is_array( $decoded ) || empty( $decoded['id'] ) ) {
			$snippet = trim( preg_replace( '/\s+/', ' ', $body ) );
			if ( strlen( $snippet ) > 500 ) {
				$snippet = substr( $snippet, 0, 500 ) . '...';
			}
			$this->log( 'Opendatabot IBAN: Unexpected API response: ' . $snippet . ( $order_id ? ' (order_id=' . $order_id . ')' : '' ) );
			return null;
		}

		return (string) $decoded['id'];
	}

	public function handle_callback() {
		header( 'Content-Type: application/json; charset=utf-8' );

		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			status_header( 405 );
			echo wp_json_encode( array( 'error' => 'Method not allowed' ) );
			exit;
		}

		$raw_body = file_get_contents( 'php://input' );

		if ( false === $raw_body || '' === $raw_body ) {
			status_header( 400 );
			echo wp_json_encode( array( 'error' => 'Empty body' ) );
			exit;
		}

		if ( '' !== $this->x_client_key ) {
			$signature = $_SERVER['HTTP_SIGNATURE'] ?? '';

			if ( '' === $signature ) {
				status_header( 401 );
				echo wp_json_encode( array( 'error' => 'Missing signature' ) );
				exit;
			}

			$expected = hash_hmac( 'sha256', $raw_body, $this->x_client_key );

			if ( ! hash_equals( $expected, $signature ) ) {
				status_header( 401 );
				echo wp_json_encode( array( 'error' => 'Invalid signature' ) );
				exit;
			}
		}

		$data = json_decode( $raw_body, true );

		if ( ! is_array( $data ) ) {
			status_header( 400 );
			echo wp_json_encode( array( 'error' => 'Invalid JSON' ) );
			exit;
		}

		$order_id = isset( $data['invoiceNumber'] ) ? (int) $data['invoiceNumber'] : 0;

		if ( ! $order_id ) {
			$this->log( 'Opendatabot IBAN callback: Missing or invalid invoiceNumber in payload' );
			status_header( 200 );
			echo wp_json_encode( array( 'ok' => true, 'matched' => false ) );
			exit;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->log( 'Opendatabot IBAN callback: Order not found (order_id=' . $order_id . ')' );
			status_header( 200 );
			echo wp_json_encode( array( 'ok' => true, 'matched' => false ) );
			exit;
		}

		$paid_status = $this->paid_order_status ? $this->paid_order_status : 'processing';

		if ( $order->get_status() === $paid_status || $order->is_paid() ) {
			$this->log( 'Opendatabot IBAN callback: Order #' . $order_id . ' already paid — skipping' );
			echo wp_json_encode( array( 'ok' => true, 'matched' => true, 'order_id' => $order_id, 'skipped' => true ) );
			exit;
		}

		$order->payment_complete();
		$order->update_status(
			$paid_status,
			__( 'Payment confirmed via Opendatabot autoclient.', 'opendatabot-iban' )
		);

		$this->log( 'Opendatabot IBAN callback: Order #' . $order_id . ' marked as paid' );

		echo wp_json_encode( array( 'ok' => true, 'matched' => true, 'order_id' => $order_id ) );
		exit;
	}

	private function log( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->info( $message, array( 'source' => 'opendatabot-iban' ) );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $message );
		}
	}

	public static function iban_mod97( $iban ) {
		$iban       = strtoupper( $iban );
		$rearranged = substr( $iban, 4 ) . substr( $iban, 0, 4 );
		$numeric    = '';
		$len        = strlen( $rearranged );

		for ( $i = 0; $i < $len; $i++ ) {
			$c = $rearranged[ $i ];
			if ( $c >= 'A' && $c <= 'Z' ) {
				$numeric .= (string) ( ord( $c ) - 55 );
			} else {
				$numeric .= $c;
			}
		}

		$remainder = 0;
		$nlen      = strlen( $numeric );

		for ( $i = 0; $i < $nlen; $i++ ) {
			$remainder = ( $remainder * 10 + (int) $numeric[ $i ] ) % 97;
		}

		return $remainder;
	}
}
