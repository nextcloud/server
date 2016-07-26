/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function () {
	SidebarPreviewManager = function (fileList) {
		this._fileList = fileList;
		this._previewHandlers = {};
		OC.Plugins.attach('OCA.Files.SidebarPreviewManager', this);
	};

	SidebarPreviewManager.prototype = {
		addPreviewHandler: function (mime, handler) {
			this._previewHandlers[mime] = handler;
		},

		getPreviewHandler: function (mime) {
			var mimePart = mime.split('/').shift();
			if (this._previewHandlers[mime]) {
				return this._previewHandlers[mime];
			} else if(this._previewHandlers[mimePart]) {
				return this._previewHandlers[mimePart];
			} else {
				return this.fallbackPreview.bind(this);
			}
		},

		loadPreview: function (model, $thumbnailDiv, $thumbnailContainer) {
			if (model.get('hasPreview') === false) {
				var mimeIcon = OC.MimeType.getIconUrl(model.get('mimetype'));
				$thumbnailDiv.removeClass('icon-loading icon-32');
				$thumbnailContainer.removeClass('image'); //fall back to regular view
				$thumbnailDiv.css({
					'background-image': 'url("' + mimeIcon + '")'
				});
			} else {
				var handler = this.getPreviewHandler(model.get('mimetype'));
				var fallback = this.fallbackPreview.bind(this, model, $thumbnailDiv, $thumbnailContainer);
				handler(model, $thumbnailDiv, $thumbnailContainer, fallback);
			}
		},

		// previews for images and mimetype icons
		fallbackPreview: function (model, $thumbnailDiv, $thumbnailContainer) {
			var isImage = model.isImage();
			var maxImageWidth = $thumbnailContainer.parent().width() + 50;  // 50px for negative margins
			var maxImageHeight = maxImageWidth / (16 / 9);
			var smallPreviewSize = 75;

			var isLandscape = function (img) {
				return img.width > (img.height * 1.2);
			};

			var isSmall = function (img) {
				return (img.width * 1.1) < (maxImageWidth * window.devicePixelRatio);
			};

			var getTargetHeight = function (img) {
				if (isImage) {
					var targetHeight = img.height / window.devicePixelRatio;
					if (targetHeight <= smallPreviewSize) {
						targetHeight = smallPreviewSize;
					}
					return targetHeight;
				} else {
					return smallPreviewSize;
				}
			};

			var getTargetRatio = function (img) {
				var ratio = img.width / img.height;
				if (ratio > 16 / 9) {
					return ratio;
				} else {
					return 16 / 9;
				}
			};

			this._fileList.lazyLoadPreview({
				path: model.getFullPath(),
				mime: model.get('mimetype'),
				etag: model.get('etag'),
				y: isImage ? maxImageHeight : smallPreviewSize,
				x: isImage ? maxImageWidth : smallPreviewSize,
				a: isImage ? 1 : null,
				mode: isImage ? 'cover' : null,
				callback: function (previewUrl, img) {
					$thumbnailDiv.previewImg = previewUrl;

					// as long as we only have the mimetype icon, we only save it in case there is no preview
					if (!img) {
						return;
					}
					$thumbnailDiv.removeClass('icon-loading icon-32');
					var targetHeight = getTargetHeight(img);
					if (isImage && targetHeight > smallPreviewSize) {
						$thumbnailContainer.addClass((isLandscape(img) && !isSmall(img)) ? 'landscape' : 'portrait');
						$thumbnailContainer.addClass('large');
					}

					// only set background when we have an actual preview
					// when we don't have a preview we show the mime icon in the error handler
					$thumbnailDiv.css({
						'background-image': 'url("' + previewUrl + '")',
						height: (targetHeight > smallPreviewSize) ? 'auto' : targetHeight,
						'max-height': isSmall(img) ? targetHeight : null
					});

					var targetRatio = getTargetRatio(img);
					$thumbnailDiv.find('.stretcher').css({
						'padding-bottom': (100 / targetRatio) + '%'
					});
				},
				error: function () {
					$thumbnailDiv.removeClass('icon-loading icon-32');
					$thumbnailContainer.removeClass('image'); //fall back to regular view
					$thumbnailDiv.css({
						'background-image': 'url("' + $thumbnailDiv.previewImg + '")'
					});
				}
			});
		}
	};

	OCA.Files.SidebarPreviewManager = SidebarPreviewManager;
})();
