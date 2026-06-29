<?php
/**
 * Plugin Name: Sales Funnel WooCommerce
 * Plugin URI: https://github.com/Finland93/sales-funnel-woocommerce
 * Description: Buy Now button, custom Add to Cart text, small-order fee, cart-on-checkout and cart redirects for WooCommerce.
 * Version: 2.0.0
 * Author: Finland93
 * Author URI: https://github.com/Finland93
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sales-funnel-woocommerce
 * WC requires at least: 6.0
 * WC tested up to: 9.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SFW_VERSION', '2.0.0' );
define( 'SFW_FILE', __FILE__ );

// Declare HPOS (High-Performance Order Storage) compatibility — this plugin
// doesn't touch order storage directly, so it's compatible.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SFW_FILE, true );
		}
	}
);

final class Sales_Funnel_WooCommerce {

	const OPTION = 'sales_funnel_woocommerce_options';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( SFW_FILE, array( __CLASS__, 'activate' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( SFW_FILE ) );
			set_transient( 'sfw_wc_missing', 1, 60 );
		}
	}

	public function init() {
		load_plugin_textdomain( 'sales-funnel-woocommerce', false, dirname( plugin_basename( SFW_FILE ) ) . '/languages' );

		// Settings UI is always available so the user can configure the plugin.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'wc_missing_notice' ) );
			return;
		}

		// Small order fee.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_small_fee' ) );

		// Add to cart button text.
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_text' ) );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_text' ) );

		// Buy Now.
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'buy_now_loop_button' ), 15 );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'buy_now_single_button' ) );
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'buy_now_redirect' ) );

		// Cart on checkout + cart redirects.
		add_action( 'woocommerce_before_checkout_form', array( $this, 'add_cart_to_checkout' ), 5 );
		add_action( 'template_redirect', array( $this, 'cart_redirects' ) );
	}

	public function wc_missing_notice() {
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Sales Funnel WooCommerce', 'sales-funnel-woocommerce' ) . '</strong> ' .
			esc_html__( 'requires WooCommerce to be installed and active.', 'sales-funnel-woocommerce' ) . '</p></div>';
	}

	/* ---------------------------------------------------------------------
	 * Option helpers
	 * ------------------------------------------------------------------- */

	private function options() {
		$opts = get_option( self::OPTION );
		return is_array( $opts ) ? $opts : array();
	}

	private function opt( $key, $default = '' ) {
		$opts = $this->options();
		return isset( $opts[ $key ] ) ? $opts[ $key ] : $default;
	}

	private function is_on( $key ) {
		return 'on' === $this->opt( $key );
	}

	/** WC-independent decimal parser (accepts comma decimals). */
	private function to_decimal( $value ) {
		$value = str_replace( ',', '.', (string) $value );
		$value = preg_replace( '/[^0-9.\-]/', '', $value );
		return round( (float) $value, 2 );
	}

	/* ---------------------------------------------------------------------
	 * WooCommerce behaviour
	 * ------------------------------------------------------------------- */

	public function apply_small_fee( $cart ) {
		// Avoid running in the admin (except AJAX cart calculations).
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! $this->is_on( 'enable_small_fee' ) ) {
			return;
		}

		$threshold = $this->to_decimal( $this->opt( 'small_fee_threshold' ) );
		$amount    = $this->to_decimal( $this->opt( 'small_fee_amount' ) );
		if ( $threshold <= 0 || $amount <= 0 ) {
			return;
		}

		$name = $this->opt( 'small_fee_name' );
		if ( '' === $name ) {
			$name = __( 'Small order fee', 'sales-funnel-woocommerce' );
		}

		$subtotal = (float) WC()->cart->subtotal;
		if ( $subtotal > 0 && $subtotal < $threshold ) {
			$cart->add_fee( $name, $amount );
		}
	}

	public function change_add_to_cart_text( $text ) {
		$custom = $this->opt( 'add_to_cart_text' );
		return '' !== $custom ? $custom : $text;
	}

	private function buy_now_label() {
		$text = $this->opt( 'buy_now_text' );
		return '' !== $text ? $text : __( 'Buy Now', 'sales-funnel-woocommerce' );
	}

	/**
	 * Shop-loop Buy Now button. Uses a plain add-to-cart GET URL with a
	 * buy_now flag; the redirect to checkout is handled correctly by the
	 * woocommerce_add_to_cart_redirect filter below (never inside a URL filter).
	 */
	public function buy_now_loop_button() {
		global $product;
		if ( ! $this->is_on( 'enable_buy_now' ) || ! $product instanceof WC_Product ) {
			return;
		}
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		$add_url = $product->add_to_cart_url();
		// Only products that can be added directly (e.g. simple) get a Buy Now
		// button; variable/grouped products need their options chosen first.
		if ( false === strpos( $add_url, 'add-to-cart=' ) ) {
			return;
		}

		$margin = absint( $this->opt( 'buy_now_margin_bottom' ) );
		$url    = add_query_arg( 'buy_now', '1', $add_url );

		printf(
			'<a href="%1$s" class="button alt sfw-buy-now" style="margin-bottom:%2$dpx;">%3$s</a>',
			esc_url( $url ),
			$margin,
			esc_html( $this->buy_now_label() )
		);
	}

	/**
	 * Single-product Buy Now button. Rendered as a submit button INSIDE the
	 * add-to-cart form, so it respects the chosen quantity / variation. On
	 * submit it carries buy_now=1, which the redirect filter sends to checkout.
	 */
	public function buy_now_single_button() {
		global $product;
		if ( ! $this->is_on( 'enable_buy_now' ) || ! $product instanceof WC_Product ) {
			return;
		}
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		printf(
			'<button type="submit" name="buy_now" value="1" class="button alt sfw-buy-now-single" style="margin-top:10px;display:block;">%s</button>',
			esc_html( $this->buy_now_label() )
		);
	}

	public function buy_now_redirect( $url ) {
		if ( $this->is_on( 'enable_buy_now' ) && ! empty( $_REQUEST['buy_now'] ) && function_exists( 'wc_get_checkout_url' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return wc_get_checkout_url();
		}
		return $url;
	}

	public function add_cart_to_checkout() {
		if ( ! $this->is_on( 'add_cart_to_checkout_page' ) ) {
			return;
		}
		echo do_shortcode( '[woocommerce_cart]' );
	}

	public function cart_redirects() {
		if ( ! $this->is_on( 'cart_redirection_to_checkout' ) ) {
			return;
		}
		if ( ! function_exists( 'is_cart' ) || ! is_cart() || ! WC()->cart ) {
			return;
		}

		if ( WC()->cart->is_empty() ) {
			wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
		} else {
			wp_safe_redirect( wc_get_checkout_url() );
		}
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------- */

	public function admin_menu() {
		add_menu_page(
			__( 'Sales Funnel', 'sales-funnel-woocommerce' ),
			__( 'Sales Funnel', 'sales-funnel-woocommerce' ),
			'manage_options',
			'sales-funnel-woocommerce',
			array( $this, 'render_settings_page' ),
			'dashicons-chart-area',
			98
		);
	}

	public function register_settings() {
		register_setting(
			'sales_funnel_woocommerce_options',
			self::OPTION,
			array( 'sanitize_callback' => array( $this, 'sanitize_options' ) )
		);

		add_settings_section( 'sfw_fee', __( 'Small Order Fee', 'sales-funnel-woocommerce' ), '__return_false', 'sales-funnel-woocommerce' );
		$this->add_field( 'enable_small_fee', __( 'Enable small order fee', 'sales-funnel-woocommerce' ), 'checkbox', 'sfw_fee' );
		$this->add_field( 'small_fee_name', __( 'Fee name', 'sales-funnel-woocommerce' ), 'text', 'sfw_fee' );
		$this->add_field( 'small_fee_amount', __( 'Fee amount', 'sales-funnel-woocommerce' ), 'text', 'sfw_fee' );
		$this->add_field( 'small_fee_threshold', __( 'Apply when subtotal is under', 'sales-funnel-woocommerce' ), 'text', 'sfw_fee' );

		add_settings_section( 'sfw_buttons', __( 'Buttons', 'sales-funnel-woocommerce' ), '__return_false', 'sales-funnel-woocommerce' );
		$this->add_field( 'add_to_cart_text', __( 'Add to Cart button text', 'sales-funnel-woocommerce' ), 'text', 'sfw_buttons' );
		$this->add_field( 'enable_buy_now', __( 'Enable Buy Now button', 'sales-funnel-woocommerce' ), 'checkbox', 'sfw_buttons' );
		$this->add_field( 'buy_now_text', __( 'Buy Now button text', 'sales-funnel-woocommerce' ), 'text', 'sfw_buttons' );
		$this->add_field( 'buy_now_margin_bottom', __( 'Buy Now margin bottom (px, shop loop)', 'sales-funnel-woocommerce' ), 'number', 'sfw_buttons' );

		add_settings_section( 'sfw_checkout', __( 'Checkout', 'sales-funnel-woocommerce' ), '__return_false', 'sales-funnel-woocommerce' );
		$this->add_field( 'add_cart_to_checkout_page', __( 'Show cart on the checkout page', 'sales-funnel-woocommerce' ), 'checkbox', 'sfw_checkout' );
		$this->add_field( 'cart_redirection_to_checkout', __( 'Redirect cart page to checkout', 'sales-funnel-woocommerce' ), 'checkbox', 'sfw_checkout' );
	}

	private function add_field( $name, $label, $type, $section ) {
		add_settings_field(
			$name,
			$label,
			array( $this, 'render_field' ),
			'sales-funnel-woocommerce',
			$section,
			array(
				'name' => $name,
				'type' => $type,
			)
		);
	}

	public function render_field( $args ) {
		$name  = $args['name'];
		$type  = $args['type'];
		$value = $this->opt( $name );
		$field = self::OPTION . '[' . $name . ']';

		if ( 'checkbox' === $type ) {
			printf(
				'<input type="checkbox" name="%1$s" %2$s />',
				esc_attr( $field ),
				checked( 'on', $value, false )
			);
		} elseif ( 'number' === $type ) {
			printf(
				'<input type="number" min="0" step="1" name="%1$s" value="%2$s" class="small-text" />',
				esc_attr( $field ),
				esc_attr( $value )
			);
		} else {
			printf(
				'<input type="text" name="%1$s" value="%2$s" class="regular-text" />',
				esc_attr( $field ),
				esc_attr( $value )
			);
		}
	}

	public function sanitize_options( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		foreach ( array( 'enable_small_fee', 'enable_buy_now', 'add_cart_to_checkout_page', 'cart_redirection_to_checkout' ) as $cb ) {
			$out[ $cb ] = ( isset( $input[ $cb ] ) && 'on' === $input[ $cb ] ) ? 'on' : '';
		}

		$out['small_fee_name']        = isset( $input['small_fee_name'] ) ? sanitize_text_field( $input['small_fee_name'] ) : '';
		$out['small_fee_amount']      = isset( $input['small_fee_amount'] ) ? $this->to_decimal( $input['small_fee_amount'] ) : 0;
		$out['small_fee_threshold']   = isset( $input['small_fee_threshold'] ) ? $this->to_decimal( $input['small_fee_threshold'] ) : 0;
		$out['add_to_cart_text']      = isset( $input['add_to_cart_text'] ) ? sanitize_text_field( $input['add_to_cart_text'] ) : '';
		$out['buy_now_text']          = isset( $input['buy_now_text'] ) ? sanitize_text_field( $input['buy_now_text'] ) : '';
		$out['buy_now_margin_bottom'] = isset( $input['buy_now_margin_bottom'] ) ? absint( $input['buy_now_margin_bottom'] ) : 0;

		return $out;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sales Funnel', 'sales-funnel-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Buy Now button, custom Add to Cart text, small-order fee and checkout tweaks.', 'sales-funnel-woocommerce' ); ?></p>

			<div style="display:flex;gap:30px;flex-wrap:wrap;">
				<div style="flex:1 1 420px;">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'sales_funnel_woocommerce_options' );
						do_settings_sections( 'sales-funnel-woocommerce' );
						submit_button();
						?>
					</form>
				</div>
				<div style="flex:0 1 280px;">
					<h2><?php esc_html_e( 'Recommended plugins', 'sales-funnel-woocommerce' ); ?></h2>
					<ul>
						<li><a href="https://wordpress.org/plugins/yith-woocommerce-quick-view/" target="_blank" rel="noopener">YITH Quick View</a></li>
						<li><a href="https://wordpress.org/plugins/side-cart-woocommerce/" target="_blank" rel="noopener">Side Cart WooCommerce</a></li>
						<li><a href="https://wordpress.org/plugins/woo-cart-abandonment-recovery/" target="_blank" rel="noopener">Cart Abandonment Recovery</a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
}

Sales_Funnel_WooCommerce::instance();
