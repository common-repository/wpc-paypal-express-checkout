<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wpc_Paypal_Express_Checkout
 * @subpackage Wpc_Paypal_Express_Checkout/public
 * @author     WPCodelibrary <support@wpcodelibrary.com>
 */
class Wpc_Paypal_Express_Checkout_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    const API_VERSION = '120.0';

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wpc-paypal-express-checkout-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
	public function enqueue_scripts() {

		global $wpc_settings;

		$env         = isset( $wpc_settings['testmode'] ) && $wpc_settings['testmode'] === 'yes' ? 'sandbox' : 'production';
		$is_incotext = isset( $wpc_settings['enable_in_context_checkout_flow'] ) && $wpc_settings['enable_in_context_checkout_flow'] === 'yes' ? 'yes' : 'no';
		$size        = isset ( $wpc_settings['button_size'] ) ? $wpc_settings['button_size'] : 'small';
		$shape       = isset( $wpc_settings['button_shape'] ) ? $wpc_settings['button_shape'] : 'pill';
		$color       = isset( $wpc_settings['button_color'] ) ? $wpc_settings['button_color'] : 'gold';

		if ( ! empty( $is_incotext ) && $is_incotext === 'yes' ) {

			if ( $env == 'sandbox' ) {
				$merchant_id = ! empty( $wpc_settings['wpc_pal_id_sandbox'] ) ? $wpc_settings['wpc_pal_id_sandbox'] : $this->get_payer_id();
			} else {
				$merchant_id = ! empty( $wpc_settings['wpc_pal_id_live'] ) ? $wpc_settings['wpc_pal_id_live'] : $this->get_payer_id();
			}

			wp_enqueue_script( 'paypal-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true );
			if ( is_cart() || is_checkout() ) {
				wp_enqueue_script( 'wpc-in-context-checkout-js-frontend', plugin_dir_url( __FILE__ ) . 'js/wpc-paypal-express-checkout-public.js', array( 'jquery' ), $this->version, false );
			}
			wp_localize_script( 'wpc-in-context-checkout-js-frontend', 'wpc_in_content_param', array(
					'payer_id'                    => $merchant_id,
					'environment'                 => $env,
					'locale'                      => $this->country_code(),
					'start_flow'                  => esc_url( add_query_arg( array( 'startcheckout' => 'true' ), wc_get_page_permalink( 'cart' ) ) ),
					'show_modal'                  => apply_filters( 'woocommerce_paypal_express_checkout_show_cart_modal', true ),
					'update_shipping_costs_nonce' => wp_create_nonce( '_wc_wpc_ec_update_shipping_costs_nonce' ),
					'ajaxurl'                     => WC_AJAX::get_endpoint( 'wc_wpc_ec_update_shipping_costs' ),
					'generate_cart_nonce'         => wp_create_nonce( '_wpc_generate_cart_nonce' ),
					'add_to_cart_ajaxurl'         => WC_AJAX::get_endpoint( 'wpc_ajax_generate_cart' ),
					'set_express_checkout'        => add_query_arg( 'action', 'wpc_set_ec', WC()->api_request_url( 'WPC_PayPal_Express_Checkout_Gateway' ) ),
					'is_product'                  => is_product() ? "yes" : "no",
					'size'                        => $size,
					'shape'                       => $shape,
					'color'                       => $color,
				) );
		}
	}

    public function wpc_add_payment_gateway($methods) {
        $methods[] = 'WPC_PayPal_Express_Checkout_Gateway';
        return $methods;
    }

    public function wpc_ec_print_chk_btn() {
        global $post, $product, $wpc_settings;
        $display_style = 'display: none;';
        $is_gatway_available = $wpc_settings['enabled'];
        $enable_in_context_checkout_flow = $wpc_settings['enable_in_context_checkout_flow'];
        if (isset($is_gatway_available) && $is_gatway_available == 'no') {
            return;
        }
        $display_cc = !empty($wpc_settings['show_paypal_credit']) ? $wpc_settings['show_paypal_credit'] : 'no';
        $cc_availabe = false;
        if ($display_cc == 'yes') {
            if (substr(get_option("woocommerce_default_country"), 0, 2) == 'US') {
                $cc_availabe = true;
            } else {
                $cc_availabe = false;
            }
        }
        if (is_checkout()) {
            if ($wpc_settings['show_on_checkout'] == 'no') {
                return;
            }
        }
        if (is_cart()) {
            if ($wpc_settings['show_on_cart_page'] == 'no') {
                return;
            }
        }
        if (is_product()) {
            $product = wc_get_product($post->ID);
            if ($product->is_type('simple') || $product->is_type('variable')) {
                $display_style = 'display: block;';
            }
            if ($wpc_settings['show_on_product_page'] == 'no') {
                return;
            }
        }

        if (WC()->cart->needs_payment() || is_product()) {
            $_product = wc_get_product($post->ID);
            if ($_product == true) {
                if ($_product->is_type('simple') && (version_compare(WC_VERSION, '3.0', '<') == false)) {
                    ?>
                    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product->get_id()); ?>" />
                    <?php
                }
            }
            $btn_html = '';
            $wpc_btn_link = add_query_arg('action', 'wpc_set_ec', WC()->api_request_url('WPC_PayPal_Express_Checkout_Gateway'));
            if ($this->country_code() == 'en_US') {
                $image_path = "https://www.paypalobjects.com/webstatic/" . $this->country_code() . "/i/buttons/checkout-logo-medium.png";
            } else {
                $image_path = esc_url(add_query_arg('cmd', '_dynamic-image', add_query_arg('locale', $this->country_code(), 'https://fpdbs.paypal.com/dynamicimageweb')));
            }
            $btn_html .= '<div class="wpc_ec_button_div"><a href="' . $wpc_btn_link . '" class="single_add_to_cart_button cls-wpc-ec-btn paypal_checkout_button_incon">';
            if (is_product()) {
                $btn_html .= '<input type="image" class="single_add_to_cart_button" src="' . $image_path . '" style="clear: both;" />';
            }

            if ($enable_in_context_checkout_flow == 'no' && ( is_cart() || is_checkout())) {
                $btn_html .= '<input type="image" class="single_add_to_cart_button" src="' . $image_path . '" style="clear: both;" />';
            }
            $btn_html .= "</a></div>";

            if ($cc_availabe) {
                $btn_html .= '<div class="wpc_ec_cc_button_div"><a href="' . esc_url(add_query_arg('use_bml', 'true', $wpc_btn_link)) . '" class="single_add_to_cart_button cls-wpc-ec-cc-btn paypal_checkout_button_cc_bottom_incon">';
                $btn_html .= '<input type="image" data-action="' . esc_url($wpc_btn_link) . '"  class="single_add_to_cart_button" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_bml_SM.png" width="145" height="32" style="width: 145px; height: 32px; float: right; clear: both; border: none; padding: 0; margin: 0;" align="top" />';
                $btn_html .= '</a>';
                $btn_html .= '</div>';
            }
            if (is_checkout()) {
                $btn_html = '<div class="col2-set" id="customer_details"><div class="wpc_ec_btn_chk"><div id="wpc_chk_btn_div" >' . $btn_html . '</div><div id="wpc_ec_txt" >' . $wpc_settings['skip_text'] . '</div></div></div>';
            }
            if (is_product()) {
                $btn_html = '<div id="wpc_btn_product" style="' . $display_style . '">' . $btn_html . '</div>';
            }
            echo wp_kses_post($btn_html);
        }
    }

    public function wpc_ec_regular_checkout() {
        if (isset($_POST['payment_method']) && 'wpc_pp_express_checkout' === $_POST['payment_method'] && wc_notice_count('error') == 0) {
            WC()->session->post_data = $_POST;

            if (!isset(WC()->session->wpc_pp_ec)) {
                $args = array(
                    'result' => 'success',
                    'redirect' => add_query_arg('action', 'wpc_set_ec', WC()->api_request_url('WPC_PayPal_Express_Checkout_Gateway')),
                );
                if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                    WC()->session->wpc_ec_term = 1;
                }
                if (is_ajax()) {
                    wp_send_json($args);
                } else {
                    wp_redirect($args['redirect']);
                }
                exit;
            }
        }
    }

    public function wpc_ec_cancelurl() {
        if (!empty($_GET['wpc_ec_clear'])) {
            unset(WC()->session->wpc_pp_ec);
            unset(WC()->session->wpc_ec_term);
            wc_add_notice(__('You have cancelled checkout. Please try again.', WPCPPEC_SLUG), 'notice');
        }
    }

    public function wpc_redirect_after_addto_cart($url) {
        if (isset($_POST['wpc_btn_product']) && !empty($_POST['wpc_btn_product'])) {
            return wp_kses_post($_POST['wpc_btn_product']);
        } else {
            return $url;
        }
    }

    public function wpc_curl_req($handle, $r, $url) {
        if (strstr($url, 'https://') && strstr($url, '.paypal.com')) {
            curl_setopt($handle, CURLOPT_VERBOSE, 1);
            curl_setopt($handle, CURLOPT_SSLVERSION, 6);
        }
    }

    public function country_code() {
        $locale = get_locale();
        $country_code = array(
            'en_US', 'fr_XC', 'es_XC', 'zh_XC', 'en_AU', 'de_DE', 'nl_NL',
            'fr_FR', 'pt_BR', 'fr_CA', 'zh_CN', 'ru_RU', 'en_GB', 'zh_HK',
            'he_IL', 'it_IT', 'ja_JP', 'pl_PL', 'pt_PT', 'es_ES', 'sv_SE', 'zh_TW', 'tr_TR'
        );

        if (!in_array($locale, $country_code)) {
            $locale = 'en_US';
        }
        return $locale;
    }

    public function get_payer_id() {

        $wpc_settings = get_option('woocommerce_wpc_pp_express_checkout_settings');
        $env = $wpc_settings['testmode'];
        if (!empty($env) && $env === 'yes') {
            $sig = $wpc_settings['sandbox_api_signature'];
            $pwd = $wpc_settings['sandbox_api_password'];
            $user = $wpc_settings['sandbox_api_username'];
            $payurl = 'https://api-3t.sandbox.paypal.com/nvp';
            $option_key = 'wpc_pal_id_sandbox';
        } else {
            $sig = $wpc_settings['api_signature'];
            $pwd = $wpc_settings['api_password'];
            $user = $wpc_settings['api_username'];
            $payurl = 'https://api-3t.paypal.com/nvp';
            $option_key = 'wpc_pal_id_live';
        }
        $payer_id = get_option($option_key);
        if (!empty($payer_id)) {
            return $payer_id;
        } else {
            $palArr = array(
                'VERSION' => '120.0',
                'SIGNATURE' => $sig,
                'USER' => $user,
                'PWD' => $pwd,
                'METHOD' => 'GetPalDetails');
            $result = $this->getpaldetail($palArr, $payurl);
            $result = parse_str($result['body'], $parsed_response);
            if (!empty($parsed_response['PAL'])) {
                update_option($option_key, wc_clean($parsed_response['PAL']));
                return $payer_id;
            }
        }

        return false;
    }

    public function wc_ajax_update_shipping_costs() {
        if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), '_wc_wpc_ec_update_shipping_costs_nonce')) {
            wp_die(esc_html('Cheatin&#8217; huh?'));
        }
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        WC()->shipping->reset_shipping();
        WC()->cart->calculate_totals();

        wp_send_json(new stdClass());
    }

    public function wpc_ajax_generate_cart() {
        global $post;
        if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), '_wpc_generate_cart_nonce')) {
            wp_die(esc_html('Cheatin&#8217; huh?'));
        }
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        if (!defined('WOOCOMMERCE_CHECKOUT')) {
            define('WOOCOMMERCE_CHECKOUT', true);
        }
        WC()->shipping->reset_shipping();
        if (is_product()) {
            $product = wc_get_product($post->ID);
            $qty = !isset($_POST['qty']) ? 1 : absint($_POST['qty']);
            if ($product->is_type('variable')) {
                $attributes = array_map('wc_clean', $_POST['attributes']); // phpcs:ignore
                if (version_compare(WC_VERSION, '3.0', '<')) {
                    $variation_id = $product->get_matching_variation($attributes);
                } else {
                    $data_store = WC_Data_Store::load('product');
                    $variation_id = $data_store->find_matching_product_variation($product, $attributes);
                }
                WC()->cart->add_to_cart($product->get_id(), $qty, $variation_id, $attributes);
            } elseif ($product->is_type('simple')) {
                WC()->cart->add_to_cart($product->get_id(), $qty);
            }
            WC()->cart->calculate_totals();
        }
        $url = esc_url_raw(add_query_arg('action', 'wpc_set_ec', WC()->api_request_url('WPC_PayPal_Express_Checkout_Gateway'), home_url('/')));

        if (!empty($_POST['is_cc']) && $_POST['is_cc'] == 'true') {
            $url = add_query_arg('use_bml', 'true', $url);
        }

        wp_send_json(array('url' => $url));
    }

    public function getpaldetail($palArr, $payurl) {
        return wp_safe_remote_post($payurl, array(
            'method' => 'POST',
            'headers' => array(
                'PAYPAL-NVP' => 'Y'
            ),
            'body' => $palArr,
            'timeout' => 60,
            'user-agent' => WPCPPEC_SLUG,
            'httpversion' => '1.1'
        ));
    }

}
