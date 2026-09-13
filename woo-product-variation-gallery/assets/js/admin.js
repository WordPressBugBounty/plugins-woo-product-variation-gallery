(() => {//#region src/js/admin.js
(function($) {
	"use strict";
	function rtwpvg_refresh_tooltips() {
		$(document.body).trigger("init_tooltips");
	}
	function isVersionCompatible(current, minimum) {
		const cur = current.split(".").map(Number);
		const min = minimum.split(".").map(Number);
		for (let i = 0; i < Math.max(cur.length, min.length); i++) {
			const c = cur[i] || 0;
			const m = min[i] || 0;
			if (c > m) return true;
			if (c < m) return false;
		}
		return true;
	}
	/**
	* Read an attachment ID as a number.
	*
	* WooCommerce stores "no variation image" as `_thumbnail_id` = `0`, so the
	* rendered `upload_image_id` input carries the string `"0"` — truthy in JS.
	* Every ID read goes through here so an unset image is never mistaken for a
	* real attachment.
	*
	* @param {jQuery|String|Number} value Input holding the ID, or the ID itself.
	*
	* @return {Number} Positive attachment ID, or 0 when unset.
	*/
	function attachmentId(value) {
		const raw = value && value.jquery ? value.val() : value;
		const id = parseInt(raw, 10);
		return id > 0 ? id : 0;
	}
	/**
	* Placeholder image WooCommerce renders in an empty variation image slot.
	*
	* Localised by core on the product editor; falls back to an empty string so a
	* missing global never writes `undefined` into a `src`.
	*
	* @return {String}
	*/
	function placeholderImgSrc() {
		return typeof woocommerce_admin_meta_boxes_variations !== "undefined" && woocommerce_admin_meta_boxes_variations.woocommerce_placeholder_img_src || "";
	}
	function imageUploader() {
		$(document).off("click", ".rtwpvg-add-image");
		$(document).off("click", ".rtwpvg-gallery-edit");
		$(document).off("click", ".rtwpvg-media-video-popup");
		$(document).on("click", ".rtwpvg-add-image", addImage);
		$(document).on("click", ".rtwpvg-remove-image", removeImage);
		$(document).on("click", ".rtwpvg-gallery-edit", galleryEdit);
		$(document).on("click", ".rtwpvg-media-video-popup", addMediaVideo);
		$(".woocommerce_variation").each(function() {
			let optionsWrapper = $(this).find(".options");
			$(this).find(".rtwpvg-gallery-wrapper").insertBefore(optionsWrapper);
		});
		injectVariationImageActions();
	}
	function addImage(event) {
		event.preventDefault();
		event.stopPropagation();
		const that = this;
		let file_frame = 0;
		let product_variation_id = $(this).data("product_variation_id");
		let loop = $(this).data("product_variation_loop");
		let _prev_image = $(this).parents(".rtwpvg-gallery-wrapper").find("input").map(function() {
			return Number($(this).val());
		}).get();
		console.log(_prev_image);
		if (typeof wp !== "undefined" && wp.media && wp.media.editor) {
			if (file_frame) {
				file_frame.open();
				return;
			}
			file_frame = wp.media.frames.select_image = wp.media({
				title: rtwpvg_admin.choose_image,
				button: { text: rtwpvg_admin.add_image },
				library: { type: ["image"] },
				multiple: true
			});
			file_frame.on("select", function() {
				let html = file_frame.state().get("selection").toJSON().map(function(image) {
					if (image.type === "image") {
						console.log(image);
						if (_prev_image.indexOf(image.id) === -1) {
							let id = image.id, rtwpvg_video_link = image.rtwpvg_video_link, image_sizes = image.sizes;
							image_sizes = image_sizes === void 0 ? {} : image_sizes;
							let thumbnail = image_sizes.thumbnail, full = image_sizes.full;
							let url = thumbnail ? thumbnail.url : full.url;
							return wp.template("rtwpvg-image")({
								id,
								url,
								product_variation_id,
								loop,
								rtwpvg_video_link
							});
						} else alert("Cannot add duplicate items.");
					}
				}).join("");
				$(that).parent().prev().find(".rtwpvg-images").append(html);
				sortable();
				variationChanged(that);
			});
			file_frame.open();
		}
	}
	function addMediaVideo(e) {
		e.preventDefault();
		e.stopPropagation();
		const imgList = $(e.currentTarget).parents("li.image");
		const imageId = imgList.find("input").val();
		if (!imageId) return;
		openVideoModal(imageId, imgList);
	}
	/**
	* Open the "add video" modal for an attachment.
	*
	* Video meta is stored on the attachment itself (rtwpvg_video_link / _width /
	* _height), not on the gallery slot, so the same modal serves both the gallery
	* thumbnails and the variation's main image.
	*
	* @param {Number|String} imageId    Attachment ID the video is attached to.
	* @param {jQuery|null}   $indicator Element carrying the `video` state class,
	*                                   or null when the caller shows no indicator.
	*/
	function openVideoModal(imageId, $indicator) {
		var attachment = wp.media.attachment(imageId);
		const proVersion = rtwpvg_admin?.pro_version || null;
		const minProVersion = "2.3.12";
		const hasProAndCompatible = proVersion && isVersionCompatible(proVersion, minProVersion);
		attachment.fetch().done(function() {
			var data = attachment.toJSON();
			const videoLink = data?.rtwpvg_video_link || "";
			const videoWidth = data?.rtwpvg_video_width || "";
			const videoHeight = data?.rtwpvg_video_height || "";
			var modalHtml = `
            <div class="custom-edit-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; display: flex; align-items: center; justify-content: center;">
                <div class="modal-inner-wrapper" style="background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%;">
                    <div class="modal-form-section" >
                        <h2 style="margin-top:0">Poster Image</h2>
                        <hr/>
                        <div class="image-wrap" style="display: flex;justify-content: center;"><img src="${data.url}" style="max-width: 250px; height: auto; margin-bottom: 20px;"></div>
                        ${!hasProAndCompatible ? `
                        <div style="background: #fef7e0; border: 1px solid #e0c97d; color: #7a6000; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px;">
                            Video options are available in the <a href="https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/" style="color: red;font-size: 16px" target="_blank"><strong>Pro version</strong></a> <br/> (minimum version ${minProVersion})
                        </div>
                        ` : ""}
                        <label style="display: block; margin-bottom: 15px;">
                            <strong>Video Url: </strong>
                            <input ${hasProAndCompatible ? "" : "disabled"} type="text" id="rtwpvg_video_link" value="${videoLink}" placeholder="https://www.youtube.com/watch?v=zQKKUx2ECa8" style="width: 100%; padding: 8px; margin-top: 5px;">
                            <p class="help">You can add a YouTube, Vimeo, TikTok, or uploaded video. <b>Example: https://www.youtube.com/watch?v=zQKKUx2ECa8</b> <br/> <a href="${rtwpvg_admin.admin_url}upload.php?mode=grid&attachment-filter=post_mime_type%3Avideo" target="_blank">Upload your video <span class="dashicons dashicons-video-alt3"></span></a></p>
                        </label>
                        <label style="display: block; margin-bottom: 15px;">
                            <strong>Video Width: </strong>
                            <input ${hasProAndCompatible ? "" : "disabled"} type="text" id="rtwpvg_video_width" value="${videoWidth}" style="width: 100%; padding: 8px; margin-top: 5px;">
                            <p class="help">Video Width. px or %. Empty for default. <b>Example: 575px</b> </p>
                        </label>
                        <label style="display: block; margin-bottom: 15px;">
                            <strong>Video Height: </strong>
                            <input ${hasProAndCompatible ? "" : "disabled"} type="text" id="rtwpvg_video_height" value="${videoHeight}" style="width: 100%; padding: 8px; margin-top: 5px;">
                            <p class="help">Video Height. px or %. Empty for default. <b>Example: 550px</b></p>
                        </label>
                    </div>
                     <hr/>
                    <div style="text-align: right;margin-top:15px">
                        <button class="button" id="cancel-edit" style="margin-right: 10px;">Cancel</button>
                        <button class="button button-primary" id="save-edit">Update</button>
                    </div>
                </div>
            </div>`;
			$("body").append(modalHtml);
			$("#cancel-edit").on("click", function() {
				$(".custom-edit-modal").remove();
			});
			$("#save-edit").on("click", function() {
				const videoLinkField = $("body").find("#rtwpvg_video_link");
				const videoWidthField = $("body").find("#rtwpvg_video_width");
				const videoHeightField = $("body").find("#rtwpvg_video_height");
				if (!videoLinkField.length || !videoWidthField.length || !videoHeightField.length) {
					alert("Required video fields are missing. Please reload the page.");
					return;
				}
				if (rtwpvg_admin?.pro_version) $.ajax({
					url: ajaxurl,
					type: "POST",
					data: {
						action: "rtwpvg_update_attachment_video_meta",
						attachment_id: imageId,
						video_link: videoLinkField.val(),
						video_width: videoWidthField.val(),
						video_height: videoHeightField.val(),
						_wpnonce: rtwpvg_admin.nonce || ""
					},
					success: function(response) {
						if ($indicator && $indicator.length) $indicator.toggleClass("video", !!response?.data?.hasVideo);
						$("body").find(".modal-inner-wrapper").html(response?.data?.message);
						if (response?.success) setTimeout(function() {
							$("body").find(".custom-edit-modal").remove();
						}, 800);
						console.log(response?.data?.message);
					}
				});
				else alert("It's Pro Feature");
			});
		}).fail(function() {
			console.error("Failed to load attachment");
		});
	}
	function galleryEdit(event) {
		event.preventDefault();
		event.stopPropagation();
		const imgList = $(event.currentTarget).parents("li.image");
		const imageIdField = imgList.find("input");
		const imageId = attachmentId(imageIdField);
		const frame = wp.media({
			title: "Edit Image",
			button: { text: "Update Image" },
			library: { type: "image" },
			multiple: false
		});
		frame.on("open", function() {
			if (!imageId) return;
			const selection = frame.state().get("selection");
			const attachment = wp.media.attachment(imageId);
			attachment.fetch().fail(function() {
				selection.reset();
			});
			selection.add(attachment);
		});
		frame.on("select", function() {
			const attachment = frame.state().get("selection").first().toJSON();
			const thumbUrl = attachment.sizes?.thumbnail?.url || attachment.url;
			const img = imgList.find("img");
			if (attachment?.rtwpvg_video_link) imgList.addClass("video");
			else imgList.removeClass("video");
			if (img.length) img.attr("src", thumbUrl);
			imageIdField.val(attachment.id);
		});
		frame.open();
		variationChanged(this);
	}
	function removeImage(event) {
		event.preventDefault();
		event.stopPropagation();
		let that = this;
		variationChanged(this);
		setTimeout(function() {
			$(that).parents("li.image").remove();
		}, 1);
	}
	/**
	* Inject the hover action overlay into each variation's main image slot.
	*
	* The slot itself is WooCommerce core markup (`.form-row.upload_image`), so the
	* overlay is added from JS rather than through
	* `woocommerce_variation_after_upload_image` — that hook only exists from
	* WooCommerce 10.8, while this plugin supports 3.2+.
	*/
	function injectVariationImageActions() {
		$("#variable_product_options").find(".form-row.upload_image").each(function() {
			const $button = $(this).find(".upload_image_button").first();
			if (!$button.length || $button.find(".rtwpvg-variation-image-actions").length) return;
			const $actions = $("<div class=\"rtwpvg-media-actions rtwpvg-variation-image-actions\"><span class=\"rtwpvg-variation-image-video woocommerce-help-tip dashicons dashicons-video-alt3\" data-tip=\"Add Video\"></span><span class=\"rtwpvg-variation-image-edit woocommerce-help-tip dashicons dashicons-edit\" data-tip=\"Edit Image\"></span><a href=\"#\" class=\"rtwpvg-variation-image-remove woocommerce-help-tip\" data-tip=\"Remove\"><span class=\"dashicons dashicons-no\"></span></a></div>");
			$actions.find(".rtwpvg-variation-image-video").on("click", variationImageVideo);
			$actions.find(".rtwpvg-variation-image-edit").on("click", variationImageEdit);
			$actions.find(".rtwpvg-variation-image-remove").on("click", variationImageRemove);
			$button.on("click", variationImageAnchorClick);
			$button.attr("data-tip", "Edit Image");
			$button.closest(".form-flex-box").addClass("rtwpvg-variation-image-layout");
			const heroSrc = $button.closest(".woocommerce_variation").find(".rtwpvg-gallery-wrapper").data("hero-src");
			const $image = $button.find("img").eq(0);
			if (heroSrc) $image.attr("src", heroSrc).removeAttr("srcset sizes");
			else if (!$image.attr("src")) $image.attr("src", placeholderImgSrc()).removeAttr("srcset sizes");
			$button.append($actions);
		});
		syncVariationImageActions();
	}
	/**
	* Gate clicks on the variation image itself.
	*
	* @param {Object} event
	*/
	function variationImageAnchorClick(event) {
		const $button = $(event.currentTarget);
		if ($button.data("rtwpvgAllow")) {
			$button.removeData("rtwpvgAllow");
			return;
		}
		event.preventDefault();
		event.stopPropagation();
		openVariationImageFrame($button);
	}
	/**
	* Align the overlay and WooCommerce's own button state with the stored image ID.
	*
	* Core decides between "open the picker" and "clear the image" purely from the
	* `remove` class, so it has to match the hidden input after every action —
	* including a media frame the user cancelled.
	*/
	function syncVariationImageActions() {
		$("#variable_product_options").find(".form-row.upload_image").each(function() {
			const $row = $(this);
			const $actions = $row.find(".rtwpvg-variation-image-actions");
			if (!$actions.length) return;
			const hasImage = attachmentId($row.find(".upload_image_id")) > 0;
			$actions.toggleClass("is-empty", !hasImage);
			$row.find(".upload_image_button").toggleClass("remove", hasImage);
		});
	}
	function variationImageVideo(event) {
		event.preventDefault();
		event.stopPropagation();
		const imageId = attachmentId($(event.currentTarget).closest(".upload_image").find(".upload_image_id"));
		if (!imageId) return;
		openVideoModal(imageId, null);
	}
	function variationImageEdit(event) {
		event.preventDefault();
		event.stopPropagation();
		const $button = $(event.currentTarget).closest(".upload_image").find(".upload_image_button").first();
		if (!$button.length) return;
		openVariationImageFrame($button);
	}
	/**
	* Open the media library to pick a replacement for the variation image.
	*
	* Deliberately our own frame rather than a synthetic click on core's handler:
	* core keys "open picker" off the ABSENCE of the `remove` class, but that same
	* class is what makes the image visible (`.upload_image_button.remove img`), so
	* toggling it would blank the thumbnail for as long as the modal is open.
	*
	* @param {jQuery} $button The `.upload_image_button` anchor.
	*/
	function openVariationImageFrame($button) {
		const $input = $button.closest(".upload_image").find(".upload_image_id");
		const imageId = attachmentId($input);
		const frame = wp.media({
			title: rtwpvg_admin.choose_image,
			button: { text: rtwpvg_admin.add_image },
			library: { type: "image" },
			multiple: false
		});
		frame.on("open", function() {
			if (!imageId) return;
			const selection = frame.state().get("selection");
			const attachment = wp.media.attachment(imageId);
			attachment.fetch().fail(function() {
				selection.reset();
			});
			selection.add(attachment);
		});
		frame.on("select", function() {
			const attachment = frame.state().get("selection").first().toJSON();
			const thumbUrl = attachment.sizes?.woocommerce_single?.url || attachment.sizes?.thumbnail?.url || attachment.url;
			$input.val(attachment.id).trigger("change");
			$button.find("img").eq(0).attr("src", thumbUrl);
			syncVariationImageActions();
		});
		frame.open();
	}
	function variationImageRemove(event) {
		event.preventDefault();
		event.stopPropagation();
		const $button = $(event.currentTarget).closest(".upload_image").find(".upload_image_button").first();
		if (!$button.length) return;
		$button.data("rtwpvgAllow", true).trigger("click");
		syncVariationImageActions();
	}
	/**
	* Build the shared "Add Video / Edit Image" overlay.
	*
	* @return {jQuery}
	*/
	function buildMediaActions() {
		return $("<div class=\"rtwpvg-media-actions\"><span class=\"rtwpvg-media-video woocommerce-help-tip dashicons dashicons-video-alt3\" data-tip=\"Add Video\"></span><span class=\"rtwpvg-media-edit woocommerce-help-tip dashicons dashicons-edit\" data-tip=\"Edit Image\"></span></div>");
	}
	/**
	* Add the overlay to the product's featured image box.
	*
	* That box is WordPress core's `#postimagediv`; WooCommerce only relabels its
	* strings. Removal already exists there as "Remove product image", so only the
	* video and edit actions are added.
	*/
	function injectFeaturedImageActions() {
		const $wrap = $("#postimagediv").find("#set-post-thumbnail");
		if (!$wrap.length || !$wrap.find("img").length || $wrap.find(".rtwpvg-media-actions").length) return;
		const $actions = buildMediaActions();
		$actions.find(".rtwpvg-media-video").on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			const imageId = attachmentId($("#_thumbnail_id"));
			if (!imageId) return;
			openVideoModal(imageId, null);
		});
		$actions.find(".rtwpvg-media-edit").on("click", function(event) {
			event.preventDefault();
			event.stopPropagation();
			$wrap.trigger("click");
		});
		$wrap.append($actions);
	}
	/**
	* Add the overlay to each item in the product gallery metabox.
	*
	* Items carry their attachment ID on `data-attachment_id`, and already ship a
	* Delete action of their own, so only video and edit are added.
	*/
	function injectProductGalleryActions() {
		if ($("#product_media_gallery").length) return;
		$("#product_images_container").find("ul.product_images > li.image").each(function() {
			const $item = $(this);
			if ($item.find(".rtwpvg-media-actions").length) return;
			const $actions = buildMediaActions();
			$actions.find(".rtwpvg-media-video").on("click", function(event) {
				event.preventDefault();
				event.stopPropagation();
				const imageId = $item.attr("data-attachment_id");
				if (!imageId) return;
				openVideoModal(imageId, null);
			});
			$actions.find(".rtwpvg-media-edit").on("click", function(event) {
				event.preventDefault();
				event.stopPropagation();
				openProductGalleryFrame($item);
			});
			$item.append($actions);
		});
	}
	/**
	* Replace a single product gallery image.
	*
	* @param {jQuery} $item The `li.image` being edited.
	*/
	function openProductGalleryFrame($item) {
		const imageId = $item.attr("data-attachment_id");
		const frame = wp.media({
			title: rtwpvg_admin.choose_image,
			button: { text: rtwpvg_admin.add_image },
			library: { type: "image" },
			multiple: false
		});
		frame.on("open", function() {
			if (imageId) {
				const selection = frame.state().get("selection");
				const attachment = wp.media.attachment(imageId);
				attachment.fetch();
				selection.add(attachment);
			}
		});
		frame.on("select", function() {
			const attachment = frame.state().get("selection").first().toJSON();
			const thumbUrl = attachment.sizes?.thumbnail?.url || attachment.url;
			$item.attr("data-attachment_id", attachment.id);
			$item.find("img").eq(0).attr("src", thumbUrl).removeAttr("srcset sizes");
			syncProductGalleryField();
		});
		frame.open();
	}
	/**
	* Rebuild the hidden field that actually persists the gallery.
	*
	* WooCommerce does this in `updateProductGalleryFields()`, but that lives in a
	* closure and is bound as a sortable option, so it cannot be invoked from here.
	* This mirrors the image-only branch of that function.
	*/
	function syncProductGalleryField() {
		const ids = $("#product_images_container").find("ul.product_images > li.image").map(function() {
			return $(this).attr("data-attachment_id");
		}).get().filter(Boolean);
		$("#product_image_gallery").val(ids.join(","));
	}
	/**
	* Re-inject after WooCommerce or WordPress rebuilds either area.
	*
	* Gallery items are appended by core's media frame and the featured image box is
	* replaced wholesale over AJAX; neither exposes an event to hook, so the DOM is
	* observed instead.
	*/
	function watchMediaAreas() {
		if (typeof MutationObserver === "undefined") return;
		const targets = [document.getElementById("product_images_container"), document.getElementById("postimagediv")].filter(Boolean);
		if (!targets.length) return;
		const observer = new MutationObserver(function() {
			injectFeaturedImageActions();
			injectProductGalleryActions();
		});
		targets.forEach(function(target) {
			observer.observe(target, {
				childList: true,
				subtree: true
			});
		});
	}
	function variationChanged(element) {
		$(element).closest(".woocommerce_variation").addClass("variation-needs-update");
		$("button.cancel-variation-changes, button.save-variation-changes").removeAttr("disabled");
		$("#variable_product_options").trigger("woocommerce_variations_input_changed");
	}
	function sortable() {
		$(".rtwpvg-images").sortable({
			items: "li.image",
			cursor: "move",
			scrollSensitivity: 40,
			forcePlaceholderSize: true,
			forceHelperSize: false,
			helper: "clone",
			opacity: .65,
			placeholder: "rtwpvg-sortable-placeholder",
			start: function start(event, ui) {
				ui.item.css("background-color", "#f6f6f6");
			},
			stop: function stop(event, ui) {
				ui.item.removeAttr("style");
			},
			update: function update() {
				variationChanged(this);
			}
		});
	}
	$(document).on("change", "#variable_product_options .upload_image_id", function() {
		syncVariationImageActions();
	});
	$("#woocommerce-product-data").on("woocommerce_variations_loaded", function() {
		imageUploader();
		sortable();
	});
	$("#variable_product_options").on("woocommerce_variations_added", function() {
		imageUploader();
		sortable();
	});
	$("#woocommerce-product-images .add_product_images").on("click", "a", function(event) {});
	$(function() {
		injectFeaturedImageActions();
		injectProductGalleryActions();
		watchMediaAreas();
		$("#woocommerce-product-data").on("woocommerce_variations_loaded woocommerce_variations_added", function() {
			rtwpvg_refresh_tooltips();
		});
		rtwpvg_refresh_tooltips();
	});
})(jQuery);
//#endregion
})();