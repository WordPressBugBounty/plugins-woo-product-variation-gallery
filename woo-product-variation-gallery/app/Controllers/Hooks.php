<?php

namespace Rtwpvg\Controllers;

use Rtwpvg\Helpers\Functions;
use Rtwpvgp\Helpers\Functions as Fns;
use WPML\FP\Functor\ConstFunctor;

class Hooks {

	/**
	 * Whether this plugin's gallery template replaced WooCommerce's for this request.
	 *
	 * Used to decide when it is safe to stop WooCommerce building its own native
	 * variation gallery markup: only once our template is demonstrably the one
	 * rendering the product images.
	 *
	 * @var bool
	 */
	protected static $gallery_template_rendered = false;

	public function __construct() {
		add_action( 'admin_init', [ $this, 'after_plugin_active' ] );
		add_action( 'admin_init', [ $this, 'remove_native_variation_gallery_field' ], 20 );

		add_filter( 'woocommerce_product_variation_get_gallery_image_ids', [ __CLASS__, 'suppress_native_variation_gallery' ], 20, 2 );

		add_filter( 'body_class', [ $this, 'body_class' ] );
		add_filter( 'post_class', [ $this, 'product_loop_post_class' ], 25, 3 );

		add_action( 'after_setup_theme', [ $this, 'enable_theme_support' ], 200 ); // Enable theme support.

		add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_gallery' ], 10, 2 );
		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'gallery_admin_html' ], 10, 3 );

		add_filter( 'woocommerce_available_variation', [ $this, 'available_variation_gallery' ], 90, 3 );
		// 60 For support Avanam.
		add_filter( 'wc_get_template', [ $this, 'gallery_template_override' ], 60, 2 );

		add_action( 'wp_ajax_rtwpvg_get_default_gallery_images', [ $this, 'get_default_gallery_images' ] );
		add_action( 'wp_ajax_nopriv_rtwpvg_get_default_gallery_images', [ $this, 'get_default_gallery_images' ] );

		add_action( 'wp_ajax_rtwpvg_get_variation_gallery', [ $this, 'ajax_get_variation_gallery' ] );
		add_action( 'wp_ajax_nopriv_rtwpvg_get_variation_gallery', [ $this, 'ajax_get_variation_gallery' ] );

		add_filter( 'rtwpvg_inline_style', [ $this, 'rtwpvg_add_inline_style' ], 9 );
		add_action( 'woocommerce_update_product', [ $this, 'delete_cache_data' ], 10, 1 );
		add_action( 'delete_attachment', [ $this, 'delete_attachment_cache_data' ], 10, 1 );
		add_action( 'rtwpvg_product_badge', [ __CLASS__, 'add_yith_badge' ] );
		// rtwpvg_disable_enqueue_scripts.
		add_filter( 'rtwpvg_disable_enqueue_scripts', [ $this, 'disable_enqueue_scripts' ], 10 );
		add_filter( 'woocommerce_gallery_thumbnail_size', [ __CLASS__, 'rtwpvg_gallery_thumbnail_size' ], 15 );

		if ( ! defined( 'RTWPVGP_VERSION' ) || ( defined( 'RTWPVGP_VERSION' ) && version_compare( RTWPVGP_VERSION, '2.3.6', '>=' ) ) ) {
			add_filter( 'woocommerce_product_export_meta_value', [ __CLASS__, 'product_export_meta_value' ], 15, 4 );
			add_filter( 'woocommerce_product_import_process_item_data', [ __CLASS__, 'product_import_process_item_data' ], 15 );
		}
	}

	/**
	 * Export Image
	 *
	 * @param $meta_value
	 * @param $meta
	 * @param $product
	 * @param $row
	 *
	 * @return mixed|string
	 */
	public static function product_export_meta_value( $meta_value, $meta, $product, $row ) {
		if ( Functions::LEGACY_GALLERY_META_KEY !== $meta->key || ! ( is_array( $meta_value ) && count( $meta_value ) ) ) {
			return $meta_value;
		}

		/*
		 * Once a variation is native-owned the legacy meta is a stale snapshot: the
		 * migration copies rather than moves it, and later saves only touch the native
		 * store. Exporting it would put outdated URLs in the CSV, and re-importing that
		 * file would write them back over the correct gallery.
		 */
		if ( $product instanceof \WC_Product && Functions::is_native_gallery_owned( $product->get_id() ) ) {
			return '';
		}
		$images = [];
		foreach ( $meta_value as $image_id ) {
			$images[] = wp_get_attachment_image_url( $image_id, 'full' );
		}
		return implode( ',', $images );
	}

	/**
	 * @param $meta_value
	 * @param $meta
	 * @param $product
	 * @param $row
	 *
	 * @return mixed|string
	 */
	public static function product_import_process_item_data( $data ) {

		if ( empty( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) || ! count( $data['meta_data'] ) ) {
			return $data;
		}
		foreach ( $data['meta_data'] as $key => $meta ) {
			if ( Functions::LEGACY_GALLERY_META_KEY !== $meta['key'] ) {
				continue;
			}
			if ( empty( $meta['value'] ) ) {
				unset( $data['meta_data'][ $key ] );
				continue;
			}
			$images_url = array_filter( array_map( 'trim', explode( ',', $meta['value'] ) ) );

			if ( Functions::has_native_variation_gallery() ) {
				/*
				 * Hand the URLs to WooCommerce's own importer field rather than
				 * resolving them here: `set_image_data()` converts them to attachment
				 * IDs and calls set_gallery_image_ids() before the importer's single
				 * save(), so the gallery lands in the native store with no legacy meta
				 * and no extra save cycle. The key is excluded from set_props(), so it
				 * is safe to populate.
				 */
				$gallery_urls = isset( $data['raw_gallery_image_ids'] ) ? (array) $data['raw_gallery_image_ids'] : [];

				$data['raw_gallery_image_ids'] = array_values( array_unique( array_merge( $gallery_urls, $images_url ) ) );

				unset( $data['meta_data'][ $key ] );
				continue;
			}

			// WooCommerce < 11.1 has no native variation gallery to write to, so the
			// legacy meta stays the import target.
			$images_id = [];
			foreach ( $images_url as $url ) {
				$images_id[] = Functions::get_attachment_id_from_url( $url, $data['id'] );
			}
			$data['meta_data'][ $key ]['value'] = $images_id;
		}
		return $data;
	}

	/**
	 * Get image size data by size name.
	 *
	 * @param string $size Image size name.
	 *
	 * @return array|null Array with width, height, crop keys or null if not found.
	 */
	public static function get_image_size_data( $size ) {
		global $_wp_additional_image_sizes;

		if ( in_array( $size, [ 'thumbnail', 'medium', 'medium_large', 'large' ], true ) ) {
			return [
				'width'  => (int) get_option( "{$size}_size_w" ),
				'height' => (int) get_option( "{$size}_size_h" ),
				'crop'   => (bool) get_option( "{$size}_crop" ),
			];
		}

		if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
			return $_wp_additional_image_sizes[ $size ];
		}

		return null;
	}

	/**
	 * Override gallery thumbnail size from plugin settings.
	 *
	 * @param string|array $size Default thumbnail size.
	 *
	 * @return array Filtered thumbnail size as [width, height].
	 */
	public static function rtwpvg_gallery_thumbnail_size( $size ) {
		$thumbnail_size = rtwpvg()->get_option( 'gallery_thumbnail_size' );
		if ( $thumbnail_size ) {
			return $thumbnail_size;
		}
		return $size;
	}

	/**
	 * Boolean Return.
	 *
	 * @param boolean $bool boolean.
	 * @return bool
	 */
	public static function disable_enqueue_scripts( $bool ) {
		// TODO:: In the future version we need to load the script for specific page.
		if ( is_admin() ) {
			return $bool;
		}
		if ( is_singular( 'product' ) ) {
			global $post;
			$disabled = get_post_meta( $post->ID, '_rtwpvg_disable_valiation_gallery', true );
			if ( 'yes' === $disabled ) {
				return true;
			}
			return $bool;
		}
		if ( rtwpvg()->get_option( 'load_scripts' ) ) {
			return false;
		}
		return true;
	}
	/**
	 * @param \WC_Product $product
	 */
	public static function add_yith_badge( $product ) {
		if ( ( defined( 'YITH_WCBM_VERSION' ) && YITH_WCBM_VERSION ) || ( defined( 'YITH_WCBM_PREMIUM' ) && YITH_WCBM_PREMIUM ) ) {
			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', '<div class="rtwpvg-yith-badge"></div>', $product->get_image_id() ); // phpcs:ignore
		}
	}


	public function after_plugin_active() {
		if ( get_option( 'rtwpvg_pro_active' ) === 'yes' ) {
			delete_option( 'rtwpvg_pro_active' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . SettingsAPI::PAGE_SLUG ) );
		}
	}

	function delete_cache_data( $product_id ) {
		Functions::delete_transients( $product_id );

		// Also clear the per-variation gallery cache for each child variation.
		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				Functions::delete_transients( absint( $variation_id ), 'variation' );
			}
		}
	}

	/**
	 * Flush gallery caches when a media-library attachment is deleted.
	 *
	 * Gallery/variation IDs are cached as image props in transients. When an image
	 * is deleted its parent product's cache would otherwise keep serving the stale
	 * ID until the TTL expires, re-introducing the empty-box bug. Product gallery
	 * images are attached to the product, so clearing the parent product (and its
	 * variations) makes the deletion reflect immediately.
	 *
	 * @param int $attachment_id Deleted attachment ID.
	 *
	 * @return void
	 */
	public function delete_attachment_cache_data( $attachment_id ) {
		$attachment_id = absint( $attachment_id );
		if ( ! $attachment_id ) {
			return;
		}

		$parent_id = absint( wp_get_post_parent_id( $attachment_id ) );
		if ( ! $parent_id ) {
			return;
		}

		$product = wc_get_product( $parent_id );
		if ( ! $product ) {
			return;
		}

		$this->delete_cache_data( $product->get_id() );
	}




	public function body_class( $classes ) {
		array_push( $classes, 'rtwpvg' );

		return array_unique( $classes );
	}

	public function product_loop_post_class( $classes, $class, $product_id ) {

		if ( 'product' === get_post_type( $product_id ) ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_type( 'variable' ) ) {
				$classes[] = 'rtwpvg-product';
			}
		}

		return $classes;
	}

	function rtwpvg_add_inline_style( $styles ) {
		$gallery_width = absint( apply_filters( 'rtwpvg_default_width', 30 ) );
		if ( $gallery_width > 99 ) {
			$styles['float']   = 'none';
			$styles['display'] = 'block';
		}

		return $styles;
	}

	function gallery_template_override( $template, $template_name ) {
		global $product;
		if ( is_a( $product, 'WC_Product' ) ) {
			$disabled = get_post_meta( $product->get_id(), '_rtwpvg_disable_valiation_gallery', true );
			if ( 'yes' === $disabled ) {
				return $template;
			}
		}
		$old_template = $template;

		// Disable gallery on specific product

		$disable_gallery = apply_filters( 'rtwpvg_disable_variation_gallery', false );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Deprecated legacy hook, retained for backward compatibility.
		$disable_gallery = apply_filters_deprecated( 'disable_woo_variation_gallery', array( $disable_gallery ), '2.3.24', 'rtwpvg_disable_variation_gallery' );
		if ( $disable_gallery ) {
			return $old_template;
		}

		if ( $template_name == 'single-product/product-image.php' ) {
			$template = rtwpvg()->locate_template( 'swiper-product-images' );
		}

		if ( $template_name == 'single-product/product-thumbnails.php' ) {
			$template = rtwpvg()->locate_template( 'product-thumbnails' );
		}

		$template = apply_filters( 'rtwpvg_gallery_template_override_location', $template, $template_name, $old_template );

		// Record that our gallery — not WooCommerce's — is what the page renders.
		if ( 'single-product/product-image.php' === $template_name && $template !== $old_template ) {
			self::$gallery_template_rendered = true;
		}

		return $template;
	}

	/**
	 * Whether this plugin's variation gallery is active for a product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	public static function is_gallery_active( $product_id ) {
		if ( 'yes' === get_post_meta( absint( $product_id ), '_rtwpvg_disable_valiation_gallery', true ) ) {
			return false;
		}

		return ! apply_filters( 'rtwpvg_disable_variation_gallery', false );
	}

	/**
	 * Stop WooCommerce 11.1+ building its own variation gallery markup.
	 *
	 * `WC_Product_Variable::get_available_variation()` renders
	 * `single-product/product-image.php` once per variation whenever the native
	 * gallery holds images — and that template is ours, so after migration every
	 * variation would re-render the full slider into `data-product_variations`.
	 * Returning an empty list on the frontend skips that render entirely; the
	 * gallery data our script consumes is supplied separately through
	 * `variation_gallery_images`.
	 *
	 * Only the `view` context is filtered, so `Functions::get_native_variation_gallery_ids()`
	 * (which reads in `edit` context) and every admin/REST reader still see the
	 * stored value.
	 *
	 * @param array                 $gallery_image_ids Native gallery image IDs.
	 * @param \WC_Product_Variation $variation         Variation object.
	 *
	 * @return array
	 */
	public static function suppress_native_variation_gallery( $gallery_image_ids, $variation ) {
		if ( ! self::$gallery_template_rendered || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $gallery_image_ids;
		}

		if ( ! apply_filters( 'rtwpvg_suppress_native_variation_gallery', true, $variation ) ) {
			return $gallery_image_ids;
		}

		return [];
	}

	/**
	 * Hide WooCommerce 11.1+'s own variation gallery field in the product editor.
	 *
	 * This plugin keeps its existing metabox as the single authoring UI and writes
	 * straight into the native store, so rendering both fields would give merchants
	 * two controls for one value. WooCommerce's field markup is what its admin CSS
	 * keys off, so removing the render also leaves the stock variation image slot
	 * untouched.
	 *
	 * @return void
	 */
	public function remove_native_variation_gallery_field() {
		$class = 'Automattic\WooCommerce\Internal\VariationGallery\ClassicVariationGalleryAdmin';
		$hook  = 'woocommerce_variation_after_upload_image';

		if ( ! Functions::has_native_variation_gallery() || ! class_exists( $class ) ) {
			return;
		}

		if ( ! apply_filters( 'rtwpvg_remove_native_variation_gallery_field', true ) ) {
			return;
		}

		if ( empty( $GLOBALS['wp_filter'][ $hook ] ) ) {
			return;
		}

		// Matched on the registered object rather than resolved from WooCommerce's
		// container, so removal does not depend on the container handing back the
		// very same instance it registered the callback with.
		foreach ( $GLOBALS['wp_filter'][ $hook ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && isset( $callback['function'][0] ) && $callback['function'][0] instanceof $class ) {
					remove_action( $hook, $callback['function'], $priority );
				}
			}
		}
	}

	public function enable_theme_support() {
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
	}

	/**
	 * Persist the variation gallery selected in this plugin's metabox.
	 *
	 * Exactly one store is written, never both. On WooCommerce 11.1.0+ that is the
	 * native variation gallery, which the migration has already populated and which
	 * WooCommerce itself reads for CSV export and the REST API. Below 11.1 there is
	 * no native store to write to, so the legacy `rtwpvg_images` meta is used.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Variation row index.
	 *
	 * @return void
	 */
	public function save_variation_gallery( $variation_id, $loop ) {

		check_ajax_referer( 'save-variations', 'security' );

		$rtwpvg_ids = [];

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by check_ajax_referer() above.
		if ( isset( $_POST['rtwpvg'][ $variation_id ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by check_ajax_referer() above.
			$rtwpvg_ids = array_map( 'absint', (array) $_POST['rtwpvg'][ $variation_id ] );
			$rtwpvg_ids = array_values( array_unique( array_filter( $rtwpvg_ids ) ) );
		}

		if ( Functions::has_native_variation_gallery() ) {
			$variation = wc_get_product( $variation_id );

			if ( $variation && $variation->is_type( 'variation' ) ) {
				$variation->set_gallery_image_ids( $rtwpvg_ids );
				$variation->save();

				// Only once the native gallery has actually been written: the sentinel
				// permanently suppresses the legacy fallback, so stamping it after a
				// failed load would strand that variation's images unreachable.
				update_post_meta( $variation_id, Functions::NATIVE_GALLERY_SENTINEL_META_KEY, 'yes' );
			}
		} elseif ( $rtwpvg_ids ) {
			update_post_meta( $variation_id, Functions::LEGACY_GALLERY_META_KEY, $rtwpvg_ids );
		} else {
			delete_post_meta( $variation_id, Functions::LEGACY_GALLERY_META_KEY );
		}

		// Bust the per-variation gallery cache so the new images are served on demand.
		Functions::delete_transients( $variation_id, 'variation' );
	}

	public function gallery_admin_html( $loop, $variation_data, $variation ) {
		$variation_id   = absint( $variation->ID );
		$gallery_images = get_post_meta( $variation_id, Functions::LEGACY_GALLERY_META_KEY, true );

		// On WooCommerce 11.1+ the native gallery is authoritative, so the metabox
		// must show it rather than the legacy meta it mirrors. The legacy value is
		// only used while the variation is still waiting on the migration.
		if ( Functions::has_native_variation_gallery() ) {
			$variation_object = wc_get_product( $variation_id );

			if ( $variation_object && $variation_object->is_type( 'variation' ) ) {
				$native_images = Functions::get_native_variation_gallery_ids( $variation_object );

				if ( $native_images || Functions::is_native_gallery_owned( $variation_id ) ) {
					$gallery_images = $native_images;
				}
			}
		}
		?>
		<?php
		/*
		 * WooCommerce renders the variation's main image at thumbnail size (150px) in
		 * `html-variation-admin.php`, which is soft in the wider slot this plugin uses.
		 * Its own variation gallery hero uses `woocommerce_single`, so the matching URL
		 * is handed to the frontend script here — that markup belongs to core and
		 * exposes no filter to size it directly.
		 */
		$variation_image_id = absint( get_post_thumbnail_id( $variation_id ) );
		$hero_src           = $variation_image_id ? wp_get_attachment_image_url( $variation_image_id, 'woocommerce_single' ) : '';
		?>
		<div class="form-row form-row-full rtwpvg-gallery-wrapper" data-hero-src="<?php echo esc_url( $hero_src ); ?>">
			<h4><?php esc_html_e( 'Variation Image/Video Gallery', 'woo-product-variation-gallery' ); ?></h4>
			<div class="rtwpvg-image-container">
				<ul class="rtwpvg-images">
					<?php
					if ( is_array( $gallery_images ) && ! empty( $gallery_images ) ) {
						$gallery_images = array_values( array_unique( $gallery_images ) );
						foreach ( $gallery_images as $image_id ) :
							$image = wp_get_attachment_image_src( $image_id );
							$video = Functions::gallery_has_video( $image_id );
							if ( ! is_array( $image ) || empty( $image[0] ) ) {
								continue;
							}
							$add_video_class = $video ? ' video' : '';
							?>
							<li class="image<?php echo esc_html( $add_video_class ); ?>">
								<input type="hidden" name="rtwpvg[<?php echo esc_attr( $variation_id ); ?>][]" value="<?php echo absint( $image_id ); ?>">
								<img src="<?php echo esc_url( $image[0] ); ?>">
								<div class="rtwpvg-action-button">
									<span class="rtwpvg-media-video-popup woocommerce-help-tip dashicons dashicons-video-alt3" data-tip="Add Video" ></span>
									<span class="rtwpvg-gallery-edit woocommerce-help-tip dashicons dashicons-edit" data-tip="Edit Image" ></span>
									<a href="#" class="delete rtwpvg-remove-image woocommerce-help-tip " data-tip="Remove">
										<span class="dashicons dashicons-no"></span>
									</a>
								</div>
							</li>
							<?php
						endforeach;
					}
					?>
				</ul>
			</div>
			<p class="rtwpvg-add-image-wrapper hide-if-no-js">
				<a href="#" data-product_variation_loop="<?php echo absint( $loop ); ?>"
				   data-product_variation_id="<?php echo esc_attr( $variation_id ); ?>"
				   class="button rtwpvg-add-image"><?php esc_html_e( 'Add Gallery Images', 'woo-product-variation-gallery' ); ?></a>
			</p>
		</div>
		<?php
	}


	/**
	 * @param $available_variation
	 * @param $variationProductObject
	 * @param $variation
	 *
	 * @return string
	 */
	public function available_variation_gallery( $available_variation, $variationProductObject, $variation ) {

		$product_id = absint( $variation->get_parent_id() );

		/**
		 * Hybrid loading strategy.
		 *
		 * For small products (few variations) the gallery props are embedded inline
		 * so the frontend swaps instantly with no AJAX round-trip. For large,
		 * image-heavy products they are omitted and fetched on demand per selected
		 * variation via the `rtwpvg_get_variation_gallery` AJAX endpoint, keeping the
		 * initial page payload lean.
		 *
		 * The threshold counts the parent product's variations (a cheap proxy that
		 * avoids building any image props to decide) and is filterable.
		 *
		 * @see \Rtwpvg\Helpers\Functions::get_variation_gallery()
		 */
		$variation_count = count( $variationProductObject->get_children() );

		/**
		 * Maximum number of variations for which galleries are embedded inline.
		 *
		 * @param int $max         Variation-count threshold. Default 30.
		 * @param int $product_id  Parent product ID.
		 */
		$inline_max = absint( apply_filters( 'rtwpvg_inline_gallery_max_variations', 30, $product_id ) );

		if ( $variation_count > 0 && $variation_count <= $inline_max ) {
			$available_variation['variation_gallery_images'] = Functions::get_variation_gallery( $product_id, absint( $variation->get_id() ) );
		}

		/*
		 * WooCommerce 11.1+ ships its own gallery markup in `gallery_images_html` and
		 * swaps it in from `add-to-cart-variation.js`. Our gallery owns that DOM, so
		 * drop the payload to keep core's swap from competing with ours — and to keep
		 * a duplicate copy of the gallery out of `data-product_variations`.
		 */
		if ( Functions::has_native_variation_gallery() && isset( $available_variation['gallery_images_html'] ) && self::is_gallery_active( $product_id ) ) {
			$available_variation['gallery_images_html'] = '';
		}

		return apply_filters( 'rtwpvg_available_variation_gallery', $available_variation, $variation, $product_id );
	}

	public function get_default_gallery_images() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		if ( ! $this->is_product_readable( $product_id ) ) {
			wp_send_json_error( [], 404 );
		}

		$images = Functions::get_gallery_images( $product_id );
		wp_send_json_success( apply_filters( 'rtwpvg_get_default_gallery_images', $images, $product_id ) );
	}

	/**
	 * Verify a product may be exposed to the current (possibly unauthenticated) caller.
	 *
	 * Prevents information disclosure on the public, nonce-less gallery AJAX endpoints:
	 * only published products are served to guests, while draft/pending/private products
	 * require the caller to hold read capability for that specific post.
	 *
	 * @param int $product_id Product ID to authorise.
	 *
	 * @return bool True when the product exists and may be read by the caller.
	 */
	protected function is_product_readable( $product_id ) {
		if ( ! $product_id ) {
			return false;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return false;
		}

		return 'publish' === $product->get_status() || current_user_can( 'read_post', $product_id );
	}

	/**
	 * AJAX: Return a single variation's gallery image props on demand.
	 *
	 * This is a public, read-only endpoint serving public product image data. It is
	 * intentionally nonce-less, mirroring WooCommerce core's own variation AJAX, so
	 * it keeps working on pages served from a full-page cache (where a localised
	 * nonce would be stale). All input is sanitised.
	 *
	 * @return void
	 */
	public function ajax_get_variation_gallery() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public, read-only endpoint (mirrors WooCommerce core's nonce-less variation AJAX) for full-page-cache compatibility.
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;

		if ( ! $variation_id ) {
			wp_send_json_error( [], 400 );
		}

		$variation = wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			wp_send_json_error( [], 404 );
		}

		// Authorise the variation's ACTUAL parent, never the caller-supplied
		// product_id, which an attacker can spoof with any published product.
		$parent_id = absint( $variation->get_parent_id() );

		if ( ! $this->is_product_readable( $parent_id ) ) {
			wp_send_json_error( [], 404 );
		}

		$images = Functions::get_variation_gallery( $parent_id, $variation_id );

		wp_send_json_success( apply_filters( 'rtwpvg_get_variation_gallery', $images, $variation_id, $parent_id ) );
	}
}
