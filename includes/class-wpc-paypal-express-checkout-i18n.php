<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wpc_Paypal_Express_Checkout
 * @subpackage Wpc_Paypal_Express_Checkout/includes
 * @author     WPCodelibrary <support@wpcodelibrary.com>
 */
class Wpc_Paypal_Express_Checkout_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wpc-paypal-express-checkout',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
