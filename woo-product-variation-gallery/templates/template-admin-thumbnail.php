<?php
/**
 * Admin Thumbnail js template
 *
 * This template can be overridden by copying it to
 * yourtheme/woo-product-variation-gallery-pro/template-admin-thumbnail.php
 */

defined( 'ABSPATH' ) || exit;
?>
<script type="text/html" id="tmpl-rtwpvg-image">
	<# hasVideo = ( typeof data.rtwpvg_video_link !== 'undefined' && data.rtwpvg_video_link != ''  ) ? 'video' : '';  #>
	<li class="image {{hasVideo}}">
		<input type="hidden" name="rtwpvg[{{data.product_variation_id}}][]" value="{{data.id}}">
		<img src="{{data.url}}">
		<div class="rtwpvg-action-button">
			<span  data-tip="Add Video" class="rtwpvg-media-video-popup dashicons dashicons-video-alt3" ></span>
			<span data-tip="Edit Image" class="rtwpvg-gallery-edit dashicons dashicons-edit"></span>
			<a data-tip="Remove" href="#" class="delete rtwpvg-remove-image">
				<span class="dashicons dashicons-no"></span>
			</a>
		</div>
	</li>
</script>