<?php

namespace Rtwpvg\Controllers;


class Install {

	static function activated() {
		update_option( 'rtwpvg_pro_active', 'yes' );
		if ( ! get_option( 'RTWPVG_VERSION' ) ) {
			// acc some options
		}

		// Update version
		delete_option( 'RTWPVG_VERSION' );
		add_option( 'RTWPVG_VERSION', rtwpvg()->version() );

		if ( version_compare(RTWPVG_VERSION, '2.1.13', '<=') ) {
			$old_value = get_option('rtwpvg'); 
			if ( $old_value ) {
				$old_value['preloader'] = true;
				update_option('rtwpvg', $old_value);
			}
		}
		
	}

	static function deactivated() {
		delete_option( 'rtwpvg_pro_active' );
	}

}