<?php
/**
 * Uninstall plugin
 */

// If uninstall not called from WordPress, then exit
defined( 'WP_UNINSTALL_PLUGIN' ) or die( 'Keep Silent' );

$rtwpvg_options = get_option( 'rtwpvg', array() );
if ( ! empty( $rtwpvg_options ) && isset( $rtwpvg_options['remove_all_data'] ) && $rtwpvg_options['remove_all_data'] ) {
	delete_option( 'rtwpvg' );
	// Remove Option
	delete_option( 'rtwpvg_pro_activate' );
	// Variation gallery migration state.
	delete_option( 'rtwpvg_native_gallery_migration_completed_at' );
	delete_transient( 'rtwpvg_gallery_migration_scheduled' );
	delete_post_meta_by_key( '_rtwpvg_gallery_migration_attempts' );
	// Site options in Multisite
	delete_site_option( 'rtwpvg_pro_activate' );
}