=== Sales Funnel WooCommerce ===
Contributors: Finland93
Tags: woocommerce, buy now, checkout, fee, conversion
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Buy Now button, custom Add to Cart text, small-order fee and checkout tweaks for WooCommerce.

== Changelog ==

= 2.0.0 =
* Fixed: Buy Now now uses the woocommerce_add_to_cart_redirect filter instead of redirecting inside a URL filter; single-product Buy Now respects quantity/variation.
* Fixed: options are sanitised on save; fee amounts parsed as decimals and validated.
* Added: WooCommerce dependency check, admin/AJAX fee guard, escaping, i18n, HPOS compatibility, clean uninstall.

= 1.0 =
* Initial release.
