<?php

/**
 * Plugin Name:       In-Context PayPal Express Checkout for WooCommerce
 * Plugin URI:        http://www.wpcodelibrary.com
 * Description:       The PayPal Express Checkout gives you a simplified best checkout experience.
 * Version:           1.2.2
 * Author:            WPCodelibrary
 * Author URI:        #
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpc-paypal-express-checkout
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!defined('WPC_PP_EC_PLUGIN_BASENAME')) {
    define('WPC_PP_EC_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
define( 'WPCPPEC_VER', '1.0.0' );


if (!defined('WPC_PP_EC_PLUGIN_DIR')) {
    define('WPC_PP_EC_PLUGIN_DIR', dirname(__FILE__));
}

if ( ! defined( 'WPCPPEC_SLUG' ) ) {
	define( 'WPCPPEC_SLUG', 'wpc-paypal-express-checkout' );
}

global $woocommerce, $wpc_settings;

$wpc_settings = get_option( 'woocommerce_wpc_pp_express_checkout_settings' );
if (substr(get_option("woocommerce_default_country"),0,2) != 'US') {
    $wpc_settings['show_paypal_credit'] = 'no';
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpc-paypal-express-checkout-activator.php
 */
function activate_wpc_paypal_express_checkout() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpc-paypal-express-checkout-activator.php';
	Wpc_Paypal_Express_Checkout_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpc-paypal-express-checkout-deactivator.php
 */
function deactivate_wpc_paypal_express_checkout() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpc-paypal-express-checkout-deactivator.php';
	Wpc_Paypal_Express_Checkout_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpc_paypal_express_checkout' );
register_deactivation_hook( __FILE__, 'deactivate_wpc_paypal_express_checkout' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpc-paypal-express-checkout.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpc_paypal_express_checkout() {

	$plugin = new Wpc_Paypal_Express_Checkout();
	$plugin->run();

}


add_action('plugins_loaded', 'load_wpc_ec_class');

function load_wpc_ec_class() {
    run_wpc_paypal_express_checkout();
}