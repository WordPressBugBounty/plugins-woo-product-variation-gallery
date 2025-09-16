<?php

namespace Rtwpvg\Helpers;

class Options {


	public static function get_settings_sections() {
		$fields = [
			'general'         => [
				'id'     => 'general',
				'title'  => esc_html__( 'General', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Simple change some visual styles', 'woo-product-variation-gallery' ),
				'fields' => apply_filters(
					'rtwpvg_general_setting_fields',
					[
						[
							'title'    => esc_html__( 'Thumbnails Items per row / slider view', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_thumbnails_columns', 4 ) ),
							'desc_tip' => esc_html__( 'Product Thumbnails Item Image', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => sprintf( esc_html__( 'Product Thumbnails Item Image. Default value is: %d. Limit: 2-8. The field required for slider.', 'woo-product-variation-gallery' ), absint( apply_filters( 'rtwpvg_thumbnails_columns', 4 ) ) ),
							'id'       => 'thumbnails_columns',
							'min'      => 2,
							'max'      => 8,
							'step'     => 1,
						],
						[
							'title'    => esc_html__( 'Thumbnails Items per row / slider view (Medium Device)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_md_thumbnails_columns', 4 ) ),
							'desc_tip' => esc_html__( 'Product Thumbnails Item Image For (Medium Device)', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => sprintf( esc_html__( 'Product Thumbnails Item Image. Default value is: %d. Limit: 2-8. The field required for slider.', 'woo-product-variation-gallery' ), absint( apply_filters( 'rtwpvg_thumbnails_columns', 4 ) ) ),
							'id'       => 'thumbnails_columns_sm',
							'min'      => 2,
							'max'      => 8,
							'step'     => 1,
						],
						[
							'title'    => esc_html__( 'Thumbnails Items per row / slider view (Small Device)', 'woo-product-variation-gallery' ),
							'type'     => 'number',
							'default'  => absint( apply_filters( 'rtwpvg_sm_thumbnails_columns', 3 ) ),
							'desc_tip' => esc_html__( 'Product Thumbnails Item Image For (Small Device)', 'woo-product-variation-gallery' ),
							/* translators: %s: Default value */
							'desc'     => sprintf( esc_html__( 'Product Thumbnails Item Image. Default value is: %d. Limit: 2-8. The field required for slider.', 'woo-product-variation-gallery' ), absint( apply_filters( 'rtwpvg_thumbnails_columns', 4 ) ) ),
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
							/* translators: %s: margin */
							'desc'     => sprintf( esc_html__( 'Slider gallery bottom margin in pixel. Default value is: %d. Limit: 10-100.', 'woo-product-variation-gallery' ), apply_filters( 'gallery_margin', 30 ) ),
							'id'       => 'gallery_margin',
							'min'      => 10,
							'max'      => 100,
							'step'     => 1,
							'suffix'   => 'px',
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
				'desc'   => esc_html__( 'Advanced change some visual styles', 'woo-product-variation-gallery' ),
				'fields' => apply_filters(
					'rtwpvg_advanced_setting_fields',
					[
						[
							'title'   => esc_html__( 'Zoom Gallery image', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'id'      => 'zoom',
						],
						[
							'title'   => esc_html__( 'LightBox', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'id'      => 'lightbox',
						],
						[
							'title' => esc_html__( 'LightBox on image click', 'woo-product-variation-gallery' ),
							'type'  => 'switch',
							'id'    => 'lightbox_image_click',
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
							'title'   => esc_html__( 'Thumbnail Style', 'woo-product-variation-gallery' ),
							'id'      => 'thumbnail_position',
							'is_pro'  => true,
							'type'    => 'select',
							'default' => 'bottom',
							'options' => apply_filters(
								'rtwpvg_thumbnail_style',
								[
									'bottom' => esc_html__( 'Position Bottom', 'woo-product-variation-gallery' ),
								]
							),
						],
						[
							'title'   => esc_html__( 'Gallery Thumbnail Size', 'woo-product-variation-gallery' ),
							'id'      => 'gallery_thumbnail_size',
							'type'    => 'select',
							'default' => 'bottom',
							'options' => Functions::only_registered_image_size(),
						],
						[
							'title'   => esc_html__( 'Slider Navigation (Arrow)', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'id'      => 'slider_arrow',
							'is_pro'  => true,
						],
						[
							'title'   => esc_html__( 'Slider Adaptive Height', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'id'      => 'slider_adaptive_height',
						],
						[
							'title'   => esc_html__( 'Featured Image Remove', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => false,
							'desc'    => esc_html__( 'Featured image and thumbnail remove from slider when choose swathes', 'woo-product-variation-gallery' ),
							'id'      => 'remove_featured_thumbnail',
						],

						[
							'title'   => esc_html__( 'Thumbnail Slider', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'id'      => 'thumbnail_slide',
							'is_pro'  => true,
						],
						[
							'title'   => esc_html__( 'Preloader', 'woo-product-variation-gallery' ),
							'type'    => 'switch',
							'default' => true,
							'id'      => 'preloader',
						],
						[
							'id'    => 'preloader_image',
							'type'  => 'image',
							'title' => esc_html__( 'Preloader Image', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'Here you can change by default preloader image', 'woo-product-variation-gallery' ),
						],
						[
							'title'   => esc_html__( 'Gallery Change Effect', 'woo-product-variation-gallery' ),
							'type'    => 'select',
							'default' => 'blur',
							'id'      => 'preload_style',
							'options' => [
								'blur' => esc_html__( 'Blur', 'woo-product-variation-gallery' ),
								'fade' => esc_html__( 'Fade', 'woo-product-variation-gallery' ),
								'gray' => esc_html__( 'Gray', 'woo-product-variation-gallery' ),
							],
						],
						[
							'title'   => esc_html__( 'Zoom Button Position', 'woo-product-variation-gallery' ),
							'type'    => 'select',
							'default' => 'top-right',
							'id'      => 'zoom_position',
							'options' => [
								'top-right'    => esc_html__( 'Top right', 'woo-product-variation-gallery' ),
								'top-left'     => esc_html__( 'Top left', 'woo-product-variation-gallery' ),
								'bottom-right' => esc_html__( 'Bottom right', 'woo-product-variation-gallery' ),
								'bottom-left'  => esc_html__( 'Bottom left', 'woo-product-variation-gallery' ),
							],
						],
					]
				),
			],
			'style'           => [
				'id'     => 'style',
				'title'  => esc_html__( 'Style', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Style change some visual styles', 'woo-product-variation-gallery' ),
				'active' => apply_filters( 'rtwpvg_style_setting_active', false ),
				'fields' => apply_filters(
					'rtwpvg_style_setting_fields',
					[
						[
							'id'      => 'arrow_bg_color',
							'is_pro'  => true,
							'type'    => 'color',
							'title'   => esc_html__( 'Arrow background', 'woo-product-variation-gallery' ),
							'default' => 'rgba(0, 0, 0, 0.5)',
							'alpha'   => true,
						],
						[
							'id'      => 'arrow_bg_hover_color',
							'is_pro'  => true,
							'type'    => 'color',
							'title'   => esc_html__( 'Arrow background hover', 'woo-product-variation-gallery' ),
							'default' => 'rgba(0, 0, 0, 0.9)',
						],
						[
							'id'      => 'arrow_text_color',
							'is_pro'  => true,
							'type'    => 'color',
							'default' => '#ffffff',
							'title'   => esc_html__( 'Arrow text color', 'woo-product-variation-gallery' ),
						],
						[
							'id'      => 'arrow_text_hover_color',
							'is_pro'  => true,
							'type'    => 'color',
							'title'   => esc_html__( 'Arrow text hover color', 'woo-product-variation-gallery' ),
							'default' => '#ffffff',
						],
					]
				),
			],
			'tools'           => [
				'id'     => 'tools',
				'title'  => esc_html__( 'Tools', 'woo-product-variation-gallery' ),
				'desc'   => esc_html__( 'Tools define some system tasks', 'woo-product-variation-gallery' ),
				'active' => apply_filters( 'rtwpvg_tools_setting_active', false ),
				'fields' => apply_filters(
					'rtwpvg_tools_setting_fields',
					[
						[
							'id'    => 'remove_all_data',
							'type'  => 'switch',
							'title' => esc_html__( 'Enable to delete all data', 'woo-product-variation-gallery' ),
							'desc'  => esc_html__( 'Enable / Disable Allow to delete all data for WooCommerce Product variation Gallery plugin during delete this plugin', 'woo-product-variation-gallery' ),
						],
						[
							'id'      => 'upgrade_slider_scripts',
							'type'    => 'switch',
							'title'   => esc_html__( 'Upgrade Slider Scripts ', 'woo-product-variation-gallery' ) . '<span style="color:red">(New)</span>',
							'desc'    => '<span style="color:red">' . __( 'Upgrade slider script from Slick Carousel to Swiper Slider. Uncheck if you don\'t want to upgrade slider library. Note: Slick will be phased out in the future.', 'woo-product-variation-gallery' ) . '</span>',
							'default' => true,
						],
						[
							'id'      => 'load_scripts',
							'type'    => 'checkbox',
							'title'   => esc_html__( 'Load Scripts', 'woo-product-variation-gallery' ),
							'desc'    => __( 'Load Scripts to all over the site. [if checked then it will load the scripts to all over the site]', 'woo-product-variation-gallery' ),
							'default' => false,
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

		return apply_filters( 'rtwpvg_settings_fields', $fields );
	}
}
