;
(function ($, window, document) {
    if (wpc_in_content_param.is_product == 'no') {
        var target_url = '';
        window.paypalCheckoutReady = function () {
            setInterval(function () {
                $('.woocommerce').unblock();
            }, 3000);
            ['.paypal_checkout_button_incon', '.paypal_checkout_button_cc_bottom_incon'].forEach(function (selector) {
                paypal.checkout.setup(
                        wpc_in_content_param.payer_id,
                        {
                            environment: wpc_in_content_param.environment,
                            button: selector,
                            locale: wpc_in_content_param.locale,
                            click: function (event) {
                                event.preventDefault();
                                console.log($(event.target).parent().attr("href"));
                                paypal.checkout.initXO();
                                target_url = $(event.target).parent().attr("href");
                                paypal.checkout.startFlow(target_url);
                            }
                        }
                );
            });
            paypal.Button.render({
                env: wpc_in_content_param.environment,
                style: {label: 'checkout',
                    size: wpc_in_content_param.size,
                    shape: wpc_in_content_param.shap,
                    color: wpc_in_content_param.color,
                },
                locale: wpc_in_content_param.locale,
                payment: function () {

                },
                onAuthorize: function (data, actions) {

                },
                onCancel: function (data, actions) {
                    $('.cart').unblock();
                    return actions.redirect();
                },
                onClick: function () {
                    event.preventDefault();
                    paypal.checkout.initXO();
                    $('.cart').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                    var get_attributes = function () {
                        var select = $('.variations_form').find('.variations select'),
                                data = {},
                                count = 0,
                                chosen = 0;

                        select.each(function () {
                            var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                            var value = $(this).val() || '';

                            if (value.length > 0) {
                                chosen++;
                            }

                            count++;
                            data[ attribute_name ] = value;
                        });
                        return {
                            'count': count,
                            'chosenCount': chosen,
                            'data': data
                        };
                    };

                    var data = {
                        'nonce': wpc_in_content_param.generate_cart_nonce,
                        'qty': $('.quantity .qty').val(),
                        'attributes': $('.variations_form').length ? get_attributes().data : [],
                        'is_cc': $(event.target).hasClass('paypal_checkout_button_cc_bottom')
                    };

                    $.ajax({
                        type: 'POST',
                        data: data,
                        url: wpc_in_content_param.add_to_cart_ajaxurl,
                        success: function (data) {
                            $('.cart').unblock();
                            paypal.checkout.startFlow(data.url);
                        },
                        error: function (e) {
                            console.log("Error in ajax post:" + e.statusText);
                            $('.cart').unblock();
                            paypal.checkout.closeFlow();
                        }
                    });
                },
                onError: function (err, actions) {
                    console.log("Error in ajax post:" + err.statusText);
                    $('.cart').unblock();
                    paypal.checkout.closeFlow();
                }
            }, '.paypal_checkout_button_incon', '.paypal_checkout_button_cc_bottom_incon');

        }

    } else {

        window.paypalCheckoutReady = function () {
            paypal.checkout.setup(wpc_in_content_param.payer_id, {
                environment: wpc_in_content_param.environment,
                click: function (event) {
                    event.preventDefault();
                    paypal.checkout.initXO();
                    $('.cart').block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                    var get_attributes = function () {
                        var select = $('.variations_form').find('.variations select'),
                                data = {},
                                count = 0,
                                chosen = 0;

                        select.each(function () {
                            var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                            var value = $(this).val() || '';

                            if (value.length > 0) {
                                chosen++;
                            }

                            count++;
                            data[ attribute_name ] = value;
                        });
                        return {
                            'count': count,
                            'chosenCount': chosen,
                            'data': data
                        };
                    };

                    var data = {
                        'nonce': wpc_in_content_param.generate_cart_nonce,
                        'qty': $('.quantity .qty').val(),
                        'attributes': $('.variations_form').length ? get_attributes().data : [],
                        'is_cc': $(event.target).hasClass('paypal_checkout_button_cc_bottom')
                    };

                    $.ajax({
                        type: 'POST',
                        data: data,
                        url: wpc_in_content_param.add_to_cart_ajaxurl,
                        success: function (data) {
                            $('.cart').unblock();
                            paypal.checkout.startFlow(data.url);
                        },
                        error: function (e) {
                            console.log("Error in ajax post:" + e.statusText);
                            $('.cart').unblock();
                            paypal.checkout.closeFlow();
                        }
                    });
                },
                button: ['.paypal_checkout_button_incon', '.paypal_checkout_button_cc_bottom_incon'],
                condition: function () {
                    if ($('.paypal_checkout_button_incon').hasClass("disabled")) {
                        return false;
                    } else {
                        return true;
                    }
                }
            });
        }
    }
})(jQuery, window, document);