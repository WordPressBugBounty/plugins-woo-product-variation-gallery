(function ($) {
  'use strict';

  /**
   * @param $slider
   * @constructor
   */
  // Gallery js
  $.fn.rtWpVGallery = function () {
    this._item = $(this);
    this._target = this._item.parent();
    this._product = this._item.closest('.product');
    this._default_gallery_images = this.data('default-images') || [];
    this._variation_form = this._product.find('.variations_form');
    this._product_id = this._variation_form.data('product_id');
    this._is_variation_product = !!this._variation_form.length;
    this._is_bundle_product = this._item.hasClass('rtwpvg-product-type-bundle');
    this._slider = $('.rtwpvg-slider', this._item);
    this._thumbnail = $('.rtwpvg-thumbnail-slider', this._item);
    this.initial_load = true;
    this.thumbSliderOptionsValue = this._thumbnail.data('options') ? this._thumbnail.data('options') : {}; //console.log( this._thumbnail , this._thumbnail.data('swiper').vertical )

    this.mainSliderOptionsValue = this._slider.data('options') ? this._slider.data('options') : {}; //console.log( this._thumbnail , this._thumbnail.data('swiper').vertical )

    this.sliderThumbnailPosition = this._item.data('thumbnail_position');
    this.isSlide_vertical = 'left' === this.sliderThumbnailPosition || 'right' === this.sliderThumbnailPosition;
    this.is_vertical = !!this.thumbSliderOptionsValue.vertical;
    this._grid = $('.rtwpvg-grid-layout', this._item);
    this.is_grid_layout = 'grid' === this.sliderThumbnailPosition; // rtwpvg.thumbnail_position;

    this.slider_enabled = 'function' === typeof Swiper;
    this.defaultOptions = {
      observer: true,
      speed: 400
    };
    this.removeLoading = function () {
      this._item.removeClass('loading-rtwpvg');
    };
    this.addLoading = function () {
      this._item.addClass('loading-rtwpvg');
    };
    this.loadDefaultGalleryImages = function () {
      var that = this;
      if (this._is_variation_product && !this._default_gallery_images.length) {
        var _that = this;
        wp.ajax.send('rtwpvg_get_default_gallery_images', {
          data: {
            product_id: this._product_id
          },
          success: function success(data) {
            _that._default_gallery_images = data;
            _that._item.trigger('rtwpvg_default_gallery_image_loaded');
          },
          error: function error(e) {
            _that._default_gallery_images = [];
            _that._item.trigger('rtwpvg_default_gallery_image_loaded');
          }
        });
      }
      setTimeout(function () {
        that.loadZoom(that._slider.find('.swiper-wrapper'));
      }, 150);
    };
    this.initSlider = function () {
      var that = this;
      if (!this.slider_enabled) {
        return;
      }
      this.setThumbnailMaxHeight();
      this.swiperThumbnailSlider = null;
      if (rtwpvg.enable_thumbnail_slide) {
        var thumbSliderOptions = Object.assign({}, this.defaultOptions, this.thumbSliderOptionsValue || {});
        this.$mainThumbnail = this._thumbnail;
        this.$thumbnail = this.$mainThumbnail.get(0);
        if (this.$thumbnail) {
          this.swiperThumbnailSlider = this.$thumbnail.swiper || null;
        }
        if (this.swiperThumbnailSlider) {
          this.swiperThumbnailSlider.update();
        } else {
          this.swiperThumbnailSlider = new Swiper(this.$thumbnail, thumbSliderOptions);
        }
      }

      // Main Slider.
      this.$mainSlider = this._slider;
      this.$slider = this.$mainSlider.get(0);
      this.swiperSlider = null;
      var mainSliderOptions = Object.assign({}, this.defaultOptions, this.mainSliderOptionsValue || {});
      if (this.$slider) {
        this.swiperSlider = this.$slider.swiper || null;
      }
      if (this.swiperSlider) {
        this.swiperSlider.update();
      } else {
        if (!this.swiperThumbnailSlider) {
          delete mainSliderOptions.thumbs;
        } else {
          mainSliderOptions.thumbs = {
            swiper: this.swiperThumbnailSlider
          };
        }
        this.swiperSlider = new Swiper(this.$slider, mainSliderOptions);
      }
      if (!rtwpvg.enable_thumbnail_slide) {
        this._thumbnail.addClass('loaded-thumbnail-no-slider');
        that.swiperSlider.slideTo(0, mainSliderOptions.speed);
        this._thumbnail.not('.swiper-initialized').find('.rtwpvg-thumbnail-image').each(function (i, item) {
          $(item).find('div, img').on('click', function (event) {
            that._thumbnail.find('.rtwpvg-thumbnail-image').removeClass('swiper-slide-thumb-active');
            event.preventDefault();
            event.stopPropagation();
            that.swiperSlider.slideTo(i, mainSliderOptions.speed);
            $(item).addClass('swiper-slide-thumb-active');
          });
        });
        this._thumbnail.not('.swiper-initialized').find('.rtwpvg-thumbnail-image').first().addClass('swiper-slide-thumb-active');
      }
    };
    this.loadSlider = function () {
      var that = this;
      this.initSlider();
      setTimeout(function () {
        that._item.trigger('rtwpvg_slider_init');
      }, 1);
    };
    this.stopVideo = function (item) {
      $(item).find('iframe, video').each(function () {
        var tag = $(this).prop("tagName").toLowerCase();
        if (tag === 'iframe') {
          var src = $(this).attr('src');
          $(this).attr('src', src);
        }
        if (tag === 'video') {
          $(this)[0].pause();
        }
      });
    };
    this.setThumbnailMaxHeight = function () {
      if (this._slider.length > 0 && this.isSlide_vertical) {
        var maxHeight = 0;
        // Iterate through all images using the "img" selector
        this._slider.find('.rtwpvg-gallery-image').find('.rtwpvg-single-image-container img').each(function () {
          var currentHeight = $(this).height();
          // Check if the current image has a greater height
          if (currentHeight > maxHeight) {
            maxHeight = currentHeight;
          }
        });
        this._thumbnail.height(parseInt(maxHeight));
      }
    };
    this.loadZoom = function (currentSlide) {
      if (!rtwpvg.enable_zoom) {
        return;
      }
      var that = this;
      var galleryWidth,
        // = parseInt(this._target.width())
        zoomEnabled = false,
        zoomTarget;
      if (this.is_grid_layout) {
        zoomTarget = this._grid.find('.rtwpvg-gallery-image');
      } else {
        zoomTarget = currentSlide.find('.rtwpvg-gallery-image');
      }

      // console.log( zoomTarget )

      $(zoomTarget).each(function (index, element) {
        galleryWidth = $(this).width(); // = parseInt(this._target.width())
        var image = $(this).find('.rtwpvg-single-image-container img');
        // console.log( 'galleryWidth ', element, 'image ', parseInt(image.data('large_image_width')) )
        if (parseInt(image.data('large_image_width')) > galleryWidth) {
          zoomEnabled = true;
          return false;
        }
      });
      if (!$.fn.zoom) {
        return;
      }
      if (zoomEnabled) {
        var zoom_options = $.extend({
          touch: false
        }, wc_single_product_params.zoom_options);
        if ('ontouchstart' in document.documentElement) {
          zoom_options.on = 'click';
        }
        zoomTarget.trigger('zoom.destroy');
        zoomTarget.zoom(zoom_options);
        // Reset the zoom of the current image
        //const currentImage = this._slider.slick("getSlick").$slides.eq(currentSlide);
        // let mouseReenter = true;
        // // First Time mouseleave/mouseenter functionality Enable
        // $(zoomTarget).on('mousemove', function(event) {
        //     // Trigger mouse leave event on the current image
        //     if ( mouseReenter && that.isMouseOnElement(zoomTarget, event) ) {
        //        // zoomTarget.trigger('mouseleave');
        //        // console.log( 'First time mouseleave' )
        //         setTimeout(function () {
        //            // zoomTarget.trigger('mouseenter');
        //             mouseReenter = false;
        //            // console.log( 'First time mouseenter' )
        //         }, 1);
        //     }
        // });
      }
    };

    // Function to check if the mouse is on the specified element
    // this.isMouseOnElement = function (element, event) {
    //     let rect = element[0].getBoundingClientRect();
    //     let mouseX = event.clientX;
    //     let mouseY = event.clientY;
    //     return (
    //         mouseX >= rect.left &&
    //         mouseX <= rect.right &&
    //         mouseY >= rect.top &&
    //         mouseY <= rect.bottom
    //     );
    // }

    this.loadPhotoSwipe = function () {
      var that = this;
      if (!rtwpvg.enable_lightbox) {
        return;
      }
      this._item.off('click', '.rtwpvg-trigger');
      this._item.on('click', '.rtwpvg-trigger', function (event) {
        that.openPhotoSwipe(event);
      });
      if (rtwpvg.lightbox_image_click) {
        this._item.off('click', '.rtwpvg-gallery-image');
        if (this.is_grid_layout) {
          this._item.off('click', '.rtwpvg-trigger');
        }
        this._item.on('click', '.rtwpvg-gallery-image', function (event) {
          that.openPhotoSwipe(event);
        });
      }
    };
    this.openPhotoSwipe = function (event) {
      var _this$_slider$get;
      event.preventDefault();
      if (typeof PhotoSwipe === 'undefined') {
        return false;
      }
      var that = this;
      var pswpElement = $('.pswp')[0],
        items = this.getGalleryItems();
      var options = $.extend({
        index: (_this$_slider$get = this._slider.get(0)) === null || _this$_slider$get === void 0 ? void 0 : _this$_slider$get.swiper.activeIndex
      }, wc_single_product_params.photoswipe_options);
      if (that.is_grid_layout) {
        var current_click = $(event.target).parents('.rtwpvg-gallery-image').find('.rtwpvg-single-image-container img').data('src');
        var index = items.findIndex(function (item) {
          return item.src === current_click;
        });
        options.index = index;
      }
      var photoSwipe = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
      photoSwipe.listen('close', function () {
        that.stopVideo(pswpElement);
      });
      photoSwipe.listen('afterChange', function () {
        that.stopVideo(pswpElement);
      });
      photoSwipe.init();

      // if( that.is_grid_layout ) {
      //     let current_click = $(event.target).parents('.rtwpvg-gallery-image').find('.rtwpvg-single-image-container img').attr('src');
      //     let index = items.findIndex(function (item) {
      //         return item.src === current_click;
      //     });
      //     photoSwipe.goTo(index);
      // }
    };
    this.getGalleryItems = function () {
      var items = [];
      var _slides = this._item.find('.rtwpvg-gallery-image');
      if (_slides.length > 0) {
        _slides.each(function (i, el) {
          var img = $(el).find('img, iframe, video');
          var tag = $(img).prop("tagName").toLowerCase();
          var src = void 0,
            item = void 0;
          switch (tag) {
            case 'img':
              var large_image_src = img.attr('data-large_image'),
                large_image_w = img.attr('data-large_image_width'),
                large_image_h = img.attr('data-large_image_height');
              item = {
                src: large_image_src,
                w: large_image_w,
                h: large_image_h,
                title: img.attr('data-caption') ? img.attr('data-caption') : img.attr('title')
              };
              break;
            case 'iframe':
              src = img.attr('src');
              item = {
                html: '<iframe class="rtwpvg-lightbox-iframe" src="' + src + '" style="width: 100%; height: 100%; margin: 0;padding: 0; background-color: #000000" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>'
              };
              break;
            case 'video':
              src = img.attr('src');
              item = {
                html: '<video class="rtwpvg-lightbox-video" controls controlsList="nodownload" src="' + src + '" style="width: 100%; height: 100%; margin: 0;padding: 0; background-color: #000000"></video>'
              };
              break;
          }
          items.push(item);
        });
      }
      return items;
    };
    this.loadGallery = function (images) {
      var that = this;
      var hasGallery = images.length > 1;
      this._item.trigger('before_rtwpvg_load', [images]);
      var thumbnail_html = '';
      if (this._slider.length) {
        var slider_html = images.map(function (image) {
          if (!image.image_id) {
            return '';
          }
          var template = wp.template('rtwpvg-slider-template');
          return template(image);
        }).join('');
        thumbnail_html = images.map(function (image) {
          if (!image.image_id) {
            return '';
          }
          var template = wp.template('rtwpvg-thumbnail-template');
          return template(image);
        }).join('');
        this._slider.find('.swiper-wrapper').html(slider_html);
        if (hasGallery) {
          this._target.addClass('rtwpvg-has-product-thumbnail');
          this._thumbnail.find('.swiper-wrapper').html(thumbnail_html);
          this._thumbnail.parents('.rtwpvg-images').removeClass('rtwpvg-no-gallery-images');
          this._thumbnail.parents('.rtwpvg-images').addClass('rtwpvg-has-gallery-images');
          //rtwpvg-thumbnail-wrapper
        } else {
          this._target.removeClass('rtwpvg-has-product-thumbnail');
          this._thumbnail.find('.swiper-wrapper').html('');
          this._thumbnail.parents('.rtwpvg-images').removeClass('rtwpvg-has-gallery-images');
          this._thumbnail.parents('.rtwpvg-images').addClass('rtwpvg-no-gallery-images');
        }
        setTimeout(function () {
          that.loadZoom(that._slider.find('.swiper-wrapper'));
        }, 100);
      }
      if (this.is_grid_layout) {
        var grid_html = images.map(function (image) {
          var template = wp.template('rtwpvg-template-grid-layout');
          return template(image);
        }).join('');
        this._grid.html(grid_html);
        setTimeout(function () {
          that.loadZoom(grid_html);
        }, 1);
      }
      setTimeout(function () {
        that.imagesLoaded();
        that.hasVideo();
      }, 1);
      setTimeout(function () {
        that.removeLoading();
      }, 1);
    };
    this.resetGallery = function () {
      var that = this;
      if (this._default_gallery_images.length > 0) {
        this.loadGallery(this._default_gallery_images);
      }
      setTimeout(function () {
        that.removeLoading();
      }, 1);
    };
    this.imagesLoaded = function () {
      var that = this;
      if ($.fn.imagesLoaded.done) {
        this._item.trigger('rtwpvg_image_loading', [that]);
        this._item.trigger('rtwpvg_image_loaded', [that]);
        return;
      }
      this._item.imagesLoaded().progress(function (instance, image) {
        that._item.trigger('rtwpvg_image_loading', [that]);
      }).done(function (instance) {
        that._item.trigger('rtwpvg_image_loaded', [that]);
      });
    };
    this.loadVariationGallery = function () {
      var that = this;
      this._variation_form.off('reset_image.rtwpvg');
      this._variation_form.off('click.rtwpvg');
      this._variation_form.off('show_variation.rtwpvg');
      if (rtwpvg.reset_on_variation_change) {
        this._variation_form.on('reset_image.rtwpvg', function (event) {
          that.addLoading();
          that.resetGallery();
        });
      } else {
        this._variation_form.on('click.rtwpvg', '.show_variation', function (event) {
          that.addLoading();
          that.resetGallery();
        });
      }
      this._variation_form.on('show_variation.rtwpvg', function (event, variation) {
        that.addLoading();
        that.loadGallery(variation.variation_gallery_images);
        // that.loadSlider();
      });
    };
    this.hasVideo = function () {
      var findThevideo = this._item.find('.rtwpvg-thumbnail-video');
      if (findThevideo.length) {
        this._item.addClass('rtwpvg-video-full-height');
      } else {
        this._item.removeClass('rtwpvg-video-full-height');
      }
    };
    this.loadEvents = function () {
      this._item.on('rtwpvg_image_loaded', this.init.bind(this));
    };
    this.init = function (e) {
      var that = this;
      setTimeout(function () {
        if (that._slider.length) {
          that.loadSlider();
        }
        if (that.is_grid_layout) {
          that.loadZoom(that.is_grid_layout);
        }
        that.loadPhotoSwipe();
        that.removeLoading();
      }, 10);
    };
    this.start = function () {
      if (this._is_bundle_product) {
        this._product_id = this._variation_form.data('bundle_id');
      }
      this.loadDefaultGalleryImages();
      this.loadEvents();

      // if (  this.is_variation_product) {
      //     if( this._slider.length ){
      //        this.initSlider();
      //     }
      // }

      if (!this.is_variation_product || this._is_bundle_product) {
        this.imagesLoaded();
      }
      if (!this._is_bundle_product) {
        this.loadVariationGallery();
      }
      this.init();
      this._variation_form.trigger('reload_product_variations');
      $(document).trigger('rtwpvg_loaded');
    };
    this.start();
    return this;
  };
  $(window).on('load', function () {
    $('.rtwpvg-wrapper:not(.rtwpvg-product-type-variable), .rtwpvg-grid-wrapper').rtWpVGallery();
  });
  $(document).on('wc_variation_form', '.variations_form', function () {
    $('.rtwpvg-wrapper, .rtwpvg-grid-wrapper').rtWpVGallery();
  });

  // Support for Jetpack's Infinite Scroll,
  $(document.body).on('post-load', function () {
    $('.rtwpvg-wrapper').rtWpVGallery();
  });
  $(document.body).on('jckqv_open', function () {
    $('.rtwpvg-wrapper').rtWpVGallery();
  });

  // Support for WooCommerce Quick View
  $(document.body).on('quick-view-displayed', function () {
    $('.rtwpvg-wrapper').rtWpVGallery();
  });

  // YITH Quickview
  $(document).on('qv_loader_stop', function () {
    $('.rtwpvg-wrapper:not(.rtwpvg-product-type-variable)').rtWpVGallery();
  });
  // Support Shopbuilder Quick View
  $(document).on('rtsbQv.success', function () {
    $('.rtwpvg-wrapper').rtWpVGallery();
  });
})(jQuery);
