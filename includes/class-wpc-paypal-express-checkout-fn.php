<?php

if (!defined('ABSPATH'))
    exit;

class Wpc_Paypal_Express_Checkout_Api {

    public $gateway_id;
    public $order;
    public $response;

    const VERSION = '115';

    public function __construct($gateway_id, $env, $api_username, $api_password, $api_signature, $debug) {
        $this->gateway_id = $gateway_id;
        $this->request_uri = ( $env === false ) ? 'https://api-3t.paypal.com/nvp' : 'https://api-3t.sandbox.paypal.com/nvp';
        $this->api_username = $api_username;
        $this->api_password = $api_password;
        $this->api_signature = $api_signature;
        $this->debug = $debug;
        $this->log = "";
        $this->setmethods = '';
    }

    public function wpc_set_ecfn($args) {
        $this->setmethods = 'SetExpressCheckout';
        $request = $this->wpc_ec_request();
        $request->wpc_set_ecfn($args);

        $this->response = null;
        $this->request = $request;
        $response = $this->wpc_make_request($this->request_uri . '', $this->wpc_ec_para());
        $response = $this->wpc_parsestr($response);
        $this->wpc_log($response);
        return $response;
    }

    public function wpc_ec_details($token) {
        $this->setmethods = 'GetExpressCheckoutDetails';
        $this->para["METHOD"] = 'GetExpressCheckoutDetails';
        $this->para['TOKEN'] = $token;
        $request = $this->wpc_ec_request();
        $request->wpc_ec_details($token);
        $this->response = null;
        $this->request = $request;
        $response = $this->wpc_make_request($this->request_uri . '', $this->wpc_ec_para());
        $response = $this->wpc_parsestr($response);
        $this->wpc_log($response);
        return $response;
    }

    public function wpc_doec($order) {
        $this->setmethods = 'DoExpressCheckoutPayment';
        $this->order = $order;
        $request = $this->wpc_ec_request();
        $request->wpc_ec_do_payment($order);
        $this->response = null;
        $this->request = $request;
        $response = $this->wpc_make_request($this->request_uri . '', $this->wpc_ec_para());
        $response = $this->wpc_parsestr($response);
        $this->wpc_log($response);
        return $response;
    }

    public function wpc_ec_request($type = array()) {
        return new Wpc_Paypal_Express_Checkout_Api_Request($this->api_username, $this->api_password, $this->api_signature, self::VERSION);
    }

    public function wpc_make_request($wpc_url, $wpc_para) {
        return wp_safe_remote_request($wpc_url, $wpc_para);
    }

    public function wpc_ec_para() {
        $args = array(
            'method' => 'POST',
            'timeout' => 60,
            'redirection' => 0,
            'httpversion' => 1.1,
            'sslverify' => FALSE,
            'blocking' => true,
            'user-agent' => 'wpc_ec_checkout',
            'headers' => array(),
            'body' => $this->request->wpc_http_query(),
            'cookies' => array(),
        );
        return $args;
    }

    public function wpc_parsestr($response) {
        if (is_wp_error($response)) {
            return;
        }
        parse_str($response['body'], $parseStr);
        return $parseStr;
    }

    public function wpc_ec_refund($order_id, $amount, $reason) {
        $order = wc_get_order($order_id);
        $this->setmethods = 'RefundTransaction';
        $this->order = $order;

        $request = $this->wpc_ec_request();
        $order = wc_get_order($order_id);
        if ($order->get_total() == $amount) {
            $refundType = 'Full';
        } else {
            $refundType = 'Partial';
        }
        $transID = $order->get_transaction_id();
        $currency = $order->get_currency();
        $order = wc_get_order($order_id);
        $request->para['METHOD'] = 'RefundTransaction';
        $parmsRefund = array();
        $parmsRefund = array(
            'TRANSACTIONID' => $transID,
            'REFUNDTYPE' => $refundType,
            'AMT' => $amount,
            'CURRENCYCODE' => $currency,
            'NOTE' => $reason,);

        foreach ($parmsRefund as $parmsRefundkey => $parmsRefundval) {
            $request->para[$parmsRefundkey] = $parmsRefundval;
        }

        $this->response = null;
        $this->request = $request;
        $response = $this->wpc_make_request($this->request_uri . '', $this->wpc_ec_para());
        $response = $this->wpc_parsestr($response);
        $this->wpc_log($response);
        return $response;
    }

    public function wpc_api_masking($msg) {
        foreach ($msg as $key => $value) {
            if ($key == "USER" || $key == "PWD" || $key == "SIGNATURE") {
                $str_length = strlen($value);
                $maskstring = "";
                for ($i = 0; $i <= $str_length; $i++) {
                    $maskstring .= '*';
                }
                $msg[$key] = $maskstring;
            }
        }
        return $msg;
    }

    public function wpc_log($msg) {
        if ($this->debug) {
            if (empty($this->log)) {
                $this->log = new WC_Logger();
            }
            $msg = $this->wpc_api_masking($msg);
            $this->log->add('wpc_ec', $this->setmethods . print_r($msg, true));
        }
        return true;
    }

    public function wpc_round($amt) {
        $precision = 2;
        if (!self::check_decimal(get_woocommerce_currency())) {
            $precision = 0;
        }
        return round((float) $amt, $precision);
    }

    public static function check_decimal($currency) {
        if (in_array($currency, array('HUF', 'JPY', 'TWD'))) {
            return false;
        }
        return true;
    }

}