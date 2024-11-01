<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wpc_Paypal_Express_Checkout_Api_Request {

	public $para = array();
	public $order;

	public function __construct( $api_username, $api_password, $api_signature, $api_version ) {
		$userAPI = array( 'USER' => $api_username, 'PWD' => $api_password, 'SIGNATURE' => $api_signature, 'VERSION' => $api_version );
		foreach ( $userAPI as $userkey => $userval ) {
			$this->para[ $userkey ] = $userval;
		}
		$this->wpc_settings = get_option( 'woocommerce_wpc_pp_express_checkout_settings' );
	}

	public function wpc_set_ecfn( $args ) {
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}
		$this->para['METHOD'] = 'SetExpressCheckout';
		$defaults             = array(
			'use_bml'                 => false,
			'paypal_account_optional' => false,
			'landing_page'            => 'billing',
			'page_style'              => null,
			'brand_name'              => null,
			'payment_action'          => 'Sale',
		);
		$args                 = wp_parse_args( $args, $defaults );
		$arrParams = array(
			'RETURNURL'    => $args['return_url'],
			'CANCELURL'    => $args['cancel_url'],
			'PAGESTYLE'    => $args['page_style'],
			'BRANDNAME'    => $args['brand_name'],
			'LOCALECODE'   => ( get_locale() != '' ) ? get_locale() : '',
			'HDRIMG'       => isset ( $args['hdrimg'] ) ? $args['hdrimg'] : '',
			'LOGOIMG'      => isset( $args['logoimg'] ) ? $args['logoimg'] : '',
			'SOLUTIONTYPE' => $args['paypal_account_optional'] ? 'Sole' : 'Mark',
			'LANDINGPAGE'  => ( 'login' == $args['landing_page'] ) ? 'Login' : 'Billing',
		);
		foreach ( $arrParams as $key => $value ) {
			$this->para[ $key ] = $value;
		}

		if ( $args['use_bml'] ) {
			$useBML = array(
				'USERSELECTEDFUNDINGSOURCE' => 'BML',
				'SOLUTIONTYPE'              => 'Sole',
				'LANDINGPAGE'               => 'Billing',
			);
			foreach ( $useBML as $keybml => $valbml ) {
				$this->para[ $keybml ] = $valbml;
			}
		}
		$i = 0;
		$j = 0;
		$k = 0;
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}
		WC()->cart->calculate_totals();
		if ( isset( $wpc_settings['send_item'] ) && $wpc_settings['send_item'] == 'no' ) {
			$this->is_not_send_itme();
		} else {
			$roundTotal = 0;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product  = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$qty      = absint( $cart_item['quantity'] );
				$itemAmt  = $this->wpc_round( $cart_item['line_subtotal'] / $qty, 2 );
				$lineItem = array(
					'NAME'    => html_entity_decode( wc_trim_string( $product->get_title() ? $product->get_title() : __( 'Item', 'woocommerce' ), 127 ), ENT_NOQUOTES, 'UTF-8' ),
					'DESC'    => '',
					'AMT'     => $itemAmt,
					'QTY'     => $qty,
					'ITEMURL' => $product->get_permalink(),
				);

				foreach ( $lineItem as $lineikey => $lineival ) {
					$this->para["L_PAYMENTREQUEST_0_{$lineikey}{$i}"] = $lineival;
				}
				$i          = $i + 1;
				$roundTotal += $this->wpc_round( $itemAmt * $qty );
			}
			if ( WC()->cart->get_cart_discount_total() > 0 ) {

				$discountArr = array(
					'NAME' => __( 'Total Discount', WPCPPEC_SLUG ),
					'QTY'  => 1,
					'AMT'  => - $this->wpc_round( WC()->cart->get_cart_discount_total() ),
				);
				foreach ( $discountArr as $diskey => $disval ) {
					$this->para["L_PAYMENTREQUEST_0_{$diskey}{$i}"] = $disval;
				}
				$i          = $i + 1;
				$roundTotal -= $this->wpc_round( WC()->cart->get_cart_discount_total() );
			}

			foreach ( WC()->cart->get_fees() as $fee ) {

				$feeArr = array(
					'NAME' => __( 'Fee', WPCPPEC_SLUG ),
					'DESC' => $this->wpc_liimit_str( $fee->name ),
					'AMT'  => $this->wpc_round( $fee->amount ),
					'QTY'  => 1,
				);
				foreach ( $feeArr as $feeArrkey => $feeArrval ) {
					$this->para["L_PAYMENTREQUEST_0_{$feeArrkey}{$i}"] = $feeArrval;
				}
				$i          = $i + 1;
				$roundTotal += $this->wpc_round( $fee->amount );
			}
			$totalAmt   = $this->wpc_round( WC()->cart->total );
			$roundTotal += $this->wpc_round( WC()->cart->shipping_total ) + wc_round_tax_total( WC()->cart->tax_total + WC()->cart->shipping_tax_total );
			if ( $totalAmt !== $roundTotal ) {
				$roundArr = array(
					'NAME' => __( 'PayPal Rounding Adjustment', WPCPPEC_SLUG ),
					'AMT'  => - ( $roundTotal - $totalAmt ),
					'QTY'  => 1,
				);
				// $k = $k++;

				foreach ( $roundArr as $roundkey => $roundval ) {
					$this->para["L_PAYMENTREQUEST_0_{$roundkey}{$i}"] = $roundval;
				}
				$i = $i + 1;
			}
			$cartArr = array(
				'AMT'           => $this->wpc_round( WC()->cart->total ),
				'CURRENCYCODE'  => get_woocommerce_currency(),
				'ITEMAMT'       => $this->wpc_round( WC()->cart->cart_contents_total + WC()->cart->fee_total ),
				'SHIPPINGAMT'   => $this->wpc_round( WC()->cart->shipping_total ),
				'TAXAMT'        => wc_round_tax_total( WC()->cart->tax_total + WC()->cart->shipping_tax_total ),
				'NOTIFYURL'     => isset( $args['notifyurl'] ) ? $args['notifyurl'] : '',
				'PAYMENTACTION' => ( $args['payment_action'] == 'Authorization' ) ? 'Authorization' : 'Sale',
			);

			foreach ( $cartArr as $keyCart => $valCart ) {
				$this->para["PAYMENTREQUEST_0_{$keyCart}"] = $valCart;
			}
		}

		$this->para["MAXAMT"] = ceil( WC()->cart->total + ( WC()->cart->total * .5 ) );
		if ( is_user_logged_in() ) {
			$customer_id      = get_current_user_id();
			$customerFullName = get_user_meta( $customer_id, 'shipping_first_name', true ) . ' ' . get_user_meta( $customer_id, 'shipping_last_name', true );
			$shipptoArr       = array( 'SHIPTONAME' => $customerFullName );
			foreach ( $shipptoArr as $shipptoArrkey => $shipptoArrval ) {
				$this->para["PAYMENTREQUEST_0_{$shipptoArrkey}"] = $shipptoArrval;
			}
		}
		$customerShippingArr = array(
			'SHIPTOSTREET'      => WC()->customer->get_shipping_address(),
			'SHIPTOSTREET2'     => WC()->customer->get_shipping_address_2(),
			'SHIPTOCITY'        => WC()->customer->get_shipping_city(),
			'SHIPTOSTATE'       => WC()->customer->get_shipping_state(),
			'SHIPTOZIP'         => WC()->customer->get_shipping_postcode(),
			'SHIPTOCOUNTRYCODE' => WC()->customer->get_shipping_country(),
			'SHIPTOPHONENUM'    => ( WC()->session->post_data['billing_phone'] ) ? WC()->session->post_data['billing_phone'] : WC()->session->post_data['billing_phone'],
			'NOTETEXT'          => ( WC()->session->post_data['order_comments'] ) ? WC()->session->post_data['order_comments'] : WC()->session->post_data['order_comments'],
		);
		foreach ( $customerShippingArr as $custshipKey => $custshipval ) {
			$this->para["PAYMENTREQUEST_0_{$custshipKey}"] = $custshipval;
		}
	}

	public function wpc_ec_details( $token ) {

		$this->para["METHOD"] = 'GetExpressCheckoutDetails';
		$this->para['TOKEN']  = $token;
	}

	public function wpc_ec_do_payment( $order ) {
		$this->order          = $order;
		$this->para["METHOD"] = 'DoExpressCheckoutPayment';

		$doPara = array(
			'TOKEN'            => WC()->session->wpc_pp_ec['token'],
			'PAYERID'          => ( ! empty( WC()->session->wpc_pp_ec['payer_id'] ) ) ? WC()->session->wpc_pp_ec['payer_id'] : null,
			'RETURNFMFDETAILS' => 1,
		);
		foreach ( $doPara as $doParakey => $doParaval ) {
			$this->para[ $doParakey ] = $doParaval;
		}

		$roundTotal     = 0;
		$order_subtotal = $i = 0;
		$order_items    = array();
		foreach ( $order->get_items() as $cart_item_key => $item ) {
			$product     = $order->get_product_from_item( $item );
			$product_sku = null;
			if ( is_object( $product ) ) {
				$product_sku = $product->get_sku();
			}
			$order_items[]  = array(
				'NAME'   => $this->wpc_liimit_str( ( $product->get_title() ) ),
				'DESC'   => '',
				'AMT'    => $this->wpc_round( $order->get_item_subtotal( $item ) ),
				'QTY'    => ( ! empty( $item['qty'] ) ) ? absint( $item['qty'] ) : 1,
				'NUMBER' => $product_sku,
			);
			$order_subtotal += $item['line_total'];
		}
		foreach ( $order->get_fees() as $fee ) {
			$order_items[]  = array(
				'NAME' => $this->wpc_liimit_str( $fee['name'] ),
				'AMT'  => $this->wpc_round( $fee['line_total'] ),
				'QTY'  => 1,
			);
			$order_subtotal += $fee['line_total'];
		}

		if ( $order->get_total_discount() > 0 ) {
			$order_items[] = array(
				'NAME' => __( 'Total Discount', WPCPPEC_SLUG ),
				'QTY'  => 1,
				'AMT'  => - $this->wpc_round( $order->get_total_discount() ),
			);
		}
		if ( $this->is_tax_enable( $order ) ) {
			$totalAmt   = $this->wpc_round( $order->get_total() );
			$roundTotal += $this->wpc_round( $order_subtotal + $order->get_cart_tax() ) + $this->wpc_round( $order->get_total_shipping() + $order->get_shipping_tax() );
			if ( $totalAmt !== $roundTotal ) {
				$order_subtotal = $order_subtotal - ( $roundTotal - $totalAmt );
			}
			$item_names = array();
			foreach ( $order_items as $item ) {
				$item_names[] = sprintf( '%1$s x %2$s', $item['NAME'], $item['QTY'] );
			}
			$doLineitem = array(
				'NAME' => sprintf( __( '%s - Order', WPCPPEC_SLUG ), get_option( 'blogname' ) ),
				'DESC' => $this->wpc_liimit_str( implode( ', ', $item_names ) ),
				'AMT'  => $this->wpc_round( $order_subtotal + $order->get_cart_tax() ),
				'QTY'  => 1,
			);

			foreach ( $doLineitem as $doLineitemkey => $doLineitemval ) {
				$this->para["L_PAYMENTREQUEST_0_{$doLineitemkey}{0}"] = $doLineitemval;
			}

			$dopayPara = array(
				'AMT'              => $totalAmt,
				'CURRENCYCODE'     => version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_order_currency() : $order->get_currency(),
				'ITEMAMT'          => $this->wpc_round( $order_subtotal + $order->get_cart_tax() ),
				'SHIPPINGAMT'      => $this->wpc_round( $order->get_total_shipping() + $order->get_shipping_tax() ),
				'INVNUM'           => $this->wpc_settings['invoice_id_prefix'] . preg_replace( "/[^0-9,.]/", "", $order->get_order_number() ),
				'PAYMENTACTION'    => $this->wpc_settings['payment_action'],
				'PAYMENTREQUESTID' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id(),
			);

			foreach ( $dopayPara as $dopayParakey => $dopayParaval ) {
				$this->para["PAYMENTREQUEST_0_{$dopayParakey}"] = $dopayParaval;
			}
		} else {
			foreach ( $order_items as $item ) {
				$l = $i ++;
				foreach ( $item as $keyitem => $valitem ) {
					$this->para["L_PAYMENTREQUEST_0_{$keyitem}{$l}"] = $valitem;
				}
				$roundTotal += $this->wpc_round( $item['AMT'] * $item['QTY'] );
			}
			$roundTotal += $this->wpc_round( $order->get_total_shipping() ) + $this->wpc_round( $order->get_total_tax() );
			$totalAmt   = $this->wpc_round( $order->get_total() );
			$i          = $i ++;
			if ( $totalAmt !== $roundTotal ) {
				$roundArr = array(
					'NAME' => __( 'Rounding Adjustment', WPCPPEC_SLUG ),
					'AMT'  => - ( $roundTotal - $totalAmt ),
					'QTY'  => 1,
				);
				foreach ( $roundArr as $roundArrkey => $roundArrval ) {
					$this->para["L_PAYMENTREQUEST_0_{$roundArrkey}{$i}"] = $roundArrval;
				}
			}

			$paymentPara = array(
				'AMT'              => $totalAmt,
				'CURRENCYCODE'     => version_compare( WC_VERSION, '3.0', '<' ) ? $order->get_order_currency() : $order->get_currency(),
				'ITEMAMT'          => $this->wpc_round( $order_subtotal ),
				'SHIPPINGAMT'      => $this->wpc_round( $order->get_total_shipping() ),
				'TAXAMT'           => $this->wpc_round( $order->get_total_tax() ),
				'INVNUM'           => $this->wpc_settings['invoice_id_prefix'] . preg_replace( "/[^0-9,.]/", "", $order->get_order_number() ),
				'PAYMENTACTION'    => $this->wpc_settings['payment_action'],
				'PAYMENTREQUESTID' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id(),
			);
			foreach ( $paymentPara as $paymentParak => $paymentParav ) {
				$this->para["PAYMENTREQUEST_0_{$paymentParak}"] = $paymentParav;
			}
		}
	}

	public function is_tax_enable( $order = null ) {
		return $is_tax = ( get_option( 'woocommerce_calc_taxes' ) === 'yes' && get_option( 'woocommerce_prices_include_tax' ) === 'yes' );
	}

	public function wpc_http_query() {
		return http_build_query( $this->wpc_unset_blank() );
	}

	public function wpc_unset_blank() {
		$this->para = $this->para;
		foreach ( $this->para as $key => $value ) {
			if ( '' === $value || is_null( $value ) ) {
				unset( $this->para[ $key ] );
			}
		}

		return $this->para;
	}

	public static function check_decimal( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ) ) ) {
			return false;
		}

		return true;
	}

	public static function wpc_round( $amt ) {
		$precision = 2;
		if ( ! self::check_decimal( get_woocommerce_currency() ) ) {
			$precision = 0;
		}

		return round( (float) $amt, $precision );
	}

	public function wpc_liimit_str( $str ) {
		if ( strlen( $str ) > 127 ) {
			$str = substr( $str, 0, 124 ) . '...';
		}

		return html_entity_decode( $str, ENT_NOQUOTES, 'UTF-8' );
	}

	public function is_not_send_itme() {
		$item_names = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product      = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$qty          = absint( $cart_item['quantity'] );
			$item_names[] = sprintf( '%1$s x %2$s', $product->get_title(), $qty );
		}
		foreach ( WC()->cart->get_fees() as $fee ) {
			$item_names[] = sprintf( __( 'Cart Fee - %s', WPCPPEC_SLUG ), $fee->name );
		}
		$lineAmt     = $this->wpc_round( WC()->cart->cart_contents_total + WC()->cart->fee_total );
		$shippingAmt = $this->wpc_round( WC()->cart->shipping_total + WC()->cart->shipping_tax_total );
		$taxAmt      = $this->wpc_round( WC()->cart->tax_total );
		$totalAmt    = $this->wpc_round( WC()->cart->total );
		$roundTotal  = $this->wpc_round( $lineAmt ) + $this->wpc_round( $shippingAmt ) + $this->wpc_round( $taxAmt );
		if ( $totalAmt !== $roundTotal ) {
			$lineAmt = $lineAmt - ( $roundTotal - $totalAmt );
		}
		$lineitemArr = array(
			'NAME' => sprintf( __( '%s - Order', WPCPPEC_SLUG ), get_option( 'blogname' ) ),
			'DESC' => $this->wpc_liimit_str( html_entity_decode( implode( ', ', $item_names ), ENT_QUOTES, 'UTF-8' ) ),
			'AMT'  => $lineAmt,
		);

		foreach ( $lineitemArr as $linekey => $lineval ) {
			$this->para["L_PAYMENTREQUEST_0_{$linekey}{0}"] = $lineval;
		}
		$paymentArr = array(
			'AMT'           => $totalAmt,
			'CURRENCYCODE'  => get_woocommerce_currency(),
			'ITEMAMT'       => $lineAmt,
			'SHIPPINGAMT'   => $shippingAmt,
			'TAXAMT'        => $taxAmt,
			'NOTIFYURL'     => $args['notifyurl'],
			'PAYMENTACTION' => ( $args['payment_action'] == 'Authorization' ) ? 'Authorization' : 'Sale',
		);

		foreach ( $paymentArr as $paykey => $payval ) {
			$this->para["PAYMENTREQUEST_0_{$paykey}"] = $payval;
		}
	}

}