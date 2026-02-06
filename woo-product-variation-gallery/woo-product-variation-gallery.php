<?php

/**
 * Plugin Name:         Variation Images Gallery for WooCommerce
 * Plugin URI:          https://radiustheme.com
 * Description:         Variation Images Gallery for WooCommerce plugin allows to add UNLIMITED additional images for each variation of product.
 * Version:             2.3.22
 * Author:              RadiusTheme
 * Author URI:          https://radiustheme.com
 * Requires at least:   4.8
 * Tested up to:        6.9
 * WC requires at least:3.2
 * WC tested up to:     8.6.1
 * Domain Path:         /languages
 * Text Domain:         woo-product-variation-gallery
 * License:             GPLv3
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or die('Keep Silent');

// Define RTWPVG_PLUGIN_FILE.
if (!defined('RTWPVG_PLUGIN_FILE')) {
  define('RTWPVG_PLUGIN_FILE', __FILE__);
}
define( 'RTWPVG_PLUGIN_PATH', plugin_dir_path(__FILE__) );

// Define RTWPVG_VERSION.
if (!defined('RTWPVG_VERSION')) {
  define('RTWPVG_VERSION', '2.3.22');
}

if (!class_exists('WooProductVariationGallery')) {
  require_once 'app/WooProductVariationGallery.php';
}

// HPOS
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );


