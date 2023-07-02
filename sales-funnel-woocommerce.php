<?php
/**
 * Plugin Name: Sales Funnel WooCommerce
 * Plugin URI: https://github.com/Finland93/sales-funnel-woocommerce
 * Description: Add buy now button, edit add to cart text, add small order fees, and more WooCommerce customizations.
 * Version: 1.0
 * Author: Finland93
 * Author URI: https://github.com/Finland93
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Add menu page
function sales_funnel_woocommerce_menu_page() {
    add_menu_page(
        'Sales Funnel',
        'Sales Funnel',
        'manage_options',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_page_callback',
        'dashicons-chart-area',
        98
    );
}
add_action('admin_menu', 'sales_funnel_woocommerce_menu_page');

// Menu page callback function
function sales_funnel_woocommerce_page_callback() {
    // Check if the form was submitted and the settings were saved
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
        // Display the notice
        sales_funnel_woocommerce_settings_saved_notice();
    }

    echo '<div class="wrap">';
    echo '<h1>Sales Funnel</h1>';
    echo '<p>Add buy now button, edit add to cart text, add small order fees, and more WooCommerce customizations.</p>';

    // Display the options form
    echo '<form method="post" action="options.php">';
    settings_fields('sales_funnel_woocommerce_options');
    do_settings_sections('sales-funnel-woocommerce');
    submit_button();
    echo '</form>';

    echo '</div>';
}


// Register plugin options
function sales_funnel_woocommerce_register_options() {
    add_settings_section(
        'sales_funnel_woocommerce_settings_section',
        'Small Order Fee Settings',
        '',
        'sales-funnel-woocommerce'
    );

    add_settings_field(
        'enable_small_fee',
        'Enable Small Order Fee',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_settings_section',
        [
            'type' => 'checkbox',
            'name' => 'enable_small_fee',
        ]
    );

    add_settings_field(
        'small_fee_name',
        'Small Fee Name',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_settings_section',
        [
            'type' => 'text',
            'name' => 'small_fee_name',
        ]
    );

    add_settings_field(
        'small_fee_amount',
        'Small Fee Amount',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_settings_section',
        [
            'type' => 'text',
            'name' => 'small_fee_amount',
        ]
    );

    add_settings_field(
        'small_fee_threshold',
        'Small Fee for Sum Under',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_settings_section',
        [
            'type' => 'text',
            'name' => 'small_fee_threshold',
        ]
    );

    add_settings_section(
        'sales_funnel_woocommerce_button_text_section',
        'Button Text Settings',
        '',
        'sales-funnel-woocommerce'
    );

    add_settings_field(
        'add_to_cart_text',
        'Add to Cart Button Text',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_button_text_section',
        [
            'type' => 'text',
            'name' => 'add_to_cart_text',
        ]
    );

    add_settings_field(
        'enable_buy_now',
        'Enable Buy Now',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_button_text_section',
        [
            'type' => 'checkbox',
            'name' => 'enable_buy_now',
        ]
    );

    add_settings_field(
        'buy_now_text',
        'Buy Now Button Text',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_button_text_section',
        [
            'type' => 'text',
            'name' => 'buy_now_text',
        ]
    );

    add_settings_field(
        'buy_now_margin_bottom',
        'Buy Now Button Margin Bottom',
        'sales_funnel_woocommerce_field_callback',
        'sales-funnel-woocommerce',
        'sales_funnel_woocommerce_button_text_section',
        [
            'type' => 'number',
            'name' => 'buy_now_margin_bottom',
        ]
    );

	add_settings_section(
		'sales_funnel_woocommerce_checkout_text_section',
		'Edit Checkout Options',
		'',
		'sales-funnel-woocommerce'
	);

	add_settings_field(
		'add_cart_to_checkout_page',
		'Add Cart to Checkout Page',
		'sales_funnel_woocommerce_field_callback',
		'sales-funnel-woocommerce',
		'sales_funnel_woocommerce_checkout_text_section',
		[
			'type' => 'checkbox',
			'name' => 'add_cart_to_checkout_page',
		]
	);

	add_settings_field(
		'cart_redirection_to_checkout',
		'Cart Redirection to Checkout',
		'sales_funnel_woocommerce_field_callback',
		'sales-funnel-woocommerce',
		'sales_funnel_woocommerce_checkout_text_section',
		[
			'type' => 'checkbox',
			'name' => 'cart_redirection_to_checkout',
		]
	);

    register_setting('sales_funnel_woocommerce_options', 'sales_funnel_woocommerce_options');
}
add_action('admin_init', 'sales_funnel_woocommerce_register_options');

// Options field callback
function sales_funnel_woocommerce_field_callback($args) {
    $options = get_option('sales_funnel_woocommerce_options');
    $type = $args['type'];
    $name = $args['name'];

    switch ($type) {
        case 'checkbox':
            $checked = isset($options[$name]) && $options[$name] === 'on';
            echo '<input type="checkbox" name="sales_funnel_woocommerce_options[' . esc_attr($name) . ']" ' . checked($checked, true, false) . ' />';
            break;
        case 'text':
            echo '<input type="text" name="sales_funnel_woocommerce_options[' . esc_attr($name) . ']" value="' . esc_attr($options[$name] ?? '') . '" />';
            break;
        case 'number':
            echo '<input type="number" name="sales_funnel_woocommerce_options[' . esc_attr($name) . ']" value="' . esc_attr($options[$name] ?? '') . '" />';
            break;
    }
}

// Apply small order fee
function sales_funnel_woocommerce_apply_small_fee($cart) {
    $options = get_option('sales_funnel_woocommerce_options');
    $enable_small_fee = isset($options['enable_small_fee']) && $options['enable_small_fee'] === 'on';
    $small_fee_name = $options['small_fee_name'] ?? '';
    $small_fee_amount = $options['small_fee_amount'] ?? '';
    $small_fee_threshold = $options['small_fee_threshold'] ?? '';

    $cart_total = WC()->cart->subtotal;

    if ($enable_small_fee && $cart_total < $small_fee_threshold) {
        $fee = new WC_Cart_Fee($small_fee_name, $small_fee_amount, '');
        $cart->add_fee($fee);
    }
}
add_action('woocommerce_cart_calculate_fees', 'sales_funnel_woocommerce_apply_small_fee');

// Change "Add to Cart" button text
function sales_funnel_woocommerce_change_add_to_cart_text($text) {
    $options = get_option('sales_funnel_woocommerce_options');
    $add_to_cart_text = $options['add_to_cart_text'] ?? '';

    if (!empty($add_to_cart_text)) {
        $text = $add_to_cart_text;
    }

    return $text;
}
add_filter('woocommerce_product_single_add_to_cart_text', 'sales_funnel_woocommerce_change_add_to_cart_text', 10, 1);
add_filter('woocommerce_product_add_to_cart_text', 'sales_funnel_woocommerce_change_add_to_cart_text', 10, 1);

// Redirect "Buy Now" button to checkout
function sales_funnel_woocommerce_redirect_buy_now_to_checkout($url, $product) {
    $options = get_option('sales_funnel_woocommerce_options');
    $enable_buy_now = isset($options['enable_buy_now']) && $options['enable_buy_now'] === 'on';
    $buy_now_text = $options['buy_now_text'] ?? '';

    if ($enable_buy_now && !empty($buy_now_text) && $product && $product->is_purchasable() && $product->is_in_stock() && isset($_GET['buy_now']) && $_GET['buy_now'] == $product->get_id()) {

        if (isset($options['cart_redirection_to_checkout']) && $options['cart_redirection_to_checkout'] === 'on') {
			// Redirect to the checkout page
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }

    return $url;
}

add_filter('woocommerce_product_add_to_cart_url', 'sales_funnel_woocommerce_redirect_buy_now_to_checkout', 10, 2);
add_filter('woocommerce_product_single_add_to_cart_url', 'sales_funnel_woocommerce_redirect_buy_now_to_checkout', 10, 2);


// Add "Buy Now" button to shop listing page

function sales_funnel_woocommerce_add_buy_now_button() {
    global $product;

    $options = get_option('sales_funnel_woocommerce_options');
    $enable_buy_now = isset($options['enable_buy_now']) && $options['enable_buy_now'] === 'on';
    $buy_now_text = $options['buy_now_text'] ?? '';
    $buy_now_margin_bottom = $options['buy_now_margin_bottom'] ?? '';

    if ($enable_buy_now && !empty($buy_now_text) && $product && $product->is_purchasable() && $product->is_in_stock()) {
        $buy_now_url = add_query_arg('buy_now', $product->get_id(), $product->add_to_cart_url());

        echo '<a href="' . esc_url($buy_now_url) . '" class="button alt" style="margin-bottom: ' . esc_attr($buy_now_margin_bottom) . 'px;">' . esc_html($buy_now_text) . '</a>';
    }
}
add_action('woocommerce_after_shop_loop_item', 'sales_funnel_woocommerce_add_buy_now_button', 10);

// Add cart to checkout page
function sales_funnel_woocommerce_add_cart_to_checkout_page() {
    $options = get_option('sales_funnel_woocommerce_options');
    $add_cart_to_checkout_page = isset($options['add_cart_to_checkout_page']) && $options['add_cart_to_checkout_page'] === 'on';

    if ($add_cart_to_checkout_page) {
        echo do_shortcode('[woocommerce_cart]');
    }
}

add_action('woocommerce_before_checkout_form', 'sales_funnel_woocommerce_add_cart_to_checkout_page', 5);

// Redirect cart page to checkout if cart is not empty
add_action('template_redirect', 'sales_funnel_woocommerce_redirect_cart_to_checkout');
function sales_funnel_woocommerce_redirect_cart_to_checkout() {
    $options = get_option('sales_funnel_woocommerce_options');
    $cart_redirection_to_checkout = isset($options['cart_redirection_to_checkout']) && $options['cart_redirection_to_checkout'] === 'on';

    if ($cart_redirection_to_checkout && is_cart() && !WC()->cart->is_empty()) {
        wp_redirect(wc_get_checkout_url());
        exit;
    }
}

// Redirect cart page to shop if cart is empty
add_action('template_redirect', 'sales_funnel_woocommerce_redirect_cart_to_shop');
function sales_funnel_woocommerce_redirect_cart_to_shop() {
    $options = get_option('sales_funnel_woocommerce_options');
    $cart_redirection_to_checkout = isset($options['cart_redirection_to_checkout']) && $options['cart_redirection_to_checkout'] === 'on';

    if ($cart_redirection_to_checkout && is_cart() && WC()->cart->is_empty()) {
        wp_redirect(get_permalink(wc_get_page_id('shop')));
        exit;
    }
}

function sales_funnel_woocommerce_settings_saved_notice() {
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>Settings saved.</p>';
    echo '</div>';
}
