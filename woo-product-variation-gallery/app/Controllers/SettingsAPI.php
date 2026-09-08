<?php

namespace Rtwpvg\Controllers;

use Rtwpvg\Helpers\Options;
use Rtwpvgp\Controllers\Licensing;

class SettingsAPI {

	/**
	 * Slug of the dedicated settings page under the WooCommerce menu.
	 */
	const PAGE_SLUG = 'rtwpvg-settings';

	private $setting_id = 'rtwpvg';
	private $defaults   = [];
	private $sections   = [];

	public function __construct() {
		$this->sections = Options::get_settings_sections();
		add_action( 'init', [ $this, 'set_defaults' ], 8 );
		add_filter(
			'plugin_action_links_' . RTWPVG_PLUGIN_BASENAME,
			[
				$this,
				'plugin_action_links',
			]
		);
		add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
		add_action( 'woocommerce_settings_tabs_' . $this->setting_id, [ $this, 'settings_tab' ] );
		add_action( 'woocommerce_update_options_' . $this->setting_id, [ $this, 'update_settings' ] );
		add_action( 'woocommerce_admin_field_' . $this->setting_id, [ $this, 'global_settings' ] );
		add_action( 'wp_ajax_rtwpvg_save_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'admin_menu', [ $this, 'register_submenu' ], 60 );
		add_action( 'admin_init', [ $this, 'maybe_redirect_legacy_tab' ] );
		add_action( 'in_admin_header', [ $this, 'suppress_admin_notices' ], PHP_INT_MAX );
	}

	/**
	 * Register the "Variation Gallery" submenu under the WooCommerce menu.
	 *
	 * @return void
	 */
	public function register_submenu() {
		add_submenu_page(
			'woocommerce',
			esc_html__( 'Variation Gallery', 'woo-product-variation-gallery' ),
			esc_html__( 'Variation Gallery', 'woo-product-variation-gallery' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Redirect the legacy WooCommerce settings tab (wc-settings&tab=rtwpvg) to
	 * the dedicated submenu page so both entry points land in the same place.
	 *
	 * @return void
	 */
	public function maybe_redirect_legacy_tab() {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing.
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing.

		if ( 'wc-settings' === $page && $this->setting_id === $tab ) {
			// Carry a deep link (…&section=license) over to the hash router.
			$section  = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing.
			$redirect = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
			if ( $section ) {
				$redirect .= '#/' . $section;
			}

			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Keep the dedicated settings screen clean by removing third-party admin
	 * notices (theme license, TGM "required plugin" prompts, etc.) that
	 * WordPress otherwise injects above the settings app.
	 *
	 * @return void
	 */
	public function suppress_admin_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_' . self::PAGE_SLUG !== $screen->id ) {
			return;
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}

	/**
	 * Render the dedicated submenu settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		echo '<div class="rtwpvg-settings-page">';
		$this->render_settings_app();
		echo '</div>';
	}

	public function set_defaults() {
		foreach ( $this->sections as $section ) {
			foreach ( $section['fields'] as $field ) {
				$field['default'] = isset( $field['default'] ) ? $field['default'] : null;
				$this->set_default( $field['id'], $field['type'], $field['default'] );
			}
		}
	}

	private function set_default( $key, $type, $value ) {
		$this->defaults[ $key ] = [
			'id'    => $key,
			'type'  => $type,
			'value' => $value,
		];
	}

	private function get_default( $key ) {
		return isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : null;
	}

	public function get_defaults() {
		return $this->defaults;
	}

	public function plugin_action_links( $links ) {
		$new_links = [
			'<a href="' . admin_url( 'admin.php?page=' . self::PAGE_SLUG ) . '">' . __( 'Settings', 'woo-product-variation-gallery' ) . '</a>',
			'<a target="_blank" href="' . esc_url( 'https://radiustheme.com/demo/wordpress/woopluginspro/product/woocommerce-variation-images-gallery/' ) . '">' . esc_html__( 'Demo', 'woo-product-variation-gallery' ) . '</a>',
			'<a target="_blank" href="' . esc_url( 'https://www.radiustheme.com/docs/variation-gallery/' ) . '">' . esc_html__( 'Documentation', 'woo-product-variation-gallery' ) . '</a>',
		];

		if ( ! function_exists( 'rtwpvgp' ) ) {
			$new_links[] = '<a style="color: #39b54a;font-weight: 700;" target="_blank" href="' . esc_url( 'https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/?utm_source=WordPress&utm_medium=gallery&utm_campaign=pro_click' ) . '">' . esc_html__( 'Get Pro', 'woo-product-variation-gallery' ) . '</a>';
		}

		return array_merge( $links, $new_links );
	}

	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[ $this->setting_id ] = __( 'Variation Gallery', 'woo-product-variation-gallery' );

		return $settings_tabs;
	}

	public function settings_tab() {
		woocommerce_admin_fields( $this->get_settings() );
	}

	public function update_settings() {
		woocommerce_update_options( $this->get_settings() );
		$this->update_licencing_status();
		$this->clear_all_gallery_transients();
	}

	/**
	 * Clear all gallery image transients when settings change.
	 *
	 * @return void
	 */
	private function clear_all_gallery_transients() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rtwpvg_%' OR option_name LIKE '_transient_timeout_rtwpvg_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk transient purge; no cache to invalidate on DELETE.
	}

	private function update_licencing_status() {

		$license_key    = trim( $this->get_option( 'license_key' ) ?? '' );
		$license_status = $this->get_option( 'license_status' );
		$status         = ( ! empty( $license_status ) && $license_status === 'valid' ) ? true : false;
		if ( $license_key && ! $status ) {
			if ( ! class_exists( Licensing::class ) ) {
				return;
			}
			$api_params = [
				'edd_action' => 'activate_license',
				'license'    => $license_key,
				'item_id'    => Licensing::$product_id,
				'url'        => home_url(),
			];
			$response   = wp_remote_post(
				Licensing::$store_url,
				[
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $api_params,
				]
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				if ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) {
					$message = $response->get_error_message();
				} elseif ( isset( $response['response'] ) && is_array( $response['response'] ) && isset( $response['response']['message'] ) ) {
					$message = 'sss' . $response['response']['message'];
				} else {
					$message = esc_html__( 'An error occurred, please try again.', 'woo-product-variation-gallery' );
				}
			} else {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( false === $license_data->success ) {
					switch ( $license_data->error ) {
						case 'expired':
							$message = sprintf(
									/* translators: %s: Expiry date */
								__( 'Your license key expired on %s.', 'woo-product-variation-gallery' ),
								date_i18n(
									get_option( 'date_format' ),
									strtotime( $license_data->expires, current_time( 'timestamp' ) )
								)
							);
							break;
						case 'revoked':
							$message = __( 'Your license key has been disabled.', 'woo-product-variation-gallery' );
							break;
						case 'missing':
							$message = __( 'Invalid license.', 'woo-product-variation-gallery' );
							break;
						case 'invalid':
						case 'site_inactive':
							$message = __( 'Your license is not active for this URL.', 'woo-product-variation-gallery' );
							break;
						case 'item_name_mismatch':
							$message = __( 'This appears to be an invalid license key for Classified Listing Pro.', 'woo-product-variation-gallery' );
							break;
						case 'no_activations_left':
							$message = __( 'Your license key has reached its activation limit.', 'woo-product-variation-gallery' );
							break;
						default:
							$message = __( 'An error occurred, please try again.', 'woo-product-variation-gallery' );
							break;
					}
				}
				// Check if anything passed on a message constituting a failure
				if ( empty( $message ) && $license_data->license === 'valid' ) {
					$this->update_option( 'license_status', $license_data->license );
				} else {
					$this->update_option( 'license_status', '' );
				}
			}
		} elseif ( ! $license_key && ! $status ) {
			$this->update_option( 'license_status', '' );
		}
	}

	public function get_settings() {
		$settings = [
			[
				'name' => 'Variation Images Gallery for WooCommerce Settings',
				'type' => 'title',
				'desc' => '',
				'id'   => 'rtwpvg_settings_section',
			],
			[
				'type' => $this->setting_id,
				'id'   => $this->setting_id,
			],
			'section_end' => [
				'type' => 'sectionend',
				'id'   => 'rtwpvg_settings_section',
			],
		];

		return apply_filters( 'rtwpvg_get_settings', $settings );
	}

	public function get_setting_id() {

		return $this->setting_id;
	}

	public function options_tabs() {
		?>
		<nav class="nav-tab-wrapper wp-clearfix">
			<?php
			foreach ( $this->sections as $tabs ) :
				$active_class = $this->get_options_tab_css_classes( $tabs );
				if ( $this->get_last_active_tab() == 'license' && ! function_exists( 'rtwpvgp' ) && $tabs['id'] == 'general' ) {
					$active_class = 'nav-tab-active';
				}
				?>
				<a data-target="<?php echo esc_attr( $tabs['id'] ); ?>"
				   class="rtwpvg-setting-nav-tab nav-tab <?php echo esc_attr( $active_class ); ?> "
				   href="#<?php echo esc_attr( $tabs['id'] ); ?>"><?php echo esc_html( $tabs['title'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * WooCommerce settings-tab field callback (kept as a fallback in case the
	 * redirect is short-circuited). Hides the WooCommerce save button and
	 * renders the same React app.
	 *
	 * @return void
	 */
	public function global_settings() {
		$GLOBALS['hide_save_button'] = true;
		$this->render_settings_app();
	}

	/**
	 * Print the React mount point, its data and the module bundle.
	 *
	 * The bundle is printed directly (not enqueued) so optimization/security
	 * plugins can't strip it, and loaded as a module because it relies on
	 * `import.meta`. JSON_HEX_TAG keeps any HTML in the data from breaking out
	 * of the inline <script>.
	 *
	 * @return void
	 */
	private function render_settings_app() {
		$data    = $this->get_react_data();
		$version = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : RTWPVG_VERSION;
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$src     = rtwpvg()->get_assets_uri( "js/settings{$suffix}.js" ) . '?ver=' . rawurlencode( (string) $version );
		?>
		<div class="rtwpvg-settings">
			<div id="rtwpvg-settings-root"></div>
		</div>
		<script type="text/javascript">
			window.rtwpvgSettings = <?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;
		</script>
		<script type="module" id="rtwpvg-settings-js" src="<?php echo esc_url( $src ); ?>"></script>
		<?php
	}

	/**
	 * Build the data object consumed by the React settings app.
	 *
	 * @return array
	 */
	public function get_react_data() {
		$is_pro = function_exists( 'rtwpvgp' );

		$values     = [];
		$image_urls = [];
		foreach ( $this->get_saveable_fields() as $field ) {
			$value                  = $this->get_option( $field['id'] );
			$values[ $field['id'] ] = $value;

			if ( 'image' === $field['type'] && $value ) {
				$url = wp_get_attachment_image_url( absint( $value ), 'medium' );
				if ( $url ) {
					$image_urls[ $field['id'] ] = $url;
				}
			}
		}

		return [
			'sections'      => array_values( $this->sections ),
			'values'        => $values,
			'imageUrls'     => $image_urls,
			'isPro'         => $is_pro,
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'rtwpvg_nonce' ),
			'saveAction'    => 'rtwpvg_save_settings',
			'licenseStatus' => (string) $this->get_option( 'license_status' ),
			'licenseNonce'  => wp_create_nonce( 'rtwpvg_manage_licensing' ),
			'licenseAction' => 'rtwpvg_manage_licensing',
			'logoUrl'       => rtwpvg()->get_images_uri( 'icon-128x128.gif' ),
			'proUrl'        => 'https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/?utm_source=WordPress&utm_medium=gallery&utm_campaign=pro_click',
			'docUrl'        => 'https://www.radiustheme.com/docs/variation-gallery/',
			'supportUrl'    => 'https://www.radiustheme.com/contact/',
			'reviewUrl'     => 'https://wordpress.org/support/plugin/woo-product-variation-gallery/reviews/#new-post',
			'version'       => RTWPVG_VERSION,
			'strings'       => [
				'pageTitle'       => esc_html__( 'Variation Gallery', 'woo-product-variation-gallery' ),
				'save'            => esc_html__( 'Save Changes', 'woo-product-variation-gallery' ),
				'saving'          => esc_html__( 'Saving…', 'woo-product-variation-gallery' ),
				'saved'           => esc_html__( 'Changes Saved', 'woo-product-variation-gallery' ),
				/* translators: %s: field label. */
				'requiredField'   => esc_html__( '%s is required.', 'woo-product-variation-gallery' ),
				'requiredError'   => esc_html__( 'Please fill the required fields before saving.', 'woo-product-variation-gallery' ),
				'upgrade'         => esc_html__( 'Upgrade to Pro', 'woo-product-variation-gallery' ),
				'licenseActive'   => esc_html__( 'License Active', 'woo-product-variation-gallery' ),
				'licenseInvalid'  => esc_html__( 'Invalid Licence', 'woo-product-variation-gallery' ),
				'pro'             => esc_html__( 'Pro', 'woo-product-variation-gallery' ),
				'proField'        => esc_html__( 'This is a premium field. Upgrade to Pro to use it.', 'woo-product-variation-gallery' ),
				'cancel'          => esc_html__( 'Cancel', 'woo-product-variation-gallery' ),
				'proAlertTitle'   => esc_html__( 'Pro field alert!', 'woo-product-variation-gallery' ),
				'proAlertMessage' => esc_html__( 'Sorry! this is a pro field. To use this field, you need to use pro plugin.', 'woo-product-variation-gallery' ),
				'selectImage'     => esc_html__( 'Select Image', 'woo-product-variation-gallery' ),
				'changeImage'     => esc_html__( 'Change Image', 'woo-product-variation-gallery' ),
				'removeImage'     => esc_html__( 'Remove', 'woo-product-variation-gallery' ),
			],
		];
	}

	/**
	 * Flat list of persistable fields (excludes layout-only field types).
	 *
	 * @return array
	 */
	private function get_saveable_fields() {
		$skip   = [ 'title', 'feature', 'card' ];
		$fields = [];
		foreach ( $this->sections as $section ) {
			foreach ( $section['fields'] as $field ) {
				if ( empty( $field['id'] ) || in_array( $field['type'], $skip, true ) ) {
					continue;
				}
				$fields[ $field['id'] ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * AJAX handler: persist settings sent by the React app to the `rtwpvg`
	 * option. Unknown keys are ignored and existing keys not present in the
	 * payload are preserved (no data migration).
	 *
	 * @return void
	 */
	public function ajax_save_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'woo-product-variation-gallery' ) ], 403 );
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'rtwpvg_nonce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid or expired request. Please reload the page.', 'woo-product-variation-gallery' ) ], 403 );
		}

		$payload = isset( $_POST['payload'] ) ? json_decode( wp_unslash( $_POST['payload'] ), true ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each value is sanitized per field type below.
		if ( ! is_array( $payload ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid payload.', 'woo-product-variation-gallery' ) ], 400 );
		}

		$is_pro    = function_exists( 'rtwpvgp' );
		$allowed   = $this->get_saveable_fields();
		$sanitized = [];
		foreach ( $payload as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}
			// Never let a free user overwrite Pro-only values.
			if ( ! empty( $allowed[ $key ]['is_pro'] ) && ! $is_pro ) {
				continue;
			}
			$sanitized[ $key ] = $this->sanitize_field_value( $allowed[ $key ], $value );
		}

		$existing = get_option( $this->setting_id );
		$existing = is_array( $existing ) ? $existing : [];
		$merged   = array_merge( $existing, $sanitized );

		// Reject the save when a visible required field is left empty.
		$errors = $this->get_required_errors( $merged, $allowed );
		if ( ! empty( $errors ) ) {
			wp_send_json_error(
				[
					'message' => implode( ' ', $errors ),
					'errors'  => $errors,
				],
				422
			);
		}

		update_option( $this->setting_id, apply_filters( 'rtwpvg_update_option', $merged ) );

		// Gallery markup is cached per product, so any settings change has to
		// invalidate it (previously done by the WooCommerce save handler).
		$this->clear_all_gallery_transients();

		do_action( 'rtwpvg_settings_saved', $merged, $sanitized );

		wp_send_json_success( [ 'message' => esc_html__( 'Settings saved.', 'woo-product-variation-gallery' ) ] );
	}

	/**
	 * Sanitize one incoming value according to its field definition.
	 *
	 * @param array $field Field definition.
	 * @param mixed $value Raw value from the React app.
	 *
	 * @return mixed
	 */
	private function sanitize_field_value( $field, $value ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		switch ( $type ) {
			case 'checkbox':
			case 'switch':
				return $this->to_bool( $value ) ? 1 : 0;

			case 'image':
				return absint( $value );

			case 'number':
				$number = is_numeric( $value ) ? (float) $value : 0;
				if ( isset( $field['min'] ) && $number < (float) $field['min'] ) {
					$number = (float) $field['min'];
				}
				if ( isset( $field['max'] ) && $number > (float) $field['max'] ) {
					$number = (float) $field['max'];
				}

				return ( $number == (int) $number ) ? (int) $number : $number; // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Intentional numeric comparison.

			case 'select':
			case 'radio':
				$value   = sanitize_text_field( (string) $value );
				$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : [];
				// Fall back to the default when the value is not a known option.
				if ( $options && ! array_key_exists( $value, $options ) ) {
					return isset( $field['default'] ) ? $field['default'] : '';
				}

				return $value;

			default:
				return $this->sanitize_setting_value( $value );
		}
	}

	/**
	 * Recursively sanitize a setting value coming from the React app.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return mixed
	 */
	private function sanitize_setting_value( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'sanitize_setting_value' ], $value );
		}

		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Normalize the loose truthy values the UI can send ("0"/"false"/"").
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool
	 */
	private function to_bool( $value ) {
		if ( is_string( $value ) ) {
			return ! in_array( strtolower( trim( $value ) ), [ '', '0', 'false', 'no' ], true );
		}

		return (bool) $value;
	}

	/**
	 * Evaluate a field `condition` against the given settings.
	 *
	 * Mirrors the client-side check in utils/conditions.ts: boolean expectations
	 * are compared loosely (so "0"/"false" read as false) and `compare => '!='`
	 * inverts the result.
	 *
	 * @param array $condition Condition definition (field, value, compare).
	 * @param array $values    Settings to evaluate against.
	 *
	 * @return bool
	 */
	private function condition_matches( $condition, $values ) {
		$dep      = isset( $condition['field'] ) ? $condition['field'] : '';
		$expected = isset( $condition['value'] ) ? $condition['value'] : '';
		$actual   = isset( $values[ $dep ] ) ? $values[ $dep ] : '';

		if ( is_bool( $expected ) ) {
			$matches = ( $this->to_bool( $actual ) === $expected );
		} else {
			$matches = ( (string) $actual === (string) $expected );
		}

		if ( isset( $condition['compare'] ) && '!=' === $condition['compare'] ) {
			return ! $matches;
		}

		return $matches;
	}

	/**
	 * Collect "required" validation errors for the given (merged) settings.
	 *
	 * A field is only enforced when it is currently visible — i.e. it has no
	 * `condition`, or its `condition` field/value matches the merged data.
	 *
	 * @param array $values  Merged settings that would be persisted.
	 * @param array $allowed Flat map of saveable field definitions.
	 *
	 * @return array Map of field id => error message.
	 */
	private function get_required_errors( $values, $allowed ) {
		$errors = [];

		foreach ( $allowed as $id => $field ) {
			if ( empty( $field['required'] ) ) {
				continue;
			}

			// Respect conditional visibility: skip hidden required fields.
			if ( ! empty( $field['condition'] ) && ! $this->condition_matches( $field['condition'], $values ) ) {
				continue;
			}

			$value = isset( $values[ $id ] ) ? trim( (string) $values[ $id ] ) : '';
			if ( '' === $value ) {
				$errors[ $id ] = sprintf(
					/* translators: %s: field label. */
					esc_html__( '%s is required.', 'woo-product-variation-gallery' ),
					isset( $field['title'] ) ? wp_strip_all_tags( $field['title'] ) : $id
				);
			}
		}

		return $errors;
	}

	public function field_callback( $field ) {

		switch ( $field['type'] ) {
			case 'radio':
				$field_html = $this->radio_field_callback( $field );
				break;

			case 'checkbox':
				$field_html = $this->checkbox_field_callback( $field );
				break;

			case 'switch':
				$field_html = $this->switch_field_callback( $field );
				break;

			case 'select':
				$field_html = $this->select_field_callback( $field );
				break;

			case 'number':
				$field_html = $this->number_field_callback( $field );
				break;

			case 'image':
				$field_html = $this->image_field_callback( $field );
				break;

			case 'color':
				$field_html = $this->color_field_callback( $field );
				break;

			case 'post_select':
				$field_html = $this->post_select_field_callback( $field );
				break;

			case 'feature':
				$field_html = $this->feature_field_callback( $field );
				break;

			default:
				$field_html = $this->text_field_callback( $field );
				break;
		}
		ob_start();
		echo $field_html; // phpcs:ignore
		do_action( 'rtwpvg_settings_field_callback', $field );

		return ob_get_clean();
	}

	public function checkbox_field_callback( $args ) {

		$value = (bool) $this->get_option( $args['id'] );

		$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

		if ( ( isset( $args['is_pro'] ) && $args['is_pro'] ) && ! function_exists( 'rtwpvgp' ) ) {
			$attrs .= 'readonly';
		}

		return sprintf(
			'<fieldset><label><input %1$s type="checkbox" id="%2$s-field" name="%4$s[%2$s]" value="%3$s" %5$s/> %6$s</label></fieldset>',
			$attrs,
			$args['id'],
			true,
			$this->setting_id,
			checked( $value, true, false ),
			isset( $args['desc'] ) ? esc_attr( $args['desc'] ) : null
		);
	}

	public function switch_field_callback( $args ) {

		$value = (bool) $this->get_option( $args['id'] );

		$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

		if ( ( isset( $args['is_pro'] ) && $args['is_pro'] ) && ! function_exists( 'rtwpvgp' ) ) {
			$attrs .= 'readonly';
		}
		return sprintf(
			'<fieldset><label class="rtwpvg-switch"><input %1$s type="checkbox" id="%2$s-field" name="%4$s[%2$s]" value="%3$s" %5$s/><span class="rtwpvg-switch-slider round"></span></label>%6$s</fieldset>',
			$attrs,
			$args['id'],
			true,
			$this->setting_id,
			checked( $value, true, false ),
			isset( $args['desc'] ) && $args['desc'] ? '<p class="description">' . $args['desc'] . '</p>' : null
		);
	}

	public function radio_field_callback( $args ) {
		$options = apply_filters( "rtwpvg_settings_{$args[ 'id' ]}_radio_options", $args['options'] );
		$value   = esc_attr( $this->get_option( $args['id'] ) );

		$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

		$html  = '<fieldset>';
		$html .= implode(
			'<br />',
			array_map(
				function ( $key, $option ) use ( $attrs, $args, $value ) {
					return sprintf( '<label><input %1$s type="radio" id="%2$s-field" name="%4$s[%2$s]" value="%3$s" %5$s/> %6$s</label>', $attrs, $args['id'], $key, $this->setting_id, checked( $value, $key, false ), $option );
				},
				array_keys( $options ),
				$options
			)
		);
		$html .= $this->get_field_description( $args );
		$html .= '</fieldset>';

		return $html;
	}

	public function select_field_callback( $args ) {
		$options = apply_filters( "rtwpvg_settings_{$args[ 'id' ]}_select_options", $args['options'] );
		$value   = esc_attr( $this->get_option( $args['id'] ) );
		$options = array_map(
			function ( $key, $option ) use ( $value ) {
				return "<option value='{$key}'" . selected( $key, $value, false ) . ">{$option}</option>";
			},
			array_keys( $options ),
			$options
		);
		$size    = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

		if ( ( isset( $args['is_pro'] ) && $args['is_pro'] ) && ! function_exists( 'rtwpvgp' ) ) {
			$attrs .= 'disabled';
		}

		$html  = sprintf( '<select %5$s class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]">%3$s</select>', $size, $args['id'], implode( '', $options ), $this->setting_id, $attrs );
		$html .= $this->get_field_description( $args );

		return $html;
	}

	public function get_field_description( $args ) {
		if ( isset( $args['desc'] ) && ! empty( $args['desc'] ) ) {
			$desc = sprintf(
				'<p class="description">%s%s</p>',
				$args['id'] == 'license_key' ? sprintf(
					'<span class="license-status">%s</span>',
					trim( $this->get_option( $args['id'] ) ?? '' ) ? sprintf(
						'<span class="rt-licensing-btn button-secondary %s">%s</span>',
						$this->get_option( 'license_status' ) == 'valid' ? 'danger license_deactivate' : 'button-primary license_activate',
						$this->get_option( 'license_status' ) == 'valid' ? esc_html__( 'Deactivate License', 'woo-product-variation-gallery' ) : esc_html__( 'Activate License', 'woo-product-variation-gallery' )
					) : null
				) : null,
				$args['desc']
			);
		} else {
			$desc = '';
		}

		return $desc;
	}

	public function post_select_field_callback( $args ) {

		$options = apply_filters( "rtwpvg_settings_{$args[ 'id' ]}_post_select_options", $args['options'] );

		$value = esc_attr( $this->get_option( $args['id'] ) );

		$options = array_map(
			function ( $option ) use ( $value ) {
				return "<option value='{$option->ID}'" . selected( $option->ID, $value, false ) . ">$option->post_title</option>";
			},
			$options
		);

		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
		$html  = sprintf( '<select class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]">%3$s</select>', $size, $args['id'], implode( '', $options ), $this->setting_id );
		$html .= $this->get_field_description( $args );
		return $html;
	}

	public function text_field_callback( $args ) {
		$value = esc_attr( $this->get_option( $args['id'] ) );
		$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

		$html  = sprintf( '<input %5$s type="text" class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]" value="%3$s"/>', $size, $args['id'], $value, $this->setting_id, $attrs );
		$html .= $this->get_field_description( $args );

		return $html;
	}

	public function feature_field_callback( $args ) {

		$is_html = isset( $args['html'] );

		if ( $is_html ) {
			$html = $args['html'];
		} else {
			$image = esc_url( $args['screen_shot'] );
			$link  = esc_url( $args['product_link'] );

			$width = isset( $args['width'] ) ? $args['width'] : '70%';

			$html  = sprintf( '<a target="_blank" href="%s"><img style="width: %s" src="%s" /></a>', $link, $width, $image );
			$html .= $this->get_field_description( $args );
		}

		return $html;
	}

	public function color_field_callback( $args ) {
		$value = esc_attr( $this->get_option( $args['id'] ) );
		$alpha = isset( $args['alpha'] ) && $args['alpha'] === true ? ' data-alpha="true"' : '';
		$html  = sprintf( '<input type="text" %1$s class="rtwpvg-color-picker" id="%2$s-field" name="%4$s[%2$s]" value="%3$s"  data-default-color="%3$s" />', $alpha, $args['id'], $value, $this->setting_id );
		$html .= $this->get_field_description( $args );

		return $html;
	}

	public function number_field_callback( $args ) {
		$value  = esc_attr( $this->get_option( $args['id'] ) );
		$size   = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'small';
		$min    = isset( $args['min'] ) && ! is_null( $args['min'] ) ? 'min="' . $args['min'] . '"' : '';
		$max    = isset( $args['max'] ) && ! is_null( $args['max'] ) ? 'max="' . $args['max'] . '"' : '';
		$step   = isset( $args['step'] ) && ! is_null( $args['step'] ) ? 'step="' . $args['step'] . '"' : '';
		$suffix = isset( $args['suffix'] ) && ! is_null( $args['suffix'] ) ? ' <span>' . $args['suffix'] . '</span>' : '';
		$attrs  = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';
		if ( ! empty( $args['min'] ) ) {
			$value = absint( $value ) < absint( $args['min'] ) ? absint( $args['min'] ) : absint( $value );
		}
		if ( ! empty( $args['max'] ) ) {
			$value = absint( $value ) > absint( $args['max'] ) ? absint( $args['max'] ) : absint( $value );
		}
		$html  = sprintf( '<input %9$s type="number" class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]" value="%3$s" %5$s %6$s %7$s /> %8$s', $size, $args['id'], $value, $this->setting_id, $min, $max, $step, $suffix, $attrs );
		$html .= $this->get_field_description( $args );
		return $html;
	}

	public function image_field_callback( $args ) {
		$h     = null;
		$value = esc_attr( $this->get_option( $args['id'] ) );
		$name  = sprintf( '%1$s[%2$s]', $this->setting_id, $args['id'] );
		$h    .= sprintf( "<div class='rtwpvg-image' id='%s'>", esc_attr( $args['id'] ) );
		$h    .= sprintf( "<div class='rtwpvg-form-group'><div class='rtwpvg-preview-imgs %s'>", esc_attr( $args['id'] ) );

		if ( $value ) {
			$img_url = '';
			$img_src = wp_get_attachment_url( $value );
			if ( $img_src ) {
				$img_url = $img_src;
			}

			$h .= "<div class='rtwpvg-preview-img'><img src='" . $img_url . "' /><input type='hidden' name='" . $name . "' value='" . $value . "'><button class='rtwpvg-file-remove' data-id='" . $value . "'>x</button></div>";
		} else {
			$h .= "<div class='rtwpvg-preview-img'><input type='hidden' name='" . $name . "' value='0'></div>";
		}

		$h .= sprintf(
			"</div>
                        <button data-name='%s' data-field='image' type='button' class='rtwpvg-upload-box'> 
                            <span>%s</span>
                        </button>
                    </div>",
			$name,
			esc_html__( 'Upload Image', 'woo-product-variation-gallery' )
		);
		$h .= '</div>';

		$h .= $this->get_field_description( $args );

		return $h;
	}

	/**
	 * @param $option
	 * @param $givenDefault
	 *
	 * @return mixed|void
	 */
	public function get_option( $option, $givenDefault = null ) {
		$default = $this->get_default( $option );
		$options = get_option( $this->setting_id );
		$is_new  = ( ! is_array( $options ) && is_bool( $options ) );
		// A saved option array that predates a newly added field must still fall
		// back to that field's default. Checkboxes are excluded: an unchecked box
		// is simply absent, so falling back would make it impossible to turn off.
		$missing = ( is_array( $options ) && ! isset( $options[ $option ] ) && isset( $default['type'] ) && ! in_array( $default['type'], [ 'checkbox', 'switch' ], true ) );
		if ( $is_new || $missing ) {
			$value = isset( $default['value'] ) ? $default['value'] : $givenDefault;
		} else {
			$value = isset( $options[ $option ] ) ? $options[ $option ] : '';
			if ( $givenDefault && ! $value ) {
				$value = $givenDefault;
			}
		}

		return apply_filters( 'rtwpvg_get_option', $value, $default, $option, $options, $is_new );
	}

	private function get_options_tab_css_classes( $tabs ) {
		$classes   = [];
		$classes[] = ( $this->get_last_active_tab() == $tabs['id'] ) ? 'nav-tab-active' : '';

		return implode( ' ', array_unique( apply_filters( 'rtwpvg_get_options_tab_css_classes', $classes ) ) );
	}

	private function get_last_active_tab() {
		$last_tab = trim( $this->get_option( '_last_active_tab' ) ?? '' );
		if ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) && $this->setting_id == $_GET['tab'] && isset( $_GET['section'] ) && ! empty( $_GET['section'] ) ) { // phpcs:ignore
			$last_tab = trim( $_GET['section'] ?? '' ); // phpcs:ignore
		}
		$default_tab = 'general';
		foreach ( $this->sections as $tabs ) {
			if ( isset( $tabs['active'] ) && $tabs['active'] ) {
				$default_tab = $tabs['id'];
				break;
			}
		}

		return ! empty( $last_tab ) ? $last_tab : $default_tab;
	}

	public function update_option( $key, $value ) {
		$options         = get_option( $this->setting_id );
		$options[ $key ] = $value;
		update_option( $this->setting_id, apply_filters( 'rtwpvg_update_option', $options ) );
	}

	public function sanitize_callback( $options ) {
		foreach ( $this->get_defaults() as $opt ) {
			if ( $opt['type'] === 'checkbox' && ! isset( $options[ $opt['id'] ] ) ) {
				$options[ $opt['id'] ] = 0;
			}
		}

		return $options;
	}

	public function make_implode_html_attributes(
		$raw_attributes,
		$except = [
			'type',
			'id',
			'name',
			'value',
		]
	) {
		$attributes = [];
		foreach ( $raw_attributes as $name => $value ) {
			if ( in_array( $name, $except ) ) {
				continue;
			}
			$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return implode( ' ', $attributes );
	}

	public function array2html_attr( $attributes, $do_not_add = [] ) {

		$attributes = wp_parse_args( $attributes, [] );
		if ( ! empty( $do_not_add ) and is_array( $do_not_add ) ) {
			foreach ( $do_not_add as $att_name ) {
				unset( $attributes[ $att_name ] );
			}
		}
		$attributes_array = [];
		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $attributes[ $key ] ) and $attributes[ $key ] === true ) {
				return $attributes[ $key ] ? $key : '';
			} elseif ( is_bool( $attributes[ $key ] ) and $attributes[ $key ] === false ) {
				$attributes_array[] = '';
			} else {
				$attributes_array[] = $key . '="' . $value . '"';
			}
		}

		return implode( ' ', $attributes_array );
	}

	function pro_alert_html() {
		if ( function_exists( 'rtwpvgp' ) ) {
			return;
		}
		$html  = '';
		$html .= '<div class="rtvg-document-box rtvg-alert rtvg-pro-alert">
                <div class="rtvg-box-icon"><i class="dashicons dashicons-lock"></i></div>
                <div class="rtvg-box-content">
                    <h3 class="rtvg-box-title">' . esc_html__( 'Pro field alert!', 'woo-product-variation-gallery' ) . '</h3>
                    <p><span></span>' . esc_html__( 'Sorry! this is a pro field. To use this field, you need to use pro plugin.', 'woo-product-variation-gallery' ) . '</p>
                    <a href="https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/?utm_source=WordPress&utm_medium=gallery&utm_campaign=pro_click" target="_blank" class="rt-admin-btn">' . esc_html__( 'Upgrade to pro', 'woo-product-variation-gallery' ) . '</a>
                    <a href="#" target="_blank" class="rtvg-alert-close rtvg-pro-alert-close">x</a>
                </div>
            </div>';
		echo $html; // phpcs:ignore
	}
}

