(() => {//#region src/js/frontend.js
(function($) {
	"use strict";
	var rtwpvgInstanceUid = 0;
	/**
	* @param $slider
	* @constructor
	*/
	$.fn.rtWpVGallery = function() {
		if (this.length > 1) {
			this.each(function() {
				$(this).rtWpVGallery();
			});
			return this;
		}
		this._item = $(this);
		if (this._item.data("rtwpvg_flicker_fix_initialized")) return this;
		this._item.data("rtwpvg_flicker_fix_initialized", true);
		this._ns = this._item.data("rtwpvg_ns");
		if (!this._ns) {
			this._ns = ".rtwpvg_" + ++rtwpvgInstanceUid;
			this._item.data("rtwpvg_ns", this._ns);
		}
		this._item.data("rtwpvg_instance", this);
		this._target = this._item.parent();
		this._product = this._item.closest(".product");
		this._default_gallery_images = this.data("default-images") || [];
		this._variation_form = this._product.find(".variations_form");
		this._product_id = this._variation_form.data("product_id");
		this._is_variation_product = !!this._variation_form.length;
		this._variation_gallery_cache = {};
		this._currentVariationId = 0;
		this._loadedImageIds = null;
		this._galleryReady = false;
		this._pendingFocusImageId = 0;
		this._resetTimer = null;
		this._rtwpvgUserInteracted = false;
		this._is_bundle_product = this._item.hasClass("rtwpvg-product-type-bundle");
		this._slider = $(".rtwpvg-slider", this._item);
		this._thumbnail = $(".rtwpvg-thumbnail-slider", this._item);
		this.initial_load = true;
		this.thumbSliderOptionsValue = this._thumbnail.data("options") ? this._thumbnail.data("options") : {};
		this.mainSliderOptionsValue = this._slider.data("options") ? this._slider.data("options") : {};
		this.sliderThumbnailPosition = this._item.data("thumbnail_position");
		this.isSlide_vertical = "left" === this.sliderThumbnailPosition || "right" === this.sliderThumbnailPosition;
		this.is_vertical = !!this.thumbSliderOptionsValue.vertical;
		var _thumbSlideData = this._item.data("thumbnail_slide");
		this.enable_thumbnail_slide = typeof _thumbSlideData !== "undefined" ? !!parseInt(_thumbSlideData) : !!rtwpvg.enable_thumbnail_slide;
		this._grid = $(".rtwpvg-grid-layout", this._item);
		this.is_grid_layout = "grid" === this.sliderThumbnailPosition;
		this.slider_enabled = "function" === typeof Swiper;
		this.defaultOptions = {
			observer: true,
			speed: 400
		};
		this.removeLoading = function() {
			const that = this;
			setTimeout(function() {
				that._item.removeClass("loading-rtwpvg");
				that._thumbnail.closest(".rtwpvg-thumbnail-wrapper").css("visibility", "visible");
			}, 300);
		};
		this.addLoading = function() {
			if (this._rtwpvgUserInteracted) this._item.addClass("loading-rtwpvg");
		};
		this.loadDefaultGalleryImages = function() {
			const that = this;
			if (this._is_variation_product && !this._default_gallery_images.length) {
				let that = this;
				wp.ajax.send("rtwpvg_get_default_gallery_images", {
					data: { product_id: this._product_id },
					success: function success(data) {
						that._default_gallery_images = data;
						that._item.trigger("rtwpvg_default_gallery_image_loaded");
					},
					error: function error(e) {
						that._default_gallery_images = [];
						that._item.trigger("rtwpvg_default_gallery_image_loaded");
					}
				});
			}
			setTimeout(function() {
				that.loadZoom(that._slider.find(".swiper-wrapper"));
			}, 150);
		};
		this.initSlider = function() {
			const that = this;
			if (!this.slider_enabled) return;
			this.$mainSlider = this._slider;
			this.$slider = this.$mainSlider.get(0);
			this.$mainThumbnail = this._thumbnail;
			this.$thumbnail = this.$mainThumbnail ? this.$mainThumbnail.get(0) : null;
			var existingMainSwiper = this.$slider ? this.$slider.swiper : null;
			var existingThumbSwiper = this.$thumbnail ? this.$thumbnail.swiper : null;
			if (existingMainSwiper) existingMainSwiper.destroy(true, false);
			if (existingThumbSwiper) existingThumbSwiper.destroy(true, false);
			this.swiperThumbnailSlider = null;
			if (this.enable_thumbnail_slide) {
				const thumbSliderOptions = Object.assign({}, this.defaultOptions, this.thumbSliderOptionsValue || {});
				if (this.$thumbnail) this.swiperThumbnailSlider = new Swiper(this.$thumbnail, thumbSliderOptions);
			}
			this.swiperSlider = null;
			const mainSliderOptions = Object.assign({}, this.defaultOptions, this.mainSliderOptionsValue || {});
			if (!this.swiperThumbnailSlider) delete mainSliderOptions.thumbs;
			else mainSliderOptions.thumbs = { swiper: this.swiperThumbnailSlider };
			if (this.$slider) this.swiperSlider = new Swiper(this.$slider, mainSliderOptions);
			if (this.enable_thumbnail_slide && this.swiperThumbnailSlider && this.swiperSlider) {
				this._thumbnail.find(".rtwpvg-thumbnail-image").off("click.rtwpvg-thumb");
				this._thumbnail.find(".rtwpvg-thumbnail-image").on("click.rtwpvg-thumb", function(event) {
					var index = $(this).index();
					if (that.swiperSlider && typeof that.swiperSlider.slideTo === "function") that.swiperSlider.slideTo(index, mainSliderOptions.speed);
				});
			}
			if (this.isSlide_vertical && this.enable_thumbnail_slide) {
				var setVerticalHeight = function() {
					var configured = that.getSlidesPerView();
					var gap = that.swiperThumbnailSlider && that.swiperThumbnailSlider.params ? that.swiperThumbnailSlider.params.spaceBetween || 0 : parseInt(that._thumbnail.data("options")?.spaceBetween) || 0;
					var $slides = that._thumbnail.find(".swiper-slide");
					if (!$slides.length || !configured) return;
					var imageCount = $slides.length;
					var visibleCount = Math.min(configured, imageCount);
					var $mainImg = that._slider.find(".swiper-slide-active img").first();
					if (!$mainImg.length) $mainImg = that._slider.find(".swiper-slide img").first();
					var mainHeight = 0;
					if ($mainImg.length && $mainImg[0].naturalWidth > 0) mainHeight = that._slider.width() * ($mainImg[0].naturalHeight / $mainImg[0].naturalWidth);
					if (!mainHeight) mainHeight = $mainImg.outerHeight() || that._slider.outerHeight();
					if (mainHeight > 0) {
						var columnHeight = (mainHeight - gap * (configured - 1)) / configured * visibleCount + gap * (visibleCount - 1);
						that._thumbnail.height(Math.ceil(columnHeight));
						if (that.swiperThumbnailSlider && that.swiperThumbnailSlider.params && !that.swiperThumbnailSlider.destroyed) {
							that.swiperThumbnailSlider.params.slidesPerView = visibleCount;
							that.swiperThumbnailSlider.update();
						}
					}
				};
				that._item.imagesLoaded(function() {
					setVerticalHeight();
				});
				setVerticalHeight();
				if (that._verticalRO) {
					that._verticalRO.disconnect();
					that._verticalRO = null;
				}
				if ("undefined" !== typeof ResizeObserver && that._slider.get(0)) {
					that._lastVerticalWidth = Math.round(that._slider.width());
					that._verticalRO = new ResizeObserver(function() {
						var width = Math.round(that._slider.width());
						if (!width || width === that._lastVerticalWidth) return;
						that._lastVerticalWidth = width;
						setVerticalHeight();
					});
					that._verticalRO.observe(that._slider.get(0));
				}
			}
			if (!this.enable_thumbnail_slide) {
				this._thumbnail.addClass("loaded-thumbnail-no-slider");
				that.swiperSlider.slideTo(0, mainSliderOptions.speed);
				this._thumbnail.not(".swiper-initialized").find(".rtwpvg-thumbnail-image").each(function(i, item) {
					$(item).find("div, img").on("click", function(event) {
						that._thumbnail.find(".rtwpvg-thumbnail-image").removeClass("swiper-slide-thumb-active");
						event.preventDefault();
						event.stopPropagation();
						that.swiperSlider.slideTo(i, mainSliderOptions.speed);
						$(item).addClass("swiper-slide-thumb-active");
					});
				});
				this._thumbnail.not(".swiper-initialized").find(".rtwpvg-thumbnail-image").first().addClass("swiper-slide-thumb-active");
				if (this.isSlide_vertical) {
					var _thumbEl = this._thumbnail.get(0);
					if (_thumbEl) _thumbEl.scrollTop = 0;
					this.setDeactiveThumbnailHeight();
					this.initDragScroll(this._thumbnail, "vertical");
				}
			}
			this._galleryReady = true;
			if (this._pendingFocusImageId) {
				var pendingId = this._pendingFocusImageId;
				this._pendingFocusImageId = 0;
				this.slideToImageId(pendingId);
			}
		};
		this.loadSlider = function() {
			const that = this;
			this.initSlider();
			setTimeout(function() {
				that._item.trigger("rtwpvg_slider_init");
			}, 1);
		};
		this.initDragScroll = function($el, direction) {
			var el = $el.get(0);
			if (!el) return;
			var isDown = false, startPos, scrollStart;
			var isVertical = direction === "vertical";
			var $slider = $el.closest(".rtwpvg-thumbnail-slider");
			var updateScrollIndicator = function() {
				if (!isVertical) return;
				var scrollTop = el.scrollTop;
				var scrollHeight = el.scrollHeight - el.clientHeight;
				$slider.removeClass("rtwpvg-scroll-top rtwpvg-scroll-middle rtwpvg-scroll-bottom");
				if (scrollHeight <= 0) return;
				if (scrollTop <= 1) $slider.addClass("rtwpvg-scroll-top");
				else if (scrollTop >= scrollHeight - 1) $slider.addClass("rtwpvg-scroll-bottom");
				else $slider.addClass("rtwpvg-scroll-middle");
			};
			el.addEventListener("scroll", updateScrollIndicator);
			updateScrollIndicator();
			el.addEventListener("mousedown", function(e) {
				isDown = true;
				el.style.cursor = "grabbing";
				startPos = isVertical ? e.pageY - el.offsetTop : e.pageX - el.offsetLeft;
				scrollStart = isVertical ? el.scrollTop : el.scrollLeft;
			});
			el.addEventListener("mouseleave", function() {
				isDown = false;
				el.style.cursor = "grab";
			});
			el.addEventListener("mouseup", function() {
				isDown = false;
				el.style.cursor = "grab";
			});
			el.addEventListener("mousemove", function(e) {
				if (!isDown) return;
				e.preventDefault();
				var pos = isVertical ? e.pageY - el.offsetTop : e.pageX - el.offsetLeft;
				var diff = scrollStart - (pos - startPos);
				if (isVertical) el.scrollTop = diff;
				else el.scrollLeft = diff;
			});
			el.style.cursor = "grab";
		};
		this.stopVideo = function(item) {
			$(item).find("iframe, video").each(function() {
				let tag = $(this).prop("tagName").toLowerCase();
				if (tag === "iframe") {
					let src = $(this).attr("src");
					$(this).attr("src", src);
				}
				if (tag === "video") $(this)[0].pause();
			});
		};
		this.setThumbnailMaxHeight = function() {
			if (this._slider.length > 0 && this.isSlide_vertical && this.enable_thumbnail_slide) {
				var that = this;
				var slidesPerView;
				var gap;
				if (this.swiperThumbnailSlider) {
					slidesPerView = this.swiperThumbnailSlider.params.slidesPerView || this.getSlidesPerView();
					gap = this.swiperThumbnailSlider.params.spaceBetween || 0;
				} else {
					slidesPerView = this.getSlidesPerView();
					gap = parseInt(this._thumbnail.data("options")?.spaceBetween) || 0;
				}
				var $slides = this._thumbnail.find(".swiper-slide");
				var visibleCount = Math.min(slidesPerView, $slides.length);
				var totalHeight = 0;
				for (var i = 0; i < visibleCount; i++) {
					var $img = $slides.eq(i).find("img");
					totalHeight += $img.length ? $img.outerHeight() : $slides.eq(i).outerHeight();
				}
				totalHeight += gap * (visibleCount - 1);
				if (totalHeight > 0) that._thumbnail.height(Math.ceil(totalHeight));
			}
		};
		/**
		* Get slidesPerView from data-options breakpoints for current screen width.
		*
		* @return {number}
		*/
		this.getSlidesPerView = function() {
			var breakpoints = (this._thumbnail.data("options") || {}).breakpoints || {};
			var keys = Object.keys(breakpoints).map(Number).sort(function(a, b) {
				return a - b;
			});
			var winWidth = $(window).width();
			var slidesPerView = parseInt(rtwpvg.thumbnails_columns) || 4;
			for (var i = keys.length - 1; i >= 0; i--) if (winWidth >= keys[i]) {
				slidesPerView = parseInt(breakpoints[keys[i]].slidesPerView) || slidesPerView;
				break;
			}
			return slidesPerView;
		};
		/**
		* Set thumbnail slider height based on visible items when slider is deactive.
		*/
		this.setDeactiveThumbnailHeight = function() {
			var that = this;
			var gap = parseInt(this._thumbnail.data("options")?.spaceBetween) || 0;
			var $slides = this._thumbnail.find(".swiper-slide");
			var calcHeight = function() {
				var slidesPerView = that.getSlidesPerView();
				var totalHeight = 0;
				var visibleCount = Math.min(slidesPerView, $slides.length);
				for (var i = 0; i < visibleCount; i++) totalHeight += $slides.eq(i).outerHeight(true);
				totalHeight += gap * (visibleCount - 1);
				if (totalHeight > 0) that._thumbnail.css("max-height", totalHeight + "px");
			};
			that._item.imagesLoaded(function() {
				calcHeight();
			});
		};
		this.loadZoom = function(currentSlide) {
			if (!rtwpvg.enable_zoom) return;
			let galleryWidth, zoomEnabled = false, zoomTarget;
			if (this.is_grid_layout) zoomTarget = this._grid.find(".rtwpvg-gallery-image");
			else zoomTarget = currentSlide.find(".rtwpvg-gallery-image");
			$(zoomTarget).each(function(index, element) {
				galleryWidth = $(this).width();
				let image = $(this).find(".rtwpvg-single-image-container img");
				if (parseInt(image.data("large_image_width")) > galleryWidth) {
					zoomEnabled = true;
					return false;
				}
			});
			if (!$.fn.zoom) return;
			if (zoomEnabled) {
				let zoom_options = $.extend({ touch: false }, wc_single_product_params.zoom_options);
				if ("ontouchstart" in document.documentElement) zoom_options.on = "click";
				zoomTarget.trigger("zoom.destroy");
				zoomTarget.zoom(zoom_options);
			}
		};
		this.loadPhotoSwipe = function() {
			let that = this;
			if (!rtwpvg.enable_lightbox) return;
			this._item.off("click", ".rtwpvg-trigger");
			this._item.on("click", ".rtwpvg-trigger", function(event) {
				that.openPhotoSwipe(event);
			});
			if (rtwpvg.lightbox_image_click) {
				this._item.off("click", ".rtwpvg-gallery-image");
				if (this.is_grid_layout) this._item.off("click", ".rtwpvg-trigger");
				this._item.on("click", ".rtwpvg-gallery-image", function(event) {
					that.openPhotoSwipe(event);
				});
			}
		};
		this.openPhotoSwipe = function(event) {
			event.preventDefault();
			if (typeof PhotoSwipe === "undefined") return false;
			const that = this;
			let pswpElement = $(".pswp")[0], items = this.getGalleryItems();
			let options = $.extend({ index: this._slider.get(0)?.swiper.activeIndex }, wc_single_product_params.photoswipe_options);
			if (that.is_grid_layout) {
				let current_click = $(event.target).parents(".rtwpvg-gallery-image").find(".rtwpvg-single-image-container img").data("src");
				options.index = items.findIndex(function(item) {
					return item.src === current_click;
				});
			}
			let photoSwipe = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
			photoSwipe.listen("close", function() {
				that.stopVideo(pswpElement);
			});
			photoSwipe.listen("afterChange", function() {
				that.stopVideo(pswpElement);
			});
			photoSwipe.init();
		};
		this.getGalleryItems = function() {
			let items = [];
			let _slides = this._item.find(".rtwpvg-gallery-image");
			if (_slides.length > 0) _slides.each(function(i, el) {
				let img = $(el).find("img, iframe, video");
				let tag = $(img).prop("tagName").toLowerCase();
				let src = void 0, item = void 0;
				switch (tag) {
					case "img":
						item = {
							src: img.attr("data-large_image"),
							w: img.attr("data-large_image_width"),
							h: img.attr("data-large_image_height"),
							title: img.attr("data-caption") ? img.attr("data-caption") : img.attr("title")
						};
						break;
					case "iframe":
						src = img.attr("src");
						item = { html: "<iframe class=\"rtwpvg-lightbox-iframe\" src=\"" + src + "\" style=\"width: 100%; height: 100%; margin: 0;padding: 0; background-color: #000000\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>" };
						break;
					case "video":
						src = img.attr("src");
						item = { html: "<video class=\"rtwpvg-lightbox-video\" controls controlsList=\"nodownload\" src=\"" + src + "\" style=\"width: 100%; height: 100%; margin: 0;padding: 0; background-color: #000000\"></video>" };
				}
				items.push(item);
			});
			return items;
		};
		/**
		* Map a gallery image-props array to a flat array of numeric image ids.
		*
		* @param {Array} images
		* @return {Array<number>}
		*/
		this.getImageIds = function(images) {
			if (!Array.isArray(images)) return [];
			return images.map(function(img) {
				return parseInt(img && img.image_id ? img.image_id : 0, 10);
			}).filter(function(id) {
				return id > 0;
			});
		};
		/**
		* True when both arrays hold the same image ids, compared as unique sets
		* (order- and duplicate-insensitive). The server markup can render the same
		* id twice (e.g. the variation's featured image also appears in its gallery),
		* whereas the AJAX gallery is de-duplicated — so a plain length/element
		* comparison would wrongly report a difference and force a rebuild.
		*
		* @param {Array<number>} a
		* @param {Array<number>} b
		* @return {boolean}
		*/
		this.sameImageSet = function(a, b) {
			if (!Array.isArray(a) || !Array.isArray(b) || a.length === 0 || b.length === 0) return false;
			var uniqSort = function(arr) {
				return arr.filter(function(v, i) {
					return arr.indexOf(v) === i;
				}).sort(function(x, y) {
					return x - y;
				});
			};
			var sa = uniqSort(a);
			var sb = uniqSort(b);
			if (sa.length !== sb.length) return false;
			for (var i = 0; i < sa.length; i++) if (sa[i] !== sb[i]) return false;
			return true;
		};
		/**
		* Read the image ids currently rendered in the DOM. Thumbnails carry a
		* consistent `rtwpvg-thumbnail-image-{id}` class across server- and
		* JS-rendered markup; the main slider is used as a fallback.
		*
		* @return {Array<number>}
		*/
		this.getRenderedImageIds = function() {
			var ids = [];
			var $thumbs = this._thumbnail.find(".rtwpvg-thumbnail-image").not(".swiper-slide-duplicate");
			if ($thumbs.length) $thumbs.each(function() {
				var m = ($(this).attr("class") || "").match(/rtwpvg-thumbnail-image-(\d+)/);
				if (m) ids.push(parseInt(m[1], 10));
			});
			else this._slider.find(".rtwpvg-gallery-image").not(".swiper-slide-duplicate").each(function() {
				var m = ($(this).attr("class") || "").match(/rtwpvg-gallery-image-(?:id-)?(\d+)/);
				if (m) ids.push(parseInt(m[1], 10));
			});
			return ids;
		};
		/**
		* Move the already-rendered gallery to the slide holding the given image
		* id and highlight its thumbnail, without rebuilding anything.
		*
		* @param {number} imageId
		* @return {boolean} Whether a matching slide was found.
		*/
		this.slideToImageId = function(imageId) {
			imageId = parseInt(imageId, 10);
			if (!imageId) return false;
			var index = -1;
			var $thumbs = this._thumbnail.find(".rtwpvg-thumbnail-image").not(".swiper-slide-duplicate");
			$thumbs.each(function(i) {
				if ($(this).hasClass("rtwpvg-thumbnail-image-" + imageId)) {
					index = i;
					return false;
				}
			});
			if (index < 0) this._slider.find(".rtwpvg-gallery-image").not(".swiper-slide-duplicate").each(function(i) {
				if ($(this).hasClass("rtwpvg-gallery-image-" + imageId) || $(this).hasClass("rtwpvg-gallery-image-id-" + imageId)) {
					index = i;
					return false;
				}
			});
			if (index < 0) return false;
			if (this.swiperSlider && typeof this.swiperSlider.slideTo === "function") this.swiperSlider.slideTo(index, this.mainSliderOptionsValue.speed || this.defaultOptions.speed || 400);
			if (!this.enable_thumbnail_slide) {
				$thumbs.removeClass("swiper-slide-thumb-active");
				$thumbs.eq(index).addClass("swiper-slide-thumb-active");
			}
			return true;
		};
		this.loadGallery = function(images, opts) {
			const that = this;
			opts = opts || {};
			var newIds = this.getImageIds(images);
			if (this._loadedImageIds === null) this._loadedImageIds = this.getRenderedImageIds();
			if (this.slider_enabled && this.sameImageSet(newIds, this._loadedImageIds)) {
				if (opts.focusImageId) {
					if (this._galleryReady) this.slideToImageId(opts.focusImageId);
					else this._pendingFocusImageId = opts.focusImageId;
				}
				this.removeLoading();
				this._item.trigger("rtwpvg_gallery_reused", [images, that]);
				return;
			}
			that.addLoading();
			let hasGallery = images.length > 1;
			this._item.trigger("before_rtwpvg_load", [images]);
			let thumbnail_html = "";
			if (this._slider.length) {
				let slider_html = images.map(function(image) {
					if (!image.image_id) return "";
					return wp.template("rtwpvg-slider-template")(image);
				}).join("");
				thumbnail_html = images.map(function(image) {
					if (!image.image_id) return "";
					return wp.template("rtwpvg-thumbnail-template")(image);
				}).join("");
				this._slider.find(".swiper-wrapper").html(slider_html);
				if (hasGallery) {
					this._target.addClass("rtwpvg-has-product-thumbnail");
					this._thumbnail.find(".swiper-wrapper").html(thumbnail_html);
					if (this.enable_thumbnail_slide) {
						var thumbOpts = this._thumbnail.data("options") || {};
						var gap = parseInt(thumbOpts.spaceBetween) || 0;
						var $newSlides = this._thumbnail.find(".swiper-slide");
						if (gap > 0) {
							var marginProp = "vertical" === thumbOpts.direction || this.isSlide_vertical ? "margin-bottom" : thumbOpts.rtl ? "margin-left" : "margin-right";
							$newSlides.css({
								"margin-right": "",
								"margin-bottom": "",
								"margin-left": ""
							});
							$newSlides.css(marginProp, gap + "px");
						}
						if (!this.isSlide_vertical) {
							var cols = parseInt(rtwpvg.thumbnails_columns) || 4;
							var slideWidth = "calc((100% - " + gap + "px * " + (cols - 1) + ") / " + cols + ")";
							$newSlides.css("width", slideWidth);
						}
					}
					this._thumbnail.parents(".rtwpvg-images").removeClass("rtwpvg-no-gallery-images");
					this._thumbnail.parents(".rtwpvg-images").addClass("rtwpvg-has-gallery-images");
				} else {
					this._target.removeClass("rtwpvg-has-product-thumbnail");
					this._thumbnail.find(".swiper-wrapper").html("");
					this._thumbnail.parents(".rtwpvg-images").removeClass("rtwpvg-has-gallery-images");
					this._thumbnail.parents(".rtwpvg-images").addClass("rtwpvg-no-gallery-images");
				}
				setTimeout(function() {
					that.loadZoom(that._slider.find(".swiper-wrapper"));
				}, 100);
			}
			if (this.is_grid_layout) {
				let grid_html = images.map(function(image) {
					return wp.template("rtwpvg-template-grid-layout")(image);
				}).join("");
				this._grid.html(grid_html);
				setTimeout(function() {
					that.loadZoom(grid_html);
				}, 1);
			}
			this._loadedImageIds = newIds;
			setTimeout(function() {
				that.imagesLoaded();
				that.hasVideo();
			}, 1);
		};
		this.resetGallery = function() {
			if (this._default_gallery_images.length > 0) {
				var ids = this.getImageIds(this._default_gallery_images);
				this.loadGallery(this._default_gallery_images, { focusImageId: ids.length ? ids[0] : 0 });
			}
		};
		this.imagesLoaded = function() {
			var that = this;
			if ($.fn.imagesLoaded.done) {
				this._item.trigger("rtwpvg_image_loading", [that]);
				this._item.trigger("rtwpvg_image_loaded", [that]);
				return;
			}
			this._item.imagesLoaded().progress(function(instance, image) {
				that._item.trigger("rtwpvg_image_loading", [that]);
			}).done(function(instance) {
				that._item.trigger("rtwpvg_image_loaded", [that]);
			});
		};
		this.loadVariationGallery = function() {
			const that = this;
			const ns = this._ns;
			var boundInstances = this._variation_form.data("rtwpvg_bound_instances") || [];
			boundInstances = boundInstances.filter(function(entry) {
				if (entry.ns === ns) return false;
				if (entry.el && $.contains(document.documentElement, entry.el)) return true;
				that._variation_form.off(entry.ns);
				return false;
			});
			boundInstances.push({
				ns,
				el: this._item.get(0)
			});
			this._variation_form.data("rtwpvg_bound_instances", boundInstances);
			this._variation_form.off("reset_image" + ns);
			this._variation_form.off("click" + ns);
			this._variation_form.off("show_variation" + ns);
			this._variation_form.off("change.rtwpvg_flicker_fix" + ns);
			this._variation_form.on("change.rtwpvg_flicker_fix" + ns, "select,input", function() {
				that._rtwpvgUserInteracted = true;
			});
			var scheduleReset = function() {
				if (!that._rtwpvgUserInteracted) return;
				clearTimeout(that._resetTimer);
				that._resetTimer = setTimeout(function() {
					that._currentVariationId = 0;
					that.resetGallery();
				}, 60);
			};
			if (rtwpvg.reset_on_variation_change) this._variation_form.on("reset_image" + ns, function(event) {
				scheduleReset();
			});
			else this._variation_form.on("click" + ns, ".show_variation", function(event) {
				that._rtwpvgUserInteracted = true;
				scheduleReset();
			});
			this._variation_form.on("click" + ns, ".reset_variations", function() {
				that._rtwpvgUserInteracted = true;
				clearTimeout(that._resetTimer);
				that._currentVariationId = 0;
				that.resetGallery();
			});
			this._variation_form.on("show_variation" + ns, function(event, variation) {
				clearTimeout(that._resetTimer);
				that.loadVariationGalleryImages(variation);
			});
		};
		/**
		* Load a variation's gallery on demand.
		*
		* Gallery image props are no longer embedded in the page for every
		* variation. They are fetched per selected variation via AJAX and cached
		* client-side so re-selecting the same variation does not refetch.
		*
		* @param {Object} variation The variation object from the show_variation event.
		*/
		this.loadVariationGalleryImages = function(variation) {
			const that = this;
			const variationId = variation && variation.variation_id ? variation.variation_id : 0;
			const featuredId = variation && variation.image_id ? parseInt(variation.image_id, 10) : 0;
			that._currentVariationId = variationId;
			if (variation && variation.variation_gallery_images && variation.variation_gallery_images.length) {
				that.loadGallery(variation.variation_gallery_images, { focusImageId: featuredId });
				return;
			}
			if (!variationId) {
				that.resetGallery();
				return;
			}
			if (that._variation_gallery_cache[variationId]) {
				that.loadGallery(that._variation_gallery_cache[variationId], { focusImageId: featuredId });
				return;
			}
			that.addLoading();
			wp.ajax.send("rtwpvg_get_variation_gallery", {
				data: {
					variation_id: variationId,
					product_id: that._product_id
				},
				success: function(images) {
					that._variation_gallery_cache[variationId] = images;
					if (that._currentVariationId !== variationId) return;
					that.loadGallery(images, { focusImageId: featuredId });
				},
				error: function() {
					if (that._currentVariationId !== variationId) return;
					that.resetGallery();
				}
			});
		};
		this.hasVideo = function() {
			if (this._item.find(".rtwpvg-thumbnail-video").length) this._item.addClass("rtwpvg-video-full-height");
			else this._item.removeClass("rtwpvg-video-full-height");
		};
		this.loadEvents = function() {
			this._item.on("rtwpvg_image_loaded", this.init.bind(this));
		};
		this.init = function(e) {
			let that = this;
			setTimeout(function() {
				if (that._slider.length) that.loadSlider();
				if (that.is_grid_layout) that.loadZoom(that.is_grid_layout);
				that.loadPhotoSwipe();
				that.setThumbnailHeight();
				that._item.imagesLoaded(function() {
					that.removeLoading();
				});
			}, 10);
		};
		this.setThumbnailHeight = function() {
			var $wrapper = this._thumbnail.closest(".rtwpvg-thumbnail-wrapper");
			if (!$wrapper.length) return;
			if (this.isSlide_vertical) return;
			this._item.imagesLoaded(function() {
				var height = $wrapper.outerHeight();
				if (height > 0) $wrapper.css("min-height", height);
			});
		};
		this.start = function() {
			if (this._is_bundle_product) this._product_id = this._variation_form.data("bundle_id");
			this.loadDefaultGalleryImages();
			this.loadEvents();
			if (!this.is_variation_product || this._is_bundle_product) this.imagesLoaded();
			if (!this._is_bundle_product) this.loadVariationGallery();
			this.init();
			$(document).trigger("rtwpvg_loaded");
		};
		this.start();
		return this;
	};
	function rtwpvgInitGalleries() {
		$(".rtwpvg-wrapper:not(.rtwpvg-product-type-variable), .rtwpvg-grid-wrapper").rtWpVGallery();
		$(".rtwpvg-wrapper.rtwpvg-product-type-variable").each(function() {
			var $wrapper = $(this);
			if (!$wrapper.closest(".product").find(".variations_form").length) $wrapper.rtWpVGallery();
		});
	}
	$(window).on("load", rtwpvgInitGalleries);
	$(window).on("elementor/frontend/init", function() {
		if (typeof elementorFrontend === "undefined" || !elementorFrontend.hooks) return;
		$.each([
			"woocommerce-product-images.default",
			"woocommerce-product-add-to-cart.default",
			"wc-single-elements.default"
		], function(index, widget) {
			elementorFrontend.hooks.addAction("frontend/element_ready/" + widget, rtwpvgInitGalleries);
		});
	});
	$(document).on("wc_variation_form", ".variations_form", function() {
		$(".rtwpvg-wrapper, .rtwpvg-grid-wrapper").rtWpVGallery();
	});
	$(document.body).on("post-load", function() {
		$(".rtwpvg-wrapper").rtWpVGallery();
	});
	$(document.body).on("jckqv_open", function() {
		$(".rtwpvg-wrapper").rtWpVGallery();
	});
	$(document.body).on("quick-view-displayed", function() {
		$(".rtwpvg-wrapper").rtWpVGallery();
	});
	$(document).on("qv_loader_stop", function() {
		$(".rtwpvg-wrapper:not(.rtwpvg-product-type-variable)").rtWpVGallery();
	});
	$(document).on("rtsbQv.success", function() {
		$(".rtwpvg-wrapper").rtWpVGallery();
	});
})(jQuery);
//#endregion
})();