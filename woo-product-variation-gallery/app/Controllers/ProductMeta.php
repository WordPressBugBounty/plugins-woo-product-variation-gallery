<?php

/**
 * Product meta box and product data tab for Variation Gallery.
 *
 * @package Rtwpvg\Controllers
 */

namespace Rtwpvg\Controllers;

/**
 * Class ProductMeta
 *
 * Adds a custom checkbox meta box and "Variation Gallery" product data tab
 * to the WooCommerce product edit page.
 */
class ProductMeta {

	/**
	 * Constructor to initialize hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ] );
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_data_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'render_product_data_panel' ] );
	}

	/**
	 * Adds a custom meta box to the WooCommerce product edit page.
	 */
	public function add_metabox() {
		add_meta_box(
			'custom_checkbox_metabox', // Metabox ID.
			__( 'Variation gallery', 'woo-product-variation-gallery' ), // Title.
			[ $this, 'metabox_callback' ], // Callback function.
			'product', // Post type (WooCommerce product).
			'side', // Position.
			'high' // Priority.
		);
	}

	/**
	 * Callback function to display the checkbox in the meta box.
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function metabox_callback( $post ) {
		$value = get_post_meta( $post->ID, '_rtwpvg_disable_valiation_gallery', true );
		?>
		<p>
			<label for="custom_checkbox">
				<input type="checkbox" id="rtwpvg_disable_valiation_gallery" name="rtwpvg_disable_valiation_gallery" value="yes" <?php checked( $value, 'yes' ); ?> />
				<?php esc_html_e( 'Disable Variation Gallery', 'woo-product-variation-gallery' ); ?>
			</label><hr/>
			<span><?php esc_html_e( 'Disable variation gallery for this product', 'woo-product-variation-gallery' ); ?> </span>
		</p>
		<?php
	}

	/**
	 * Saves the checkbox value when the product is updated.
	 *
	 * @param int $post_id The ID of the product being saved.
	 */
	public function save_metabox( $post_id ) {
		// Verify nonce for security.
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}
		// Prevent autosave from overwriting.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Check if the user has permission to edit the product.
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}
		// Save the checkbox value as 'yes' or 'no'.
		$value = isset( $_POST['rtwpvg_disable_valiation_gallery'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_rtwpvg_disable_valiation_gallery', $value );
	}

	/**
	 * Register the Variation Gallery tab in product data tabs.
	 *
	 * @param array $tabs Existing product data tabs.
	 *
	 * @return array
	 */
	public function add_product_data_tab( $tabs ) {
		$tabs['rtwpvg_gallery'] = [
			'label'    => esc_html__( 'Variation Gallery', 'woo-product-variation-gallery' ),
			'target'   => 'rtwpvg_gallery_options',
			'class'    => [ 'show_if_variable' ],
			'priority' => 65,
		];

		return $tabs;
	}

	/**
	 * Render the Variation Gallery panel content.
	 * Shows blurred pro placeholder if pro is not active.
	 *
	 * @return void
	 */
	public function render_product_data_panel() {
		?>
		<div id="rtwpvg_gallery_options" class="panel woocommerce_options_panel hidden">
			<?php
			if ( function_exists( 'rtwpvgp' ) ) {
				/**
				 * Fires to render the pro panel content.
				 *
				 * @param int $post_id The product post ID.
				 */
				do_action( 'rtwpvg_product_data_panel_content' );
			} else {
				$this->render_pro_placeholder();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render blurred pro placeholder content.
	 *
	 * @return void
	 */
	private function render_pro_placeholder() {
		$pro_url = 'https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/?utm_source=WordPress&utm_medium=gallery_tab&utm_campaign=pro_click';
		?>
		<div class="rtwpvg-pro-placeholder-wrapper">
			<div class="rtwpvg-pro-blurred-content" aria-hidden="true">
				<div class="options_group">
					<p class="form-field">
						<label><?php esc_html_e( 'Thumbnail Items', 'woo-product-variation-gallery' ); ?></label>
						<input type="number" disabled value="4" style="width:60px;" />
					</p>
					<p class="form-field">
						<label><?php esc_html_e( 'Thumbnail Style', 'woo-product-variation-gallery' ); ?></label>
						<select disabled>
							<option><?php esc_html_e( '— Global Setting —', 'woo-product-variation-gallery' ); ?></option>
						</select>
					</p>
					<p class="form-field">
						<label><?php esc_html_e( 'Transition Effect', 'woo-product-variation-gallery' ); ?></label>
						<select disabled>
							<option><?php esc_html_e( '— Global Setting —', 'woo-product-variation-gallery' ); ?></option>
						</select>
					</p>
					<p class="form-field">
						<label><?php esc_html_e( 'Image Zoom', 'woo-product-variation-gallery' ); ?></label>
						<select disabled>
							<option><?php esc_html_e( '— Global Setting —', 'woo-product-variation-gallery' ); ?></option>
						</select>
					</p>
					<p class="form-field">
						<label><?php esc_html_e( 'Lightbox', 'woo-product-variation-gallery' ); ?></label>
						<select disabled>
							<option><?php esc_html_e( '— Global Setting —', 'woo-product-variation-gallery' ); ?></option>
						</select>
					</p>
				</div>
			</div>

			<div class="rtwpvg-pro-overlay">
				<div class="rtwpvg-pro-card">
					<span class="rtwpvg-pro-icon dashicons dashicons-lock"></span>
					<span class="rtwpvg-pro-badge"><?php esc_html_e( 'Pro Feature', 'woo-product-variation-gallery' ); ?></span>
					<h3 class="rtwpvg-pro-title"><?php esc_html_e( 'Per-Product Gallery Settings', 'woo-product-variation-gallery' ); ?></h3>
					<p class="rtwpvg-pro-desc">
						<?php esc_html_e( 'Override global gallery settings per product — thumbnail style, grid presets, transition effects, zoom, lightbox, and thumbnail items.', 'woo-product-variation-gallery' ); ?>
					</p>
					<a href="<?php echo esc_url( $pro_url ); ?>" target="_blank" class="button button-primary rtwpvg-pro-btn">
						<span class="dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Upgrade to Pro', 'woo-product-variation-gallery' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

}
