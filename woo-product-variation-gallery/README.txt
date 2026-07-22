=== Variation Images Gallery for WooCommerce ===
Contributors: techlabpro1, mamunnu
Tags: product variation gallery, woocommerce variation image gallery, additional variation image gallery, product variation image gallery, product variation image
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.4.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Variation Images Gallery for WooCommerce plugin allows to add UNLIMITED additional images for each variation of product.

== Description ==

[Variation Images Gallery for WooCommerce](https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/) plugin allows to add UNLIMITED additional images for each variation of product.

This additional variation images gallery plugin gives your customers an extra feature to showcase your products that gives visitors to multiple view of your products.  This will help to increase your sales.

👉 [Plugin Demo](https://radiustheme.com/demo/wooplugins/variation-gallery) | [Documentation](https://www.radiustheme.com/docs/variation-gallery/) | [Get Pro](https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/?utm_source=WordPress&utm_medium=gallery&utm_campaign=pro_click) 👈

[youtube https://www.youtube.com/watch?v=zQKKUx2ECa8]

By default, [WooCommerce](https://wordpress.org/plugins/woocommerce/) has only single image to insert as variation there is not gallery or multiple image add option.

This additional variation image gallery for WooCommerce plugin give you the opportunity to showcase additional images as gallery for each product variation. This plugin allows you to upload unlimited additional images for each variation. By using this plugin, you can showcase different set of images when your visitors switch product variation like color, image, style at the same time.

> [Click here to get Metro - Minimal WooCommerce WordPress Theme with Variation Swatches PRO & Variation images gallery PRO ](https://www.radiustheme.com/downloads/metro-minimal-woocommerce-wordpress-theme/)

This plugin is easy to use and included detail settings there is no limit to use this free version. This plugin will give your WooCoomerce store a new look to your visitors to showcase your variation images.

== 🚀 Features ==
* UNLIMITED additional images for each product variation
* Drag & Drop custom sorting option.
* Delete option for Variation images.
* Zoom option for variation images.
* Zoom button position control option.
* Light box for variation images.
* Compatible with Flatsome Theme Builder Product Gallery Element.
* Compatible with Elementor Page Builder Product Gallery Addon.
* Compatible with [Variation Swatches for WooCommerce](https://wordpress.org/plugins/woo-product-variation-swatches/) plugin.

== Pro Features ==
* Thumbnail Slider.
* Thumbnail position (Left/ Right/ Bottom)
* Support Video at gallery

== Need Any Help? ==
* For any bug, support or suggestion please submit your ticket [here](https://www.radiustheme.com/ticket-support/).

== Liked RadiusTheme ==
* Join our [Facebook Group](https://www.facebook.com/groups/radiustheme).
* Learn from our tutorials on [YouTube Channel](https://www.youtube.com/@RadiusTheme).

== 🔥 WHAT’S NEXT ==

If you like The Post Grid Plugin, then consider checking out our other WordPress Plugins:

* [ShopBuilder](https://wordpress.org/plugins/shopbuilder/) - Elementor WooCommerce Builder Addons with 84+ widgets and 10+ modules.

* [Variation Swatches](https://wordpress.org/plugins/woo-product-variation-swatches/) - Woocommerce Variation Swatches plugin converts the product variation select fields into radio, images, colors, and labels.

* [The Post Grid](https://wordpress.org/plugins/the-post-grid/) – Shortcode, Gutenberg Blocks and Elementor Addon for Post Grid.

* [Classified Lisitng](https://wordpress.org/plugins/classified-listing/) – Best Classified ads and Directory WordPress Plugin.

* [Food Menu](https://wordpress.org/plugins/tlp-food-menu/) – Restaurant Menu & Online Ordering using WooCommerce.


== Frequently Asked Questions ==

= Need Any Help? =

= How to Use Variation Images Gallery for WooCommerce =

* Go to `WooCommerce > settings > Variation Swatches`

= Is it compatible with any kinds of WooCommerce Theme? =

Yes, it is compatible with Most popular WooCommerce themes.

= Does it show in product QuickView? =

Yes, it supports any kinds of product quick view.

= Does it work on MultiSite? =

Yes, it is.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Variation Images Gallery by RadiusTheme'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `woo-product-variation-gallery.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `woo-product-variation-gallery.zip`
2. Extract the `woo-product-variation-gallery` directory to your computer
3. Upload the `woo-product-variation-gallery` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

= Requirements =
* **WordPress version:** >= 5.5
* **PHP version:** >= 7.4

== Screenshots ==
1. Variation Image Gallery
2. Image Gallery Right Side
3. Image Gallery Left Side
4. Image Gallery Grid Layout
5. Support Video Gallery
6. General Settings
7. Advanced Settings
8. Style Settings
9. Tools Settings

== Changelog ==


= 2.4.2 (Jul 22, 2026) =
Changed: Renamed filter `disable_woo_variation_gallery` to `rtwpvg_disable_variation_gallery`; old name deprecated but still works.
Changed: Renamed filter `rtvg_pro_label` to `rtwpvg_pro_label`; old name deprecated but still works.
Changed: Renamed filter `gallery_margin` to `rtwpvg_gallery_margin`; old name deprecated but still works.
Fixed: Gallery AJAX endpoints no longer expose images of unpublished (draft/pending/private) products to unauthorized users. Thanks to Que Thanh Tuan for the responsible disclosure.

= 2.4.1 (Jul 19, 2026) =
Fixed: Restored `dirname()` method so older addons no longer trigger a fatal error.
Fixed: Featured/main image no longer renders twice when it is also added to the product/variation gallery.

= 2.4.0 (Jul 16, 2026) =
Improved: Major UI upgrade — variation switching is now smooth and seamless, with an eased gallery transition, no thumbnail gap jump, and a transparent preloader that also shows with the "No effect" style (when the preloader is enabled).
Fixed: Featured/main image no longer renders twice in the gallery when it is also added to the product gallery.
Improved: Clicking "Clear" now resets the gallery to the default images and selects the main product image.
Fixed: Gap beside fallback image for variations without a gallery.
Changed: Moved plugin constants to the main file and removed redundant getter methods.
Changed: Removed unused helper methods and dead code.
Added: "No effect" option for Gallery Change Effect, now the default.
Improved: Skip gallery re-render/re-init when a variation reuses the loaded gallery; just slide to its image.
Fixed: Duplicate server-rendered image ids no longer force a rebuild when a variation reuses the same image set.
Fixed: Thumbnail item gap now applied before the carousel initialises, preventing a shift on variation change.

= 2.3.26 (Jun 22, 2026) =
Fixed: Gallery flicker on initial page load caused by the forced reload of product variations.
Fixed: Gallery reset and loading overlay now stay suppressed until the shopper actually changes a variation.
Fixed: Prevented double-initialisation of the same gallery instance.

[See changelog for all versions.](https://raw.githubusercontent.com/radiustheme/changelog/refs/heads/main/woo-product-variation-gallery.txt)


== Upgrade Notice ==