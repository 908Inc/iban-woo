=== Opendatabot IBAN Invoice ===
Contributors: opendatabot
Tags: woocommerce, payment gateway, iban, ukraine, opendatabot
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce payment gateway that creates an IBAN invoice via Opendatabot and redirects the customer to pay it.

== Description ==

Adds a WooCommerce payment method that creates an IBAN invoice via Opendatabot and redirects the customer to the Opendatabot payment page. Optionally confirms payment automatically via Opendatabot Autoclient (bank polling + webhook callback).

Works with both Classic Checkout (shortcode) and Blocks Checkout.

* Payment available only for UAH currency.
* Requires Opendatabot API credentials (x-client-key, x-client-name) — get them at https://iban.opendatabot.ua/create-invoice.
* Requires PHP cURL (via WP HTTP API) and outbound access to https://iban.opendatabot.ua from your server.

== Installation ==

1. Upload the plugin archive via WordPress admin → Plugins → Add New → Upload Plugin, or unpack into `wp-content/plugins/opendatabot-iban`.
2. Activate the plugin.
3. WooCommerce → Settings → Payments → Opendatabot IBAN Invoice → Manage.
4. Fill in IBAN, EDRPOU/Tax ID, x-client-key, x-client-name. Enable and save.
5. (Optional) Enable Autoclient, then copy the displayed Callback URL into your Autoclient settings on iban.opendatabot.ua.

== Changelog ==

= 0.1.0 =
* Initial release.
