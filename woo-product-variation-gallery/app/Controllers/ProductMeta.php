<?php

namespace Rtwpvg\Controllers;

/**
 * Class Custom_Product_Checkbox
 *
 * Adds a custom checkbox meta box to the WooCommerce product edit page.
 * Saves the checkbox value and displays it in the product list table.
 */
class ProductMeta {

	/**
	 * Constructor to initialize hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
		add_action( 'save_post', [ $this, 'save_metabox' ] );
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
	 * @param WP_Post $post The post object.
	 */
	public function metabox_callback( $post ) {
		$value = get_post_meta( $post->ID, '_rtwpvg_disable_valiation_gallery', true );
		?>
		<p>
			<label for="custom_checkbox">
				<input type="checkbox" id="rtwpvg_disable_valiation_gallery" name="rtwpvg_disable_valiation_gallery" value="yes" <?php checked( $value, 'yes' ); ?> />
				<?php esc_html_e( 'Disable Variation Gallery', 'woo-product-variation-gallery' ); ?>
			</label><br/>
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
}
