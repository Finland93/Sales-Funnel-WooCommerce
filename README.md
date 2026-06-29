# Sales Funnel WooCommerce

A small WooCommerce add-on that helps optimise the buying flow: a **Buy Now** button for instant checkout, custom **Add to Cart** text, a **small-order fee**, an optional **cart on the checkout page**, and **cart→checkout** redirects.

## Features

- **Buy Now** on shop listings (one click → checkout) and on single products (respects quantity / variation).
- Custom **Add to Cart** button text.
- **Small order fee** applied when the subtotal is below a threshold.
- Show the **cart on the checkout page**.
- **Redirect** the cart page straight to checkout (or to the shop when empty).
- HPOS (High-Performance Order Storage) compatible.

## Installation

1. Make sure **WooCommerce** is installed and active.
2. Upload the `sales-funnel-woocommerce` folder to `/wp-content/plugins/` and activate.
3. Open the **Sales Funnel** admin menu and configure.

## What changed in 2.0.0

- **Fixed the Buy Now flow.** 1.0 called `wp_redirect()` *inside* a `…add_to_cart_url` filter, which is fragile and can redirect at the wrong moment. Buy Now now uses WooCommerce's purpose-built `woocommerce_add_to_cart_redirect` filter, so add-to-cart happens normally and the redirect to checkout is clean. The single-product Buy Now is a submit button inside the form, so it respects the chosen quantity and variation.
- **Settings are now sanitised on save** (1.0 registered the option with no sanitize callback). Amounts are parsed as decimals (comma or dot), the threshold/amount are validated, and the fee is skipped when either is zero.
- Added a **WooCommerce dependency check** with an admin notice, an `is_admin()/AJAX` guard on the fee, escaping throughout, i18n, and a clean uninstall.
- Buy Now only appears on products that can be added directly (variable/grouped products are skipped on the shop loop, since they need options chosen first).

## License

GPLv2 or later — see [LICENSE](LICENSE).

**Author:** [Finland93](https://github.com/Finland93)
