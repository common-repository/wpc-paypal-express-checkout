<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPC_PayPal_Express_Checkout_Gateway extends WC_Payment_Gateway {

    protected $log;
    protected $response;

    public function __construct() {
        try {
            $this->id = 'wpc_pp_express_checkout';
            $this->method_title = __('WPC PayPal Express Checkout', WPCPPEC_SLUG);
            $this->method_description = __('Express Checkout is a fast, easy way for buyers to pay with PayPal.', WPCPPEC_SLUG);
            $this->icon = 'https://www.paypalobjects.com/webstatic/en_US/i/buttons/pp-acceptance-small.png';
            $this->has_fields = true;

            $this->supports = array('products', 'refunds');
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->environment = $this->get_option('testmode') === "yes" ? true : false;
            $this->brand_name = $this->get_option('brand_name');
            $this->api = "";
            $this->landing_page = $this->get_option('landing_page');
            $this->payment_action = $this->get_option('payment_action') === 'Authorization' ? 'Authorization' : 'Sale';
            $this->show_on_checkout = $this->get_option('show_on_checkout') === "yes" ? true : false;
            $this->checkout_logo = $this->get_option('checkout_logo');
            $this->send_items = 'yes' === $this->get_option('send_items', 'yes');
            $this->paypal_account_optional = $this->get_option('paypal_account_optional') === "yes" ? 'yes' : 'no';
            $this->order_button_text = __('Proceed to PayPal', WPCPPEC_SLUG);
            $this->response;
            $this->is_order_completed = true;
            $this->error_display_type = $this->get_option('error_display_type') ? $this->get_option('error_display_type') : 'detailed';
            $this->error_email_notify = $this->get_option('error_email_notify') === "yes" ? true : false;
            $this->show_paypal_credit = $this->get_option('show_paypal_credit') === "yes" ? true : false;
            $this->debug = $this->get_option('debug') == 'yes' ? true : false;
            $this->skip_review_order = $this->get_option('skip_review_order') == 'yes' ? true : false;
            $this->enable_in_context_checkout_flow = $this->get_option('enable_in_context_checkout_flow') === 'yes' ? 'yes' : 'no';
            if ($this->environment) {
                $this->api_username = $this->get_option('sandbox_api_username');
                $this->api_password = $this->get_option('sandbox_api_password');
                $this->api_signature = $this->get_option('sandbox_api_signature');
                $this->paypal_url = 'https://api-3t.sandbox.paypal.com/nvp';
                $this->url_endpoint = 'https://www.sandbox.paypal.com/webscr';
            } else {
                $this->api_username = $this->get_option('api_username');
                $this->api_password = $this->get_option('api_password');
                $this->api_signature = $this->get_option('api_signature');
                $this->paypal_url = 'https://api-3t.paypal.com/nvp';
                $this->url_endpoint = 'https://www.paypal.com/cgi-bin/webscr';
            }

            $this->init_form_fields();
            $this->init_settings();

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            if (!has_action('woocommerce_api_' . strtolower(get_class($this)))) {
                add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'wpc_paypal_express_api'));
            }
            add_action('woocommerce_checkout_billing', array($this, 'wpc_ec_postdata'));
            add_action('woocommerce_available_payment_gateways', array($this, 'wpc_unset_gatway'));
            add_filter('body_class', array($this, 'wpc_bodyclass'));
            add_action('woocommerce_checkout_fields', array($this, 'wpc_show_chk_detail'));
            add_action('woocommerce_review_order_after_submit', array($this, 'wpc_cancelurl'));
            add_filter('woocommerce_terms_is_checked_default', array($this, 'wpc_term_chk'));
            add_action('woocommerce_cart_emptied', array($this, 'wpc_ec_clear_session'));
        } catch (Exception $ex) {
            wc_add_notice('<strong>' . __('Payment error', WPCPPEC_SLUG) . '</strong>: ' . $ex->getMessage(), 'error');
            return;
        }
    }

    public function wpc_paypal_express_api() {
        if (!isset($_GET['action'])) {
            return;
        }
        $cancel_url = wc_get_cart_url();
        $checkout_url = wc_get_checkout_url();
        switch ($_GET['action']) {
            case 'wpc_set_ec':
                $return_url = $this->chk_url('wpc_ec_details');
                try {
                    $this->response = $this->wpc_api()->wpc_set_ecfn(array(
                        'return_url' => $return_url,
                        'cancel_url' => $cancel_url,
                        'use_bml' => $this->show_paypal_credit && isset($_GET['use_bml']) && $_GET['use_bml'],
                        'landing_page' => $this->landing_page,
                        'brand_name' => $this->brand_name,
                        'page_style' => '',
                        'logoimg' => $this->checkout_logo,
                        'paypal_account_optional' => $this->paypal_account_optional,
                        'payment_action' => $this->payment_action,
                        'localecode' => $this->country_code(),
                    ));
                    if (isset($this->response['TOKEN']) && !empty($this->response['TOKEN'])) {
                        wp_redirect($this->wpc_ec_payurl($this->response['TOKEN']));
                        exit();

                    } else {
                        if ($this->error_display_type == 'detailed') {
                            $this->wpc_long_err();
                        } else {
                            $this->wpc_short_err();
                        }
                        $this->is_order_completed = false;
                        wp_redirect($checkout_url);
                        exit();

                    }
                } catch (Exception $e) {
                    wc_add_notice(__('An error occurred, We were unable to process your order, please try again.', WPCPPEC_SLUG), 'error');
                    wp_redirect($checkout_url);
                    exit();

                }
                exit;

            case 'wpc_ec_details':
                if (!isset($_GET['token'])) {
                    return;
                }
                $token = sanitize_text_field($_GET['token']);
                try {
                    $this->response = $this->wpc_api()->wpc_ec_details($token);

                    if ($this->response['ACK'] == 'Success') {

                        $shippdetailArr = array();

                        $shippdetailArr = array(
                            'first_name' => $this->response['FIRSTNAME'],
                            'last_name' => isset($this->response['LASTNAME']) ? $this->response['LASTNAME'] : '',
                            'company' => isset($this->response['BUSINESS']) ? $this->response['BUSINESS'] : '',
                            'email' => isset($this->response['EMAIL']) ? $this->response['EMAIL'] : '',
                            'phone' => isset($this->response['PHONENUM']) ? $this->response['PHONENUM'] : '',
                            'address_1' => isset($this->response['SHIPTOSTREET']) ? $this->response['SHIPTOSTREET'] : '',
                            'address_2' => isset($this->response['SHIPTOSTREET2']) ? $this->response['SHIPTOSTREET2'] : '',
                            'city' => isset($this->response['SHIPTOCITY']) ? $this->response['SHIPTOCITY'] : '',
                            'postcode' => isset($this->response['SHIPTOZIP']) ? $this->response['SHIPTOZIP'] : '',
                            'country' => isset($this->response['SHIPTOCOUNTRYCODE']) ? $this->response['SHIPTOCOUNTRYCODE'] : '',
                            'state' => (isset($this->response['SHIPTOCOUNTRYCODE']) && isset($this->response['SHIPTOSTATE'])) ? $this->wpc_state($this->response['SHIPTOCOUNTRYCODE'], $this->response['SHIPTOSTATE']) : ''
                        );

                        $wp_ec_responseArr = array(
                            'token' => $token,
                            'shipping_details' => $shippdetailArr,
                            'order_note' => isset($this->response['PAYMENTREQUEST_0_NOTETEXT']) ? $this->response['PAYMENTREQUEST_0_NOTETEXT'] : '',
                            'payer_id' => isset($this->response['PAYERID']) ? $this->response['PAYERID'] : '',
                            'ec_response' => $this->response
                        );
                        WC()->session->set('wpc_pp_ec', $wp_ec_responseArr);
                        WC()->session->shiptoname = $this->response['FIRSTNAME'] . ' ' . $this->response['LASTNAME'];
                        WC()->session->payeremail = $this->response['EMAIL'];
                        WC()->session->chosen_payment_method = $this->id;
                    } else {
                        $message = '';
                        if ($this->error_display_type == 'detailed') {
                            $this->wpc_long_err();
                        } else {
                            $this->wpc_short_err();
                        }

                        $this->is_order_completed = false;
                    }

                    if ($this->skip_review_order) {
                        WC()->checkout->posted = WC()->session->get('post_data');
                        $_POST = WC()->session->get('post_data');
                        $this->posted = WC()->session->get('post_data');
                        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
                        if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method']))
                            foreach ($_POST['shipping_method'] as $i => $value)
                                $chosen_shipping_methods[$i] = wc_clean($value);
                        WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
                        if (WC()->cart->needs_shipping()) {
                            // Validate Shipping Methods
                            $packages = WC()->shipping->get_packages();
                            WC()->checkout()->shipping_methods = WC()->session->get('chosen_shipping_methods');
                        }
                        if (empty($this->posted)) {
                            $this->posted = array('payment_method' => $this->id);
                        }
                        $order_id = WC()->checkout()->create_order($this->posted);
                        if (is_wp_error($order_id)) {
                            throw new Exception($order_id->get_error_message());
                        }
                        $order = wc_get_order($order_id);
                        $order->set_address(WC()->session->wpc_pp_ec['shipping_details'], 'billing');
                        $order->set_address(WC()->session->wpc_pp_ec['shipping_details'], 'shipping');
                        $order->set_payment_method($this->id);

                        update_post_meta($order_id, '_payment_method', $this->id);
                        update_post_meta($order_id, '_payment_method_title', $this->title);

                        update_post_meta($order_id, '_customer_user', get_current_user_id());
                        if (!empty(WC()->session->post_data['billing_phone'])) {
                            update_post_meta($order_id, '_billing_phone', WC()->session->post_data['billing_phone']);
                        }
                        if (!empty(WC()->session->post_data['order_comments'])) {
                            update_post_meta($order_id, 'order_comments', WC()->session->post_data['order_comments']);
                            $my_post = array(
                                'ID' => $order_id,
                                'post_excerpt' => WC()->session->post_data['order_comments'],
                            );
                            wp_update_post($my_post);
                        }
                        do_action('woocommerce_checkout_order_processed', $order_id, array());
                        $this->wpc_review_order($order_id);
                    } else {
                        wp_redirect(wc_get_checkout_url());
                        exit();
                    }
                } catch (Exception $e) {
                    wc_add_notice(__('An error occurred, We were unable to process your order, please try again.', WPCPPEC_SLUG), 'error');
                    wp_redirect($checkout_url);
                    exit();

                }
                exit;

            case 'doecpayment':

                if (!isset($_GET['orderid'])) {
                    return;
                }
                $this->wpc_review_order($_GET['orderid']);
                exit;
        }
    }

    public function wpc_review_order($orderID) {

        try {
            $cancel_url = wc_get_cart_url();
            $checkout_url = wc_get_checkout_url();
            $order = wc_get_order($orderID);
            $this->response = $this->wpc_api()->wpc_doec($order);
            if ($this->response['ACK'] == 'Success' || $this->response['ACK'] == 'SuccessWithWarning') {
                $order->add_order_note(sprintf(__('%s Transaction ID: %s', WPCPPEC_SLUG), $this->title, isset($this->response['PAYMENTINFO_0_TRANSACTIONID']) ? $this->response['PAYMENTINFO_0_TRANSACTIONID'] : ''));
                $wp_ec_responseArr = WC()->session->get('wpc_pp_ec');
                if (!empty($wp_ec_responseArr['ec_response']['PAYERSTATUS'])) {
                    $order->add_order_note(sprintf(__('Payer Status: %s', WPCPPEC_SLUG), '<strong>' . $wp_ec_responseArr['ec_response']['PAYERSTATUS'] . '</strong>'));
                }
                if (!empty($wp_ec_responseArr['ec_response']['ADDRESSSTATUS'])) {
                    $order->add_order_note(sprintf(__('Address Status: %s', WPCPPEC_SLUG), '<strong>' . $wp_ec_responseArr['ec_response']['ADDRESSSTATUS'] . '</strong>'));
                }
                if ($this->response['PAYMENTINFO_0_PAYMENTSTATUS'] == 'Completed') {
                    $order->payment_complete($this->response['PAYMENTINFO_0_TRANSACTIONID']);
                } else {
                    $this->paypal_response_status($orderID, $this->response);
                    update_post_meta(version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), '_transaction_id', $this->response['PAYMENTINFO_0_TRANSACTIONID']);
                    WC()->cart->empty_cart();
                }
                WC()->cart->empty_cart();
                wc_clear_notices();
                wp_redirect($this->get_return_url($order));
                exit();
            } else {
                if ($this->error_display_type == 'detailed') {
                    $this->wpc_long_err();
                } else {
                    $this->wpc_short_err();
                }
                wp_redirect($checkout_url);
                exit();

            }
            return;
        } catch (Exception $e) {
            wc_add_notice(__('An error occurred, We were unable to process your order, please try again.', WPCPPEC_SLUG), 'error');
            wp_redirect($cancel_url);
            exit();

        }
    }

    public function paypal_response_status($orderID, $result) {
        $order = wc_get_order($orderID);
        switch (strtolower($result['PAYMENTINFO_0_PAYMENTSTATUS'])) :
            case 'completed' :
                if ($order->status == 'completed') {
                    break;
                }
                if (!in_array(strtolower($result['PAYMENTINFO_0_TRANSACTIONTYPE']), array('cart', 'instant', 'wpc_pp_express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                    break;
                }
                $order->add_order_note(__('Payment Completed via PayPal Express Checkout', WPCPPEC_SLUG));
                $order->payment_complete($result['PAYMENTINFO_0_TRANSACTIONID']);
                $order->add_order_note(sprintf(__('%s Transaction ID: %s', WPCPPEC_SLUG), $this->title, isset($result['PAYMENTINFO_0_TRANSACTIONID']) ? $result['PAYMENTINFO_0_TRANSACTIONID'] : ''));
                $wp_ec_responseArr = WC()->session->get('wpc_pp_ec');
                if (!empty($wp_ec_responseArr['ec_response']['PAYERSTATUS'])) {
                    $order->add_order_note(sprintf(__('Payer Status: %s', WPCPPEC_SLUG), '<strong>' . $wp_ec_responseArr['ec_response']['PAYERSTATUS'] . '</strong>'));
                }
                if (!empty($wp_ec_responseArr['ec_response']['ADDRESSSTATUS'])) {
                    $order->add_order_note(sprintf(__('Address Status: %s', WPCPPEC_SLUG), '<strong>' . $wp_ec_responseArr['ec_response']['ADDRESSSTATUS'] . '</strong>'));
                }
                break;
            case 'pending' :
                if (!in_array(strtolower($result['PAYMENTINFO_0_TRANSACTIONTYPE']), array('cart', 'instant', 'wpc_pp_express_checkout', 'web_accept', 'masspay', 'send_money'))) {
                    break;
                }
                switch (strtolower($result['PAYMENTINFO_0_PENDINGREASON'])) {
                    case 'address':
                        $pending_reason = __('Address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile.', WPCPPEC_SLUG);
                        break;
                    case 'authorization':
                        $pending_reason = __('Authorization: The payment is pending because it has been authorized but not settled. You must capture the funds first.', WPCPPEC_SLUG);
                        break;
                    case 'echeck':
                        $pending_reason = __('eCheck: The payment is pending because it was made by an eCheck that has not yet cleared.', WPCPPEC_SLUG);
                        break;
                    case 'intl':
                        $pending_reason = __('intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.', WPCPPEC_SLUG);
                        break;
                    case 'multicurrency':
                    case 'multi-currency':
                        $pending_reason = __('Multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment.', WPCPPEC_SLUG);
                        break;
                    case 'order':
                        $pending_reason = __('Order: The payment is pending because it is part of an order that has been authorized but not settled.', WPCPPEC_SLUG);
                        break;
                    case 'paymentreview':
                        $pending_reason = __('Payment Review: The payment is pending while it is being reviewed by PayPal for risk.', WPCPPEC_SLUG);
                        break;
                    case 'unilateral':
                        $pending_reason = __('Unilateral: The payment is pending because it was made to an email address that is not yet registered or confirmed.', WPCPPEC_SLUG);
                        break;
                    case 'verify':
                        $pending_reason = __('Verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.', WPCPPEC_SLUG);
                        break;
                    case 'other':
                        $pending_reason = __('Other: For more information, contact PayPal customer service.', WPCPPEC_SLUG);
                        break;
                    case 'none':
                    default:
                        $pending_reason = __('No pending reason provided.', WPCPPEC_SLUG);
                        break;
                }
                $order->add_order_note(sprintf(__('Payment via Express Checkout Pending. PayPal reason: %s.', WPCPPEC_SLUG), $pending_reason));
                $order->update_status('on-hold');
                break;
            case 'denied' :
            case 'expired' :
            case 'failed' :
            case 'voided' :
                // Order failed
                $order->update_status('failed', sprintf(__('Payment %s via Express Checkout.', WPCPPEC_SLUG), strtolower($result['PAYMENTINFO_0_PAYMENTSTATUS'])));
                break;
            default:
                break;
        endswitch;

        return;
    }

    public function wpc_long_err() {
        wc_add_notice(__(urldecode($this->response["L_ERRORCODE0"]) . '-' . urldecode($this->response["L_LONGMESSAGE0"]), WPCPPEC_SLUG), 'error');
    }

    public function wpc_short_err() {
        wc_add_notice(__('An error occurred, We were unable to process your order, please try again.', WPCPPEC_SLUG), 'error');
    }

    public function wpc_ec_payurl($token) {
        $urlPara = array(
            'cmd' => '_express-checkout',
            'token' => $token,
        );
        return add_query_arg($urlPara, $this->url_endpoint);
    }

    public function wpc_api() {
        if (is_object($this->api)) {
            return $this->api;
        }
        return $this->api = new Wpc_Paypal_Express_Checkout_Api($this->id, $this->environment, $this->api_username, $this->api_password, $this->api_signature, $this->debug);
    }

    public function init_form_fields() {
        $this->form_fields = wpc_paypal_express_checkout_setting_field();
    }

    public function wpc_payment_available() {
        global $wpc_settings;
        $is_gatway_available = $wpc_settings['enabled'];
        if (isset($is_gatway_available) && $is_gatway_available == 'no') {
            return false;
        } else {
            return true;
        }
    }

    public function wpc_ec_postdata() {

        if (!isset(WC()->session->wpc_pp_ec) || !$this->wpc_ec_getsession('shipping_details')) {
            return;
        }
        foreach ($this->wpc_ec_getsession('shipping_details') as $field => $value) {
            if ($value) {
                $_POST['billing_' . $field] = $value;
            }
        }
        $order_note = $this->wpc_ec_getsession('order_note');
        if (!empty($order_note)) {
            $_POST['order_comments'] = $this->wpc_ec_getsession('order_note');
        }
        $this->chosen = true;
    }

    public function wpc_ec_getsession($key = '') {
        $session_data = null;
        if (empty($key)) {
            $session_data = WC()->session->wpc_pp_ec;
        } elseif (isset(WC()->session->wpc_pp_ec[$key])) {
            $session_data = WC()->session->wpc_pp_ec[$key];
        }
        return $session_data;
    }

    public function wpc_show_chk_detail($chkdetail) {
        if (isset(WC()->session->wpc_pp_ec) && $this->wpc_ec_getsession('shipping_details')) {
            foreach ($this->wpc_ec_getsession('shipping_details') as $field => $value) {
                if (isset($chkdetail['billing']) && isset($chkdetail['billing']['billing_' . $field])) {
                    $required = isset($chkdetail['billing']['billing_' . $field]['required']) && $chkdetail['billing']['billing_' . $field]['required'];
                    if (!$required || $required && $value) {
                        $chkdetail['billing']['billing_' . $field]['class'][] = 'express-provided';
                        $chkdetail['billing']['billing_' . $field]['class'][] = 'hidden';
                    }
                }
            }
        }
        return $chkdetail;
    }

    public function wpc_unset_gatway($gateways) {
        if (isset(WC()->session->wpc_pp_ec)) {
            foreach ($gateways as $id => $gateway) {
                if ($id !== $this->id) {
                    unset($gateways[$id]);
                }
            }
        }
        return $gateways;
    }

    public function wpc_bodyclass($classes) {
        if (isset(WC()->session->wpc_pp_ec) && is_page(wc_get_page_id('checkout'))) {
            $classes[] = 'wpc-ec';
            if ($this->show_on_checkout && isset(WC()->session->wpc_ec_term)) {
                $classes[] = 'wpc-hidden-term';
            }
        }
        return $classes;
    }

    public function wpc_cancelurl() {
        if (isset(WC()->session->wpc_pp_ec) || !$this->wpc_payment_available()) {
            return;
        }
        printf('<a href="%1$s" class="wpc-review-cancelurl">%2$s</a>', esc_url(add_query_arg(array('wpc_ec_clear' => true), wc_get_cart_url())), esc_html__('Cancel', WPCPPEC_SLUG));
    }

    public function wpc_term_chk($checked_default) {
        if (isset(WC()->session->wpc_pp_ec) || !$this->wpc_payment_available()) {
            return $checked_default;
        }
        if ($this->show_on_checkout && isset(WC()->session->wpc_ec_term)) {
            $checked_default = true;
        }
        return $checked_default;
    }

    public function wpc_ec_clear_session() {
        unset(WC()->session->wpc_pp_ec);
        unset(WC()->session->wpc_ec_term);
    }

    public function process_payment($order_id) {
        if (isset(WC()->session->wpc_pp_ec)) {
            $return_url = $this->chk_url('doecpayment', $order_id);
            $return_url = add_query_arg('orderid', $order_id, $return_url);
            $args = array(
                'result' => 'success',
                'redirect' => $return_url,
            );
            if (isset($_POST['terms']) && wc_get_page_id('terms') > 0) {
                WC()->session->wpc_ec_term = 1;
            }
            if (is_ajax()) {
                wp_send_json($args);
            } else {
                wp_redirect($args['redirect']);
                exit();

            }
            exit;
        } else {
            wc_add_notice('PayPal Express Checkout Fails', 'error');
        }
    }

    /**
     * Process a refund if supported
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $response = $this->wpc_api()->wpc_ec_refund($order_id, $amount, $reason);
        if ($response['ACK'] == 'Success' || $response['ACK'] == 'SuccessWithWarning') {
            $order->add_order_note('Refund Transaction ID:' . $response['REFUNDTRANSACTIONID']);
            $max_remaining_refund = wc_format_decimal($order->get_total() - $order->get_total_refunded());
            if (!$max_remaining_refund > 0) {
                $order->update_status('refunded');
            }
            return true;
        } else {
            return false;
        }
    }

    public function chk_url($action) {
        return add_query_arg('action', $action, WC()->api_request_url('WPC_PayPal_Express_Checkout_Gateway'));
    }

    public function country_code() {
        $locale = get_locale();
        $country_code = array('en_US', 'de_DE', 'en_AU', 'nl_NL', 'fr_FR', 'zh_XC', 'es_XC', 'zh_CN', 'fr_XC', 'en_GB', 'it_IT', 'pl_PL', 'ja_JP');
        if (!in_array($locale, $country_code)) {
            $locale = 'en_US';
        }
        return $locale;
    }

    public function wpc_state($country_code, $state) {
        if ($country_code !== 'US' && isset(WC()->countries->states[$country_code])) {
            $local_states = WC()->countries->states[$country_code];
            if (!empty($local_states) && in_array($state, $local_states)) {
                foreach ($local_states as $key => $val) {
                    if ($val === $state) {
                        return $key;
                    }
                }
            }
        }
        return $state;
    }

}
