<?php

namespace Rtwpvg\Helpers;

class Options {


	public static function get_settings_sections() {
		$fields = [
			'general'         => [
				'id'     => 'general',
				'title'  => esc_html__( 'General', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Configure gallery layout, thumbnail display, and responsive width settings.', 'woo-product-variation-gallery' ),
				'fields' => apply_filters(
					'rtwpvg_general_setting_fields',
					[
						[
							'id'    => 'card_thumbnails',
							'type'  => 'card',
							'title' => esc_html__( 'Thumbnails', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'How many thumbnail items are shown on each device, and the space between them.', 'woo-product-variation-gallery' ),
						],
						[
							'title'    => esc_html__( 'Thumbnail Items', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_thumbnails_columns', 4 ) ),
							'desc_tip' => esc_html__( 'Number of thumbnail items to display.', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => sprintf( esc_html__( 'Items per row (horizontal), per column (vertical), or per view when slider is active. Default: %d. Limit: 2-8.', 'woo-product-variation-gallery' ), absint( apply_filters( 'rtwpvg_thumbnails_columns', 4 ) ) ),
							'id'       => 'thumbnails_columns',
							'min'      => 2,
							'max'      => 8,
							'step'     => 1,
						],
						[
							'title'    => esc_html__( 'Thumbnail Items (Tablet)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_md_thumbnails_columns', 4 ) ),
							'desc_tip' => esc_html__( 'Number of thumbnail items on tablet devices.', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => sprintf( esc_html__( 'Items per row, column, or view on tablet. Default: %d. Limit: 2-8.', 'woo-product-variation-gallery' ), absint( apply_filters( 'rtwpvg_md_thumbnails_columns', 4 ) ) ),
							'id'       => 'thumbnails_columns_sm',
							'min'      => 2,
							'max'      => 8,
							'step'     => 1,
						],
						[
							'title'    => esc_html__( 'Thumbnail Items (Mobile)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_sm_thumbnails_columns', 3 ) ),
							'desc_tip' => esc_html__( 'Number of thumbnail items on mobile devices.', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => sprintf( esc_html__( 'Items per row, column, or view on mobile. Default: %d. Limit: 2-8.', 'woo-product-variation-gallery' ), absint( apply_filters( 'rtwpvg_sm_thumbnails_columns', 3 ) ) ),
							'id'       => 'thumbnails_columns_xs',
							'min'      => 2,
							'max'      => 8,
							'step'     => 1,
						],
						[
							'title'    => esc_html__( 'Thumbnails Gap', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_thumbnails_gap', 0 ) ),
							'desc_tip' => esc_html__( 'Product Thumbnails Gap In Pixel', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => sprintf( esc_html__( 'Product Thumbnails Gap In Pixel. Default value is: %d. Limit: 0-20.', 'woo-product-variation-gallery' ), apply_filters( 'rtwpvg_thumbnails_gap', 0 ) ),
							'id'       => 'thumbnails_gap',
							'min'      => 0,
							'max'      => 50,
							'step'     => 1,
							'suffix'   => 'px',
						],
						[
							'id'    => 'card_gallery_size',
							'type'  => 'card',
							'title' => esc_html__( 'Gallery Size', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'Width of the gallery column per breakpoint, plus the space below it.', 'woo-product-variation-gallery' ),
						],
						[
							'title'    => esc_html__( 'Gallery Width (Large Device)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_gallery_width', 46 ) ),
							'desc_tip' => esc_html__( 'Slider gallery width in % for large devices.', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => 'For large devices.<br>' . sprintf( __( 'Slider Gallery Width in percentage. Default value is: %d. Limit: 10-100.', 'woo-product-variation-gallery' ), absint( apply_filters( 'rtwpvg_default_width', 30 ) ) ),
							'id'       => 'gallery_width',
							'min'      => 10,
							'max'      => 100,
							'step'     => 1,
							'suffix'   => '%',
						],
						[
							'title'    => esc_html__( 'Gallery Width (Medium Device)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_gallery_md_width', 0 ) ),
							'desc_tip' => esc_html__( 'Slider gallery width in % for medium devices, small desktop', 'woo-product-variation-gallery' ),
							/* translators: %s: width */
							'desc'     => 'For medium devices.<br>' . esc_html__( 'Slider gallery width in % for medium devices, small desktop. Default value is: 0. Limit: 0-100. Media query (max-width : 992px)', 'woo-product-variation-gallery' ),
							'id'       => 'gallery_md_width',
							'min'      => 0,
							'max'      => 100,
							'step'     => 1,
							'suffix'   => '%',
						],
						[
							'title'    => esc_html__( 'Gallery Width (Small Device)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_gallery_sm_width', 100 ) ),
							'desc_tip' => esc_html__( 'Slider gallery width in % for small devices, tablets', 'woo-product-variation-gallery' ),
							/* translators: %s: width */
							'desc'     => 'For small devices, tablets.<br>' . esc_html__( 'Slider gallery width in % for medium devices, small desktop. Default value is: 100. Limit: 0-100. Media query (max-width : 768px)', 'woo-product-variation-gallery' ),
							'id'       => 'gallery_sm_width',
							'min'      => 0,
							'max'      => 100,
							'step'     => 1,
							'suffix'   => '%',
						],
						[
							'title'    => esc_html__( 'Gallery Width (Extra Small Device)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_gallery_xsm_width', 100 ) ),
							'desc_tip' => esc_html__( 'Slider gallery width in % for extra small devices, phones', 'woo-product-variation-gallery' ),
							'desc'     => 'For extra small devices, mobile.<br>' . esc_html__( 'Slider gallery width in % for extra small devices, phones. Default value is: 100. Limit: 0-100. Media query (max-width : 480px)', 'woo-product-variation-gallery' ),
							'id'       => 'gallery_xsm_width',
							'min'      => 0,
							'max'      => 100,
							'step'     => 1,
							'suffix'   => '%',
						],
						[
							'title'    => esc_html__( 'Gallery Bottom Gap', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_gallery_margin', 30 ) ),
							'desc_tip' => esc_html__( 'Slider gallery bottom margin in pixel', 'woo-product-variation-gallery' ),
							/* translators: %d: margin in pixels */
							'desc'     => sprintf( esc_html__( 'Slider gallery bottom margin in pixel. Default value is: %d. Limit: 10-100.', 'woo-product-variation-gallery' ), apply_filters_deprecated( 'gallery_margin', array( apply_filters( 'rtwpvg_gallery_margin', 30 ) ), '2.4.3', 'rtwpvg_gallery_margin' ) ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated legacy hook, retained for backward compatibility.
							'id'       => 'gallery_margin',
							'min'      => 10,
							'max'      => 100,
							'step'     => 1,
							'suffix'   => 'px',
						],
						[
							'id'    => 'card_gallery_behaviour',
							'type'  => 'card',
							'title' => esc_html__( 'Behaviour', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'How the gallery reacts when a shopper picks a different variation.', 'woo-product-variation-gallery' ),
						],
						[
							'title'   => esc_html__( 'Reset Variation Gallery', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'desc'    => esc_html__( 'Always Reset Gallery After Variation Select. It serves as the default selection for variation options. The control effect will not be visible when using the variation swatches plugin.', 'woo-product-variation-gallery' ),
							'id'      => 'reset_on_variation_change',
						],
					]
				),
			],
			'advanced'        => [
				'id'     => 'advanced',
				'title'  => esc_html__( 'Advanced', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Configure zoom, lightbox, slider behavior, preloader, and transition effects.', 'woo-product-variation-gallery' ),
				'fields' => apply_filters(
					'rtwpvg_advanced_setting_fields',
					[
						[
							'id'    => 'card_zoom_lightbox',
							'type'  => 'card',
							// Card titles are rendered by React as text nodes, which escape on
							// output — pre-escaping here would double-encode the ampersand.
							'title' => __( 'Zoom & Lightbox', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'Magnifier on hover and the full-screen lightbox shoppers open from the main image.', 'woo-product-variation-gallery' ),
						],
						[
							'title'   => esc_html__( 'Zoom Gallery image', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'desc'    => esc_html__( 'Enable mouse hover zoom effect on the main gallery image. Hovering over the product image will show a magnified view.', 'woo-product-variation-gallery' ),
							'id'      => 'zoom',
						],
						[
							'title'   => esc_html__( 'LightBox', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'desc'    => esc_html__( 'Enable the lightbox icon on the gallery image. Users can click the icon to open a full-screen lightbox view.', 'woo-product-variation-gallery' ),
							'id'      => 'lightbox',
						],
						[
							'title'     => esc_html__( 'LightBox on image click', 'woo-product-variation-gallery' ),
							'type'      => 'switch',
							'desc'      => esc_html__( 'Open the lightbox when clicking directly on the main gallery image, instead of requiring the lightbox icon click.', 'woo-product-variation-gallery' ),
							'id'        => 'lightbox_image_click',
							'condition' => [
								'field' => 'lightbox',
								'value' => true,
							],
						],
						[
							'title'     => esc_html__( 'Zoom Button Position', 'woo-product-variation-gallery' ),
							'type'      => 'select',
							'default'   => 'top-right',
							'desc'      => esc_html__( 'Set the position of the lightbox/zoom icon on the main gallery image.', 'woo-product-variation-gallery' ),
							'id'        => 'zoom_position',
							'options'   => [
								'top-right'    => esc_html__( 'Top right', 'woo-product-variation-gallery' ),
								'top-left'     => esc_html__( 'Top left', 'woo-product-variation-gallery' ),
								'bottom-right' => esc_html__( 'Bottom right', 'woo-product-variation-gallery' ),
								'bottom-left'  => esc_html__( 'Bottom left', 'woo-product-variation-gallery' ),
							],
							'condition' => [
								'field' => 'lightbox',
								'value' => true,
							],
						],
						[
							'id'    => 'card_main_image',
							'type'  => 'card',
							'title' => esc_html__( 'Main Image', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'Transition, navigation and sizing of the large gallery image.', 'woo-product-variation-gallery' ),
						],
						[
							'title'   => esc_html__( 'Main Image Transition Effect', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'Choose the transition effect for the main image slider.', 'woo-product-variation-gallery' ),
							'id'      => 'gallery_change_effect',
							'type'    => 'select',
							'default' => 'slide',
							'options' => [
								'slide' => esc_html__( 'Slide', 'woo-product-variation-gallery' ),
								'fade'  => esc_html__( 'Fade', 'woo-product-variation-gallery' ),
							],
						],
						[
							'title'     => esc_html__( 'Slider Navigation (Arrow)', 'woo-product-variation-gallery' ),
							'type'      => 'switch',
							'default'   => true,
							'desc'      => esc_html__( 'Show previous/next navigation arrows on the main gallery slider. Requires the pro version.', 'woo-product-variation-gallery' ),
							'id'        => 'slider_arrow',
							'is_pro'    => true,
							// The grid layout has no slider, so arrows do not apply.
							'condition' => [
								'field'   => 'thumbnail_position',
								'value'   => 'grid',
								'compare' => '!=',
							],
						],
						[
							'title'     => esc_html__( 'Slider Adaptive Height', 'woo-product-variation-gallery' ),
							'type'      => 'switch',
							'default'   => true,
							'desc'      => esc_html__( 'Automatically adjust the slider height based on the current slide image dimensions. Disable to use a fixed height.', 'woo-product-variation-gallery' ),
							'id'        => 'slider_adaptive_height',
							'condition' => [
								'field'   => 'thumbnail_position',
								'value'   => 'grid',
								'compare' => '!=',
							],
						],
						[
							'title'   => esc_html__( 'Remove Featured/Thumbnail Image', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => false,
							'desc'    => esc_html__( 'Enable this option to remove the Featured/Thumbnail image from the slider when a variation is selected.', 'woo-product-variation-gallery' ),
							'id'      => 'remove_featured_thumbnail',
						],
						[
							// Not "Thumbnails": the General section already owns a card by that
							// name for column counts and spacing.
							'id'    => 'card_thumbnail_layout',
							'type'  => 'card',
							'title' => esc_html__( 'Thumbnail Layout', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'Position, size and scrolling of the thumbnail strip.', 'woo-product-variation-gallery' ),
						],
						[
							'title'   => esc_html__( 'Thumbnail Style', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'Set the position of thumbnail images relative to the main gallery image. Pro version adds the grid layout. Note: When the thumbnail position is set to Left or Right, the thumbnail slider is automatically enabled.', 'woo-product-variation-gallery' ),
							'id'      => 'thumbnail_position',
							'type'    => 'select',
							'default' => 'bottom',
							// Grid Style is appended by the pro plugin; every other
							// position ships with the free version.
							'options' => apply_filters(
								'rtwpvg_thumbnail_style',
								[
									'bottom' => esc_html__( 'Position Bottom', 'woo-product-variation-gallery' ),
									'left'   => esc_html__( 'Position Left - Thumbnail Carousel', 'woo-product-variation-gallery' ),
									'right'  => esc_html__( 'Position Right - Thumbnail Carousel', 'woo-product-variation-gallery' ),
								]
							),
						],
						[
							'title'     => esc_html__( 'Thumbnail Slider', 'woo-product-variation-gallery' ),
							'type'      => 'switch',
							'default'   => true,
							'desc'      => esc_html__( 'Enable sliding/scrolling behavior for thumbnail images when there are more thumbnails than visible slots.', 'woo-product-variation-gallery' ),
							'id'        => 'thumbnail_slide',
							// Left/right positions force the thumbnail slider on and
							// the grid layout has none, so this only applies at the
							// bottom position.
							'condition' => [
								'field' => 'thumbnail_position',
								'value' => 'bottom',
							],
						],
						[
							'title'   => esc_html__( 'Gallery Thumbnail Size', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'Select the registered image size to use for gallery thumbnails. Choose from available WordPress image sizes configured in your site.', 'woo-product-variation-gallery' ),
							'id'      => 'gallery_thumbnail_size',
							'type'    => 'select',
							'default' => '',
							'options' => Functions::only_registered_image_size(),
						],
						[
							'id'    => 'card_loading',
							'type'  => 'card',
							'title' => esc_html__( 'Loading', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'What shoppers see while gallery images load or switch between variations.', 'woo-product-variation-gallery' ),
						],
						[
							'title'   => esc_html__( 'Preloader', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'desc'    => esc_html__( 'Show a loading animation while the gallery images are being loaded. Improves perceived performance on slower connections.', 'woo-product-variation-gallery' ),
							'id'      => 'preloader',
						],
						[
							'id'        => 'preloader_image',
							'type'      => 'image',
							'title'     => esc_html__( 'Preloader Image', 'woo-product-variation-gallery' ),
							'desc'      => esc_html__( 'Upload a custom preloader image to replace the default loading animation. Recommended size: 60x60 pixels.', 'woo-product-variation-gallery' ),
							'condition' => [
								'field' => 'preloader',
								'value' => true,
							],
						],
						[
							'title'   => esc_html__( 'Gallery Change Effect', 'woo-product-variation-gallery' ),
							'type'    => 'select',
							'default' => 'none',
							'desc'    => esc_html__( 'Choose the visual effect applied to the gallery when switching between variations. Select No effect to keep the gallery fully visible during the transition.', 'woo-product-variation-gallery' ),
							'id'      => 'preload_style',
							'options' => [
								'none' => esc_html__( 'No effect', 'woo-product-variation-gallery' ),
								'blur' => esc_html__( 'Blur', 'woo-product-variation-gallery' ),
								'fade' => esc_html__( 'Fade', 'woo-product-variation-gallery' ),
								'gray' => esc_html__( 'Gray', 'woo-product-variation-gallery' ),
							],
						],
					]
				),
			],
			'style'           => [
				'id'     => 'style',
				'title'  => esc_html__( 'Style', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Customize the colors of slider navigation arrows. Requires the pro version.', 'woo-product-variation-gallery' ),
				'active' => apply_filters( 'rtwpvg_style_setting_active', false ),
				'fields' => apply_filters(
					'rtwpvg_style_setting_fields',
					[
						[
							'id'      => 'arrow_bg_color',
							'is_pro'  => true,
							'type'    => 'color',
							'title'   => esc_html__( 'Arrow background', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'Set the background color of the slider navigation arrows. Supports alpha transparency.', 'woo-product-variation-gallery' ),
							'default' => 'rgba(0, 0, 0, 0.5)',
							'alpha'   => true,
						],
						[
							'id'      => 'arrow_bg_hover_color',
							'is_pro'  => true,
							'type'    => 'color',
							'title'   => esc_html__( 'Arrow background hover', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'Set the background color of the slider navigation arrows on mouse hover.', 'woo-product-variation-gallery' ),
							'default' => 'rgba(0, 0, 0, 0.9)',
						],
						[
							'id'      => 'arrow_text_color',
							'is_pro'  => true,
							'type'    => 'color',
							'default' => '#ffffff',
							'title'   => esc_html__( 'Arrow text color', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'Set the color of the arrow icon inside the slider navigation buttons.', 'woo-product-variation-gallery' ),
						],
						[
							'id'      => 'arrow_text_hover_color',
							'is_pro'  => true,
							'type'    => 'color',
							'title'   => esc_html__( 'Arrow text hover color', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'Set the color of the arrow icon inside the slider navigation buttons on mouse hover.', 'woo-product-variation-gallery' ),
							'default' => '#ffffff',
						],
					]
				),
			],
			'tools'           => [
				'id'     => 'tools',
				'title'  => esc_html__( 'Tools', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Manage plugin data and script loading settings.', 'woo-product-variation-gallery' ),
				'active' => apply_filters( 'rtwpvg_tools_setting_active', false ),
				'fields' => apply_filters(
					'rtwpvg_tools_setting_fields',
					[
						[
							'id'    => 'remove_all_data',
							'type'  => 'switch',
							'title' => esc_html__( 'Enable to delete all data', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'When enabled, all plugin data will be permanently removed from the database upon plugin deletion. Keep disabled to preserve settings if you plan to reinstall.', 'woo-product-variation-gallery' ),
						],
						[
							'id'      => 'load_scripts',
							'type'    => 'checkbox',
							'title'   => esc_html__( 'Load Scripts', 'woo-product-variation-gallery' ),
							'desc'    => esc_html__( 'By default, gallery scripts load only on product and shop pages. Enable this to load scripts site-wide, useful if you display products via shortcodes or custom templates on other pages.', 'woo-product-variation-gallery' ),
							'default' => false,
						],
					]
				),
			],
			'license'         => [
				'id'     => 'license',
				'title'  => esc_html__( 'License', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Add your licence code here', 'woo-product-variation-gallery' ),
				'active' => apply_filters( 'rtwpvg_license_setting_active', false ),
				'fields' => apply_filters(
					'rtwpvg_license_setting_fields',
					[
						[
							'id'    => 'license_key',
							'type'  => 'license',
							'title' => esc_html__( 'Licence key', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'Enter your license key and activate it to unlock Pro features and updates.', 'woo-product-variation-gallery' ),
						],
					]
				),
			],
			'premium_plugins' => [
				'id'     => 'premium_plugins',
				'title'  => esc_html__( 'Related Plugins', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'You can try our premium plugins', 'woo-product-variation-gallery' ),
				'fields' => apply_filters(
					'rtwpvg_premium_plugins_setting_fields',
					[
						[
							'id'         => 'premium_feature',
							'type'       => 'feature',
							'attributes' => [
								'class' => 'rt-feature',
							],
							'html'       => Functions::get_product_list_html(
								[
									'rtsb-pro'   => [
										'price'     => '$41.00 – $209.00',
										'title'     => 'ShopBuilder – Elementor WooCommerce Builder Addons',
										'image_url' => rtwpvg()->get_images_uri( 'shopbuilde.png' ),
										'url'       => 'https://www.radiustheme.com/downloads/woocommerce-bundle/',
										'demo_url'  => 'https://shopbuilderwp.com/',
										'buy_url'   => 'https://www.radiustheme.com/downloads/woocommerce-bundle/',
									],
									'rtwpvg-pro' => [
										'price'     => '$29.00 – $549.00',
										'title'     => 'Variation Images Gallery for WooCommerce Pro',
										'image_url' => rtwpvg()->get_images_uri( 'rtwpvg-pro.png' ),
										'url'       => 'https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/?utm_source=WordPress&utm_medium=gallery&utm_campaign=pro_click',
										'demo_url'  => 'https://radiustheme.com/demo/wordpress/woopluginspro/product/woocommerce-variation-images-gallery/',
										'buy_url'   => 'https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/?utm_source=WordPress&utm_medium=gallery&utm_campaign=pro_click',
									],
									'rtwpvs-pro' => [
										'title'     => 'Variation Swatches for WooCommerce Pro',
										'price'     => '$29.00 – $549.00',
										'image_url' => rtwpvg()->get_images_uri( 'rtwpvs-pro.png' ),
										'url'       => 'https://www.radiustheme.com/downloads/woocommerce-variation-swatches/',
										'demo_url'  => 'https://radiustheme.com/demo/wordpress/woopluginspro/',
										'buy_url'   => 'https://www.radiustheme.com/downloads/woocommerce-variation-swatches/',
									],
									'metro'      => [
										'title'     => 'Metro – Minimal WooCommerce WordPress Theme',
										'image_url' => rtwpvg()->get_images_uri( 'metro.jpg' ),
										'url'       => 'https://www.radiustheme.com/downloads/metro-minimal-woocommerce-wordpress-theme/',
										'demo_url'  => 'https://www.radiustheme.com/demo/wordpress/themes/metro/preview/',
										'buy_url'   => 'https://www.radiustheme.com/downloads/metro-minimal-woocommerce-wordpress-theme/',
									],
								]
							),
						],
					]
				),
			],
		];

		// The licensing UI is only meaningful with Pro active: the free plugin ships
		// the field and its AJAX plumbing, but there is nothing to license without it.
		if ( ! rtwpvg()->active_pro() ) {
			unset( $fields['license'] );
		}

		return apply_filters( 'rtwpvg_settings_fields', $fields );
	}
}
