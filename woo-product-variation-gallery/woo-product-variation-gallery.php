<?php
/**
 * Plugin Name:         Variation Images Gallery for WooCommerce
 * Plugin URI:          https://radiustheme.com
 * Description:         Variation Images Gallery for WooCommerce plugin allows to add UNLIMITED additional images for each variation of product.
 * Version:             2.4.1
 * Author:              RadiusTheme
 * Author URI:          https://radiustheme.com
 * Requires at least:   6.0
 * WC requires at least:3.2
 * WC tested up to:     10.9
 * Domain Path:         /languages
 * Text Domain:         woo-product-variation-gallery
 * License:             GPLv3
 * License URI:         http://www.gnu.org/licenses/gpl-3.0.html
 */

use Rtwpvg\Controllers\Install;
use Rtwpvg\WooProductVariationGallery;

defined( 'ABSPATH' ) or die( 'Keep Silent' );

define( 'RTWPVG_VERSION', '2.4.1' );
define( 'RTWPVG_PLUGIN_FILE', __FILE__ );
define( 'RTWPVG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'RTWPVG_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
define( 'RTWPVG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // plugin-slug/plugin-slug.php
define( 'RTWPVG_PLUGIN_DIRNAME', dirname( plugin_basename( __FILE__ ) ) ); // plugin-slug

// Define RTWPVG_VERSION.
if ( ! defined( 'RTWPVG_VERSION' ) ) {
	define( 'RTWPVG_VERSION', '2.4.0' );
}

require_once RTWPVG_PLUGIN_PATH . 'vendor/autoload.php';

// HPOS
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);


/**
 * @return WooProductVariationGallery|null
 */
function rtwpvg() {
	return WooProductVariationGallery::get_instance();
}

register_activation_hook( RTWPVG_PLUGIN_FILE, [ Install::class, 'activated' ] );
register_deactivation_hook( RTWPVG_PLUGIN_FILE, [ Install::class, 'deactivated' ] );

add_action( 'plugins_loaded', 'rtwpvg' );
