<?php

if (!defined('ABSPATH')) {
    exit;
}

function wpc_paypal_express_checkout_setting_field() {
    return array(
        'enabled' => array(
            'title' => __('Enable/Disable', WPCPPEC_SLUG),
            'label' => __('Enable PayPal Express', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no'
        ),
        'title' => array(
            'title' => __('Title', WPCPPEC_SLUG),
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', WPCPPEC_SLUG),
            'default' => __('PayPal Express Checkout', WPCPPEC_SLUG)
        ),
        'description' => array(
            'title' => __('Description', WPCPPEC_SLUG),
            'type' => 'textarea',
            'description' => __('This controls the description which the user sees during checkout.', WPCPPEC_SLUG),
            'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", WPCPPEC_SLUG)
        ),
        'api_credentials' => array(
            'title' => __('API Credentials', WPCPPEC_SLUG),
            'type' => 'title',
        ),
        'testmode' => array(
            'title' => __('PayPal Sandbox', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'label' => __('Enable PayPal Sandbox', WPCPPEC_SLUG),
            'default' => 'yes',
            'description' => __(''), WPCPPEC_SLUG
        ),
        'sandbox_api_username' => array(
            'title' => __('Sandbox API Username', WPCPPEC_SLUG),
            'type' => 'text',
            'description' => __('', WPCPPEC_SLUG),
            'default' => ''
        ),
        'sandbox_api_password' => array(
            'title' => __('Sandbox API Password', WPCPPEC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'sandbox_api_signature' => array(
            'title' => __('Sandbox API Signature', WPCPPEC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'api_username' => array(
            'title' => __('Live API Username', WPCPPEC_SLUG),
            'type' => 'text',
            'description' => __('', WPCPPEC_SLUG),
            'default' => ''
        ),
        'api_password' => array(
            'title' => __('Live API Password', WPCPPEC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'api_signature' => array(
            'title' => __('Live API Signature', WPCPPEC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'display_options' => array(
            'title' => __('Display Options', WPCPPEC_SLUG),
            'type' => 'title',
        ),
        'show_on_cart_page' => array(
            'title' => __('Cart Page', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'label' => __('Show express checkout button on cart page', WPCPPEC_SLUG),
            'default' => 'no',
        ),
        'show_on_checkout' => array(
            'title' => __('Standard checkout', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'label' => __('Show express checkout button on checkout page', WPCPPEC_SLUG),
            'default' => 'no',
        ),
        'show_on_product_page' => array(
            'title' => __('Product Page', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'label' => __('Show the Express Checkout button on product detail pages.', WPCPPEC_SLUG),
            'default' => 'no',
            'description' => __(''), WPCPPEC_SLUG
        ),
        'invoice_id_prefix' => array(
            'title' => __('Invoice ID Prefix', WPCPPEC_SLUG),
            'type' => 'text',
            'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', WPCPPEC_SLUG),
        ),
        'paypal_account_optional' => array(
            'title' => __('PayPal Account Optional', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'label' => __('Allow customers to checkout without a PayPal account using their credit card.', WPCPPEC_SLUG),
            'default' => 'no',
            'description' => __('PayPal Account Optional must be turned on in your PayPal account profile under Website Preferences.', WPCPPEC_SLUG)
        ),
        'landing_page' => array(
            'title' => __('Landing Page', WPCPPEC_SLUG),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'description' => __('Type of PayPal page to display as default. PayPal Account Optional must be checked for this option to be used.', WPCPPEC_SLUG),
            'options' => array('login' => __('Login', WPCPPEC_SLUG),
                'billing' => __('Billing', WPCPPEC_SLUG)),
            'default' => 'login',
        ),
        'send_items' => array(
            'title' => __('Send Item Details', WPCPPEC_SLUG),
            'label' => __('Send line item details to PayPal.', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'description' => __('Include all line item details in the payment request to PayPal so that they can be seen from the PayPal transaction details page.', WPCPPEC_SLUG),
            'default' => 'yes'
        ),
        'error_display_type' => array(
            'title' => __('Error Display Type', WPCPPEC_SLUG),
            'type' => 'select',
            'label' => __('Display detailed or generic errors', WPCPPEC_SLUG),
            'class' => 'error_display_type_option, wc-enhanced-select',
            'options' => array(
                'detailed' => __('Detailed', WPCPPEC_SLUG),
                'generic' => __('Generic', WPCPPEC_SLUG)
            ),
            'description' => __('Detailed displays actual errors returned from PayPal.  Generic displays general errors that do not reveal details
									and helps to prevent fraudulant activity on your site.', WPCPPEC_SLUG)
        ),
        'show_paypal_credit' => array(
            'title' => __('Enable PayPal Credit', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'label' => __('Show the PayPal Credit button next to the Express Checkout button.', WPCPPEC_SLUG),
            'default' => 'yes',
        ),
        'enable_in_context_checkout_flow' => array(
            'title' => __('Enable In-Context Checkout flow', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'label' => __('The enhanced PayPal Express Checkout with In-Context gives your customers a simplified checkout experience that keeps them local to your website throughout the payment authorization process and enables them to use their PayPal balance, bank account, or credit card to pay without sharing or entering any sensitive information on your site.', 'paypal-for-woocommerce'),
            'default' => 'no'
        ),
        'brand_name' => array(
            'title' => __('Brand Name', WPCPPEC_SLUG),
            'type' => 'text',
            'description' => __('This controls what users see as the brand / company name on PayPal review pages.', WPCPPEC_SLUG),
            'default' => __(get_bloginfo('name'), WPCPPEC_SLUG)
        ),
        'checkout_logo' => array(
            'title' => __('PayPal Checkout Logo (190x90px)', WPCPPEC_SLUG),
            'type' => 'text',
            'default' => ''
        ),
        'skip_text' => array(
            'title' => __('Express Checkout Message', WPCPPEC_SLUG),
            'type' => 'text',
            'description' => __('This message will be displayed next to the PayPal Express Checkout button at the top of the checkout page.'),
            'default' => __('Skip the forms and pay faster with PayPal!', WPCPPEC_SLUG)
        ),
        'button_styles' => array(
            'title' => __('PayPal Smart Button Style Customization', WPCPPEC_SLUG),
            'type' => 'title',
            'description' => 'Customize your PayPal button with colors, sizes and shapes.',
        ),
        'button_size' => array(
            'title' => __('Button Size', WPCPPEC_SLUG),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'description' => __('Type of PayPal Button Size (small | medium | responsive).', WPCPPEC_SLUG),
            'default' => 'small',
            'desc_tip' => true,
            'options' => array(
                'small' => __('Small', WPCPPEC_SLUG),
                'medium' => __('Medium', WPCPPEC_SLUG),
                'responsive' => __('Responsive', WPCPPEC_SLUG),
            ),
        ),
        'button_shape' => array(
            'title' => __('Button Shape', WPCPPEC_SLUG),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'description' => __('Type of PayPal Button Shape (pill | rect).', WPCPPEC_SLUG),
            'default' => 'pill',
            'desc_tip' => true,
            'options' => array(
                'pill' => __('Pill', WPCPPEC_SLUG),
                'rect' => __('Rect', WPCPPEC_SLUG)
            ),
        ),
        'button_color' => array(
            'title' => __('Button Color', WPCPPEC_SLUG),
            'type' => 'select',
            'class' => 'wc-enhanced-select',
            'description' => __('Type of PayPal Button Color (gold | blue | silver).', WPCPPEC_SLUG),
            'default' => 'gold',
            'desc_tip' => true,
            'options' => array(
                'gold' => __('Gold', WPCPPEC_SLUG),
                'blue' => __('Blue', WPCPPEC_SLUG),
                'silver' => __('Silver', WPCPPEC_SLUG)
            ),
        ),
        'payment_action' => array(
            'title' => __('Payment Action', WPCPPEC_SLUG),
            'label' => __('Whether to process as a Sale or Authorization.', WPCPPEC_SLUG),
            'class' => 'wc-enhanced-select',
            'description' => __('Sale will capture the funds immediately when the order is placed.  Authorization will authorize the payment but will not capture the funds.  You would need to capture funds through your PayPal account when you are ready to deliver.'),
            'type' => 'select',
            'options' => array(
                'Sale' => 'Sale',
                'Authorization' => 'Authorization'
            ),
            'default' => 'Sale'
        ),
        'skip_review_order' => array(
            'title' => __('Skip Final Review', WPCPPEC_SLUG),
            'label' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details. Enable this option to eliminate this page in the checkout process.', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'description' => __('', WPCPPEC_SLUG),
            'default' => 'no'
        ),
        'debug' => array(
            'title' => __('Debug Log', WPCPPEC_SLUG),
            'label' => __('Enable logging', WPCPPEC_SLUG),
            'type' => 'checkbox',
            'description' => sprintf(__('Log PayPal events, inside <code>%s</code>', WPCPPEC_SLUG), wc_get_log_file_path('wpc_ec')),
            'default' => 'no'
        ),
    );
}
