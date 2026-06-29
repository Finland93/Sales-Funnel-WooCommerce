<?php
/**
 * Remove plugin options on uninstall.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'sales_funnel_woocommerce_options' );
delete_transient( 'sfw_wc_missing' );
