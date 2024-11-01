<?php
/**
 * @since      1.0.0
 * @package    Wpc_Paypal_Express_Checkout
 * @subpackage Wpc_Paypal_Express_Checkout/includes
 * @author     WPCodelibrary <support@wpcodelibrary.com>
 */
class Wpc_Paypal_Express_Checkout {

    protected $loader;
    public $plugin_name;
    public $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = '1.0.0';
        $this->plugin_name = 'wpc-paypal-express-checkout';
        $this->load_dependencies();
        $this->set_locale();
        $this->define_public_hooks();
        $prefix = is_network_admin() ? 'network_admin_' : '';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Wpc_Paypal_Express_Checkout_Loader. Orchestrates the hooks of the plugin.
     * - Wpc_Paypal_Express_Checkout_i18n. Defines internationalization functionality.
     * - Wpc_Paypal_Express_Checkout_Admin. Defines all hooks for the admin area.
     * - Wpc_Paypal_Express_Checkout_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpc-paypal-express-checkout-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpc-paypal-express-checkout-i18n.php';
        if (class_exists('WC_Payment_Gateway')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpc-paypal-express-checkout-gateway.php';
        }
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wpc-paypal-express-checkout-public.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpc-paypal-express-checkout-fn.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpc-paypal-express-checkout-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wpc-paypal-express-settings.php';

        $this->loader = new Wpc_Paypal_Express_Checkout_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Wpc_Paypal_Express_Checkout_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Wpc_Paypal_Express_Checkout_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

	private function define_public_hooks() {
		global $wpc_settings;

		$plugin_public = new Wpc_Paypal_Express_Checkout_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_filter( 'woocommerce_add_to_cart_redirect', $plugin_public, 'wpc_redirect_after_addto_cart', 999, 1 );
		if ( isset ( $wpc_settings['show_on_product_page'] ) && "yes" === $wpc_settings['show_on_product_page'] ) {
			$this->loader->add_action( 'woocommerce_after_add_to_cart_button', $plugin_public, 'wpc_ec_print_chk_btn', 22 );
		}
		if ( isset( $wpc_settings['show_on_cart_page'] ) && "yes" === $wpc_settings['show_on_cart_page'] ) {
			$this->loader->add_action( 'woocommerce_proceed_to_checkout', $plugin_public, 'wpc_ec_print_chk_btn', 22 );
		}
		if ( isset( $wpc_settings['show_on_checkout'] ) && "yes" === $wpc_settings['show_on_checkout'] ) {
			$this->loader->add_action( 'woocommerce_before_checkout_form', $plugin_public, 'wpc_ec_print_chk_btn', 22 );
		}
		$this->loader->add_action( 'wp', $plugin_public, 'wpc_ec_cancelurl' );
		$this->loader->add_action( 'http_api_curl', $plugin_public, 'wpc_curl_req', 10, 3 );
		$this->loader->add_filter( 'woocommerce_payment_gateways', $plugin_public, 'wpc_add_payment_gateway', 10, 1 );
		$this->loader->add_action( 'woocommerce_after_checkout_validation', $plugin_public, 'wpc_ec_regular_checkout' );

		$this->loader->add_action( 'wc_ajax_wc_wpc_ec_update_shipping_costs', $plugin_public, 'wc_ajax_update_shipping_costs' );
		$this->loader->add_action( 'wc_ajax_wpc_ajax_generate_cart', $plugin_public, 'wpc_ajax_generate_cart' );
	}

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Wpc_Paypal_Express_Checkout_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}