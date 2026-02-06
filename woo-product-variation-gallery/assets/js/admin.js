(function ($) {
  'use strict';

  if ($.fn.wpColorPicker) {
    $('input.rtwpvg-color-picker').wpColorPicker();
  }
  // If you append / update variation HTML in custom code, call this:
  function rtwpvg_refresh_tooltips() {
    $(document.body).trigger('init_tooltips');
  }
  $('#rtwpvg-settings-wrapper').on('click', '.nav-tab', function (event) {
    event.preventDefault();
    var self = $(this),
      target = self.data('target');
    self.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
    $('#' + target).show().siblings().hide();
    $('#_last_active_tab').val(target);
    if (history.pushState) {
      var newurl = setGetParameter('section', target);
      window.history.pushState({
        path: newurl
      }, '', newurl);
    }
  });
  /* Licence */
  $(".rtwpvg-setting-tab #license_key-wrapper").on('keyup', '#license_key-field', function (e) {
    e.preventDefault();
    $('.license-status').html('When add license key first click on Save changes');
  });
  $(".rtwpvg-setting-tab #license_key-wrapper").on('click', '.rt-licensing-btn', function (e) {
    e.preventDefault();
    console.log('clicked');
    var self = $(this),
      type = self.hasClass('license_activate') ? 'license_activate' : 'license_deactivate';
    $.ajax({
      type: "POST",
      url: rtwpvg_admin.ajaxurl,
      data: {
        action: 'rtwpvg_manage_licensing',
        type: type
      },
      beforeSend: function beforeSend() {
        self.addClass('loading');
        self.parents('.description').find(".rt-licence-msg").remove();
        $('<span class="rt-icon-spinner animate-spin"></span>').insertAfter(self);
      },
      success: function success(response) {
        self.next('.rt-icon-spinner').remove();
        self.removeClass('loading');
        if (!response.error) {
          self.text(response.value);
          self.removeClass(type);
          self.addClass(response.type);
          if (response.type == 'license_deactivate') {
            self.removeClass('button-primary');
            self.addClass('danger');
          } else if (response.type == 'license_activate') {
            self.removeClass('danger');
            self.addClass('button-primary');
          }
        }
        if (response.msg) {
          $("<span class='rt-licence-msg'>" + response.msg + "</span>").insertAfter(self);
        }
        self.blur();
      },
      error: function error(jqXHR, exception) {
        self.removeClass('loading');
        self.next('.rt-icon-spinner').remove();
      }
    });
  });

  // Helper function to compare versions
  function isVersionCompatible(current, minimum) {
    var cur = current.split('.').map(Number);
    var min = minimum.split('.').map(Number);
    for (var i = 0; i < Math.max(cur.length, min.length); i++) {
      var c = cur[i] || 0;
      var m = min[i] || 0;
      if (c > m) return true;
      if (c < m) return false;
    }
    return true;
  }
  function setGetParameter(paramName, paramValue) {
    var url = window.location.href;
    var hash = location.hash;
    url = url.replace(hash, '');
    if (url.indexOf("?") >= 0) {
      var params = url.substring(url.indexOf("?") + 1).split("&");
      var paramFound = false;
      params.forEach(function (param, index) {
        var p = param.split("=");
        if (p[0] == paramName) {
          params[index] = paramName + "=" + paramValue;
          paramFound = true;
        }
      });
      if (!paramFound) params.push(paramName + "=" + paramValue);
      url = url.substring(0, url.indexOf("?") + 1) + params.join("&");
    } else url += "?" + paramName + "=" + paramValue;
    return url + hash;
  }
  function imageUploader() {
    $(document).off('click', '.rtwpvg-add-image');
    $(document).off('click', '.rtwpvg-gallery-edit');
    $(document).off('click', '.rtwpvg-media-video-popup');
    $(document).on('click', '.rtwpvg-add-image', addImage);
    $(document).on('click', '.rtwpvg-remove-image', removeImage);
    $(document).on('click', '.rtwpvg-gallery-edit', galleryEdit);
    $(document).on('click', '.rtwpvg-media-video-popup', addMediaVideo);
    $('.woocommerce_variation').each(function () {
      var optionsWrapper = $(this).find('.options');
      var galleryWrapper = $(this).find('.rtwpvg-gallery-wrapper');
      galleryWrapper.insertBefore(optionsWrapper);
    });
  }
  function addImage(event) {
    event.preventDefault();
    event.stopPropagation();
    var that = this;
    var file_frame = 0;
    var product_variation_id = $(this).data('product_variation_id');
    var loop = $(this).data('product_variation_loop');
    // console.log( $(this) );
    var _prev_image = $(this).parents('.rtwpvg-gallery-wrapper').find('input').map(function () {
      return Number($(this).val());
    }).get();
    console.log(_prev_image);
    if (typeof wp !== 'undefined' && wp.media && wp.media.editor) {
      if (file_frame) {
        file_frame.open();
        return;
      }
      file_frame = wp.media.frames.select_image = wp.media({
        title: rtwpvg_admin.choose_image,
        button: {
          text: rtwpvg_admin.add_image
        },
        library: {
          type: ['image']
        },
        multiple: true
      });
      file_frame.on('select', function () {
        var images = file_frame.state().get('selection').toJSON();
        var html = images.map(function (image) {
          if (image.type === 'image') {
            console.log(image);
            if (_prev_image.indexOf(image.id) === -1) {
              var id = image.id,
                rtwpvg_video_link = image.rtwpvg_video_link,
                image_sizes = image.sizes;
              image_sizes = image_sizes === undefined ? {} : image_sizes;
              var thumbnail = image_sizes.thumbnail,
                full = image_sizes.full;
              var url = thumbnail ? thumbnail.url : full.url;
              var template = wp.template('rtwpvg-image');
              return template({
                id: id,
                url: url,
                product_variation_id: product_variation_id,
                loop: loop,
                rtwpvg_video_link: rtwpvg_video_link
              });
            } else {
              alert('Cannot add duplicate items.');
            }
          }
        }).join('');
        $(that).parent().prev().find('.rtwpvg-images').append(html);
        sortable();
        variationChanged(that);
      });
      file_frame.open();
    }
  }
  function addMediaVideo(e) {
    var _rtwpvg_admin;
    e.preventDefault();
    e.stopPropagation();
    var imgList = $(e.currentTarget).parents('li.image');
    var imageIdField = imgList.find('input');
    var imageId = imageIdField.val();
    if (!imageId) return;
    var attachment = wp.media.attachment(imageId);
    var proVersion = ((_rtwpvg_admin = rtwpvg_admin) === null || _rtwpvg_admin === void 0 ? void 0 : _rtwpvg_admin.pro_version) || null;
    var minProVersion = '2.3.12';
    var hasProAndCompatible = proVersion && isVersionCompatible(proVersion, minProVersion);
    attachment.fetch().done(function () {
      var data = attachment.toJSON();
      var videoLink = (data === null || data === void 0 ? void 0 : data.rtwpvg_video_link) || '';
      var videoWidth = (data === null || data === void 0 ? void 0 : data.rtwpvg_video_width) || '';
      var videoHeight = (data === null || data === void 0 ? void 0 : data.rtwpvg_video_height) || '';
      // Create custom modal HTML
      var modalHtml = "\n            <div class=\"custom-edit-modal\" style=\"position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; display: flex; align-items: center; justify-content: center;\">\n                <div class=\"modal-inner-wrapper\" style=\"background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%;\">\n                    <div class=\"modal-form-section\" >\n                        <h2 style=\"margin-top:0\">Poster Image</h2>\n                        <hr/>\n                        <div class=\"image-wrap\" style=\"display: flex;justify-content: center;\"><img src=\"".concat(data.url, "\" style=\"max-width: 250px; height: auto; margin-bottom: 20px;\"></div>\n                        ").concat(!hasProAndCompatible ? "\n                        <div style=\"background: #fef7e0; border: 1px solid #e0c97d; color: #7a6000; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px;\">\n                            Video options are available in the <a href=\"https://www.radiustheme.com/downloads/woocommerce-variation-images-gallery/\" style=\"color: red;font-size: 16px\" target=\"_blank\"><strong>Pro version</strong></a> <br/> (minimum version ".concat(minProVersion, ")\n                        </div>\n                        ") : '', "\n                        <label style=\"display: block; margin-bottom: 15px;\">\n                            <strong>Video Url: </strong>\n                            <input ").concat(hasProAndCompatible ? '' : 'disabled', " type=\"text\" id=\"rtwpvg_video_link\" value=\"").concat(videoLink, "\" placeholder=\"https://www.youtube.com/watch?v=zQKKUx2ECa8\" style=\"width: 100%; padding: 8px; margin-top: 5px;\">\n                            <p class=\"help\">You can add a YouTube, Vimeo, TikTok, or uploaded video. <b>Example: https://www.youtube.com/watch?v=zQKKUx2ECa8</b> <br/> <a href=\"").concat(rtwpvg_admin.admin_url, "upload.php?mode=grid&attachment-filter=post_mime_type%3Avideo\" target=\"_blank\">Upload your video <span class=\"dashicons dashicons-video-alt3\"></span></a></p>\n                        </label>\n                        <label style=\"display: block; margin-bottom: 15px;\">\n                            <strong>Video Width: </strong>\n                            <input ").concat(hasProAndCompatible ? '' : 'disabled', " type=\"text\" id=\"rtwpvg_video_width\" value=\"").concat(videoWidth, "\" style=\"width: 100%; padding: 8px; margin-top: 5px;\">\n                            <p class=\"help\">Video Width. px or %. Empty for default. <b>Example: 575px</b> </p>\n                        </label>\n                        <label style=\"display: block; margin-bottom: 15px;\">\n                            <strong>Video Height: </strong>\n                            <input ").concat(hasProAndCompatible ? '' : 'disabled', " type=\"text\" id=\"rtwpvg_video_height\" value=\"").concat(videoHeight, "\" style=\"width: 100%; padding: 8px; margin-top: 5px;\">\n                            <p class=\"help\">Video Height. px or %. Empty for default. <b>Example: 550px</b></p>\n                        </label>\n                    </div>\n                     <hr/>\n                    <div style=\"text-align: right;margin-top:15px\">\n                        <button class=\"button\" id=\"cancel-edit\" style=\"margin-right: 10px;\">Cancel</button>\n                        <button class=\"button button-primary\" id=\"save-edit\">Update</button>\n                    </div>\n                </div>\n            </div>");
      $('body').append(modalHtml);

      // Cancel button
      $('#cancel-edit').on('click', function () {
        $('.custom-edit-modal').remove();
      });
      // Save button
      $('#save-edit').on('click', function () {
        var _rtwpvg_admin2;
        var videoLinkField = $('body').find('#rtwpvg_video_link');
        var videoWidthField = $('body').find('#rtwpvg_video_width');
        var videoHeightField = $('body').find('#rtwpvg_video_height');
        // If any required field is not found in DOM, stop submission
        if (!videoLinkField.length || !videoWidthField.length || !videoHeightField.length) {
          alert('Required video fields are missing. Please reload the page.');
          return;
        }
        if ((_rtwpvg_admin2 = rtwpvg_admin) !== null && _rtwpvg_admin2 !== void 0 && _rtwpvg_admin2.pro_version) {
          // Save via AJAX
          $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
              action: 'rtwpvg_update_attachment_video_meta',
              attachment_id: imageId,
              video_link: videoLinkField.val(),
              video_width: videoWidthField.val(),
              video_height: videoHeightField.val(),
              _wpnonce: rtwpvg_admin.nonce || ''
            },
            success: function success(response) {
              var _response$data, _response$data2, _response$data3;
              // Optional: update your preview image dynamically
              if (response !== null && response !== void 0 && (_response$data = response.data) !== null && _response$data !== void 0 && _response$data.hasVideo) {
                imgList.addClass('video');
              } else {
                imgList.removeClass('video');
              }
              $('body').find('.modal-inner-wrapper').html(response === null || response === void 0 || (_response$data2 = response.data) === null || _response$data2 === void 0 ? void 0 : _response$data2.message);
              if (response !== null && response !== void 0 && response.success) {
                setTimeout(function () {
                  $('body').find('.custom-edit-modal').remove();
                }, 800);
              }
              console.log(response === null || response === void 0 || (_response$data3 = response.data) === null || _response$data3 === void 0 ? void 0 : _response$data3.message);
            }
          });
        } else {
          alert('It\'s Pro Feature');
        }
      });
    }).fail(function () {
      console.error('Failed to load attachment');
    });
  }
  function galleryEdit(event) {
    event.preventDefault();
    event.stopPropagation();
    var imgList = $(event.currentTarget).parents('li.image');
    var imageIdField = imgList.find('input');
    var imageId = imageIdField.val();
    // Create the media frame
    var frame = wp.media({
      title: 'Edit Image',
      button: {
        text: 'Update Image'
      },
      library: {
        type: 'image'
      },
      multiple: false
    });
    // When the frame opens, preselect the current image
    frame.on('open', function () {
      if (imageId) {
        var selection = frame.state().get('selection');
        var attachment = wp.media.attachment(imageId);
        attachment.fetch();
        selection.add(attachment);
      }
    });
    // When the user selects a new image
    frame.on('select', function () {
      var _attachment$sizes;
      var attachment = frame.state().get('selection').first().toJSON();
      // ✅ Get thumbnail URL (fallback to full if not exist)
      var thumbUrl = ((_attachment$sizes = attachment.sizes) === null || _attachment$sizes === void 0 || (_attachment$sizes = _attachment$sizes.thumbnail) === null || _attachment$sizes === void 0 ? void 0 : _attachment$sizes.url) || attachment.url;
      // Optional: update your preview image dynamically
      var img = imgList.find('img');
      if (attachment !== null && attachment !== void 0 && attachment.rtwpvg_video_link) {
        imgList.addClass('video');
      } else {
        imgList.removeClass('video');
      }
      if (img.length) {
        img.attr('src', thumbUrl);
      }
      imageIdField.val(attachment.id);
    });

    // Open the frame
    frame.open();
    variationChanged(this);
  }
  function removeImage(event) {
    event.preventDefault();
    event.stopPropagation();
    var that = this;
    variationChanged(this);
    setTimeout(function () {
      $(that).parents('li.image').remove();
    }, 1);
  }
  function variationChanged(element) {
    $(element).closest('.woocommerce_variation').addClass('variation-needs-update');
    $('button.cancel-variation-changes, button.save-variation-changes').removeAttr('disabled');
    $('#variable_product_options').trigger('woocommerce_variations_input_changed');
  }
  function sortable() {
    $('.rtwpvg-images').sortable({
      items: 'li.image',
      cursor: 'move',
      scrollSensitivity: 40,
      forcePlaceholderSize: true,
      forceHelperSize: false,
      helper: 'clone',
      opacity: 0.65,
      placeholder: 'rtwpvg-sortable-placeholder',
      start: function start(event, ui) {
        ui.item.css('background-color', '#f6f6f6');
      },
      stop: function stop(event, ui) {
        ui.item.removeAttr('style');
      },
      update: function update() {
        variationChanged(this);
      }
    });
  }

  //Thumbnail Style
  function settingsThumbnailPosition() {
    var thumbnail_position = $('#thumbnail_position-field').val();
    // console.log( thumbnail_position );
    if ('grid' == thumbnail_position) {
      $('#thumbnail_slide-wrapper').hide();
      $('#slider_arrow-wrapper').hide();
      $('#slider_adaptive_height-wrapper').hide();
    } else {
      $('#thumbnail_slide-wrapper').show();
      $('#slider_arrow-wrapper').show();
      $('#slider_adaptive_height-wrapper').show();
    }
  }
  $('#woocommerce-product-data').on('woocommerce_variations_loaded', function () {
    imageUploader();
    sortable();
  });
  $('#variable_product_options').on('woocommerce_variations_added', function () {
    imageUploader();
    sortable();
  });
  $('#woocommerce-product-images .add_product_images').on('click', 'a', function (event) {
    // $(document).on('click', '.rtwpvg-media-video-popup', addMediaVideo);
  });
  //techlabpro23
  $(function () {
    $("#rtwpvg-settings-wrapper").on('click', '.pro-field', function (e) {
      e.preventDefault();
      $('.rtvg-pro-alert').show();
    });

    //pro alert close
    $('.rtvg-pro-alert-close').on('click', function (e) {
      e.preventDefault();
      $('.rtvg-pro-alert').hide();
    });

    //preloader option 
    function preloader_option() {
      var preloader = $("#preloader-field").is(':checked');
      if (preloader) {
        $("#preloader_image-wrapper").show();
        //$("#preload_style-wrapper").show();
      } else {
        $("#preloader_image-wrapper").hide();
        //$("#preload_style-wrapper").hide();
      }
    }
    preloader_option();
    $(document).on('change', '#preloader-field', function () {
      preloader_option();
    });

    //image upload field 
    $(document).on('click', '.rtwpvg-upload-box', function (e) {
      e.preventDefault();
      var name = $(this).attr('data-name');
      var field_type = $(this).attr('data-field');
      var self = $(this),
        file_frame,
        json; // If an instance of file_frame already exists, then we can open it rather than creating a new instance

      if (undefined !== file_frame) {
        file_frame.open();
        return;
      } // Here, use the wp.media library to define the settings of the media uploader

      file_frame = wp.media.frames.file_frame = wp.media({
        frame: 'post',
        state: 'insert',
        multiple: field_type == 'image' ? false : true
      }); // Setup an event handler for what to do when an image has been selected

      file_frame.on('insert', function () {
        // Read the JSON data returned from the media uploader
        json = file_frame.state().get('selection').first().toJSON(); // First, make sure that we have the URL of an image to display

        if (0 > $.trim(json.url.length)) {
          return;
        }
        var images = file_frame.state().get('selection').toJSON();
        var img_data = '';
        var multiple = field_type == 'image' ? '' : '[]';
        images.forEach(function (element) {
          img_data += "<div class='rtwpvg-preview-img'><img src='" + element.url + "' /><input type='hidden' name='" + name + multiple + "' value='" + element.id + "'><button class='rtwpvg-file-remove' data-id='" + element.id + "'>x</button></div>";
        });
        if (field_type == 'image') {
          self.prev().html(img_data);
        } else {
          self.prev().html(img_data);
        }
      }); // Now display the actual file_frame

      file_frame.open();
    });

    //delete image  
    $(document).on('click', '.rtwpvg-file-remove', function (e) {
      e.preventDefault();
      if (confirm(rtwpvg_admin.sure_txt)) {
        if ($(this).parent().parent().children('.rtwpvg-preview-img').length <= 1) {
          $(this).parent().children('img').remove();
          $(this).parent().children('input').val(0);
          $(this).remove();
        } else {
          $(this).parent().remove();
        }
        $('button.woocommerce-save-button').removeAttr('disabled');
      }
    });

    //Thumbnail Style
    settingsThumbnailPosition();
    $('#thumbnail_position-field').on('change', function (e) {
      e.preventDefault();
      settingsThumbnailPosition();
    });

    // Re-init when variations are loaded/added or when you modify the DOM
    $('#woocommerce-product-data').on('woocommerce_variations_loaded woocommerce_variations_added', function () {
      rtwpvg_refresh_tooltips();
    });
    rtwpvg_refresh_tooltips();
  });

  //end tachlabpro23
})(jQuery);
