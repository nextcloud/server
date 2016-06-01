/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	var TEMPLATE =
		'<div class="thumbnailContainer"><a href="#" class="thumbnail action-default"><div class="stretcher"/></a></div>' +
		'<div class="file-details-container">' +
		'<div class="fileName">' +
			'<h3 title="{{name}}" class="ellipsis">{{name}}</h3>' +
			'<a class="permalink" href="{{permalink}}" title="{{permalinkTitle}}">' +
				'<span class="icon icon-public"></span>' +
				'<span class="hidden-visually">{{permalinkTitle}}</span>' +
			'</a>' +
		'</div>' +
		'	<div class="file-details ellipsis">' +
		'		<a href="#" ' +
		'		class="action action-favorite favorite">' +
		'			<img class="svg" alt="{{starAltText}}" src="{{starIcon}}" />' +
		'		</a>' +
		'		{{#if hasSize}}<span class="size" title="{{altSize}}">{{size}}</span>, {{/if}}<span class="date" title="{{altDate}}">{{date}}</span>' +
		'	</div>' +
		'</div>' +
		'<div class="hidden permalink-field">' +
			'<input type="text" value="{{permalink}}" placeholder="{{permalinkTitle}}" readonly="readonly"/>' +
		'</div>';

	/**
	 * @class OCA.Files.MainFileInfoDetailView
	 * @classdesc
	 *
	 * Displays main details about a file
	 *
	 */
	var MainFileInfoDetailView = OCA.Files.DetailFileInfoView.extend(
		/** @lends OCA.Files.MainFileInfoDetailView.prototype */ {

		className: 'mainFileInfoView',

		/**
		 * Associated file list instance, for file actions
		 *
		 * @type {OCA.Files.FileList}
		 */
		_fileList: null,

		/**
		 * File actions
		 *
		 * @type {OCA.Files.FileActions}
		 */
		_fileActions: null,

		events: {
			'click a.action-favorite': '_onClickFavorite',
			'click a.action-default': '_onClickDefaultAction',
			'click a.permalink': '_onClickPermalink',
			'focus .permalink-field>input': '_onFocusPermalink'
		},

		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},

		initialize: function(options) {
			options = options || {};
			this._fileList = options.fileList;
			this._fileActions = options.fileActions;
			if (!this._fileList) {
				throw 'Missing required parameter "fileList"';
			}
			if (!this._fileActions) {
				throw 'Missing required parameter "fileActions"';
			}
		},

		_onClickPermalink: function() {
			var $row = this.$('.permalink-field');
			$row.toggleClass('hidden');
			if (!$row.hasClass('hidden')) {
				$row.find('>input').focus();
			}
			// cancel click, user must right-click + copy or middle click
			return false;
		},

		_onFocusPermalink: function() {
			this.$('.permalink-field>input').select();
		},

		_onClickFavorite: function(event) {
			event.preventDefault();
			this._fileActions.triggerAction('Favorite', this.model, this._fileList);
		},

		_onClickDefaultAction: function(event) {
			event.preventDefault();
			this._fileActions.triggerAction(null, this.model, this._fileList);
		},

		_onModelChanged: function() {
			// simply re-render
			this.render();
		},

		_makePermalink: function(fileId) {
			var baseUrl = OC.getProtocol() + '://' + OC.getHost();
			return baseUrl + OC.generateUrl('/f/{fileId}', {fileId: fileId});
		},

		setFileInfo: function(fileInfo) {
			if (this.model) {
				this.model.off('change', this._onModelChanged, this);
			}
			this.model = fileInfo;
			if (this.model) {
				this.model.on('change', this._onModelChanged, this);
			}
			this.render();
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			if (this.model) {
				var isFavorite = (this.model.get('tags') || []).indexOf(OC.TAG_FAVORITE) >= 0;
				this.$el.html(this.template({
					type: this.model.isImage()? 'image': '',
					nameLabel: t('files', 'Name'),
					name: this.model.get('displayName') || this.model.get('name'),
					pathLabel: t('files', 'Path'),
					path: this.model.get('path'),
					hasSize: this.model.has('size'),
					sizeLabel: t('files', 'Size'),
					size: OC.Util.humanFileSize(this.model.get('size'), true),
					altSize: n('files', '%n byte', '%n bytes', this.model.get('size')),
					dateLabel: t('files', 'Modified'),
					altDate: OC.Util.formatDate(this.model.get('mtime')),
					date: OC.Util.relativeModifiedDate(this.model.get('mtime')),
					starAltText: isFavorite ? t('files', 'Favorited') : t('files', 'Favorite'),
					starIcon: OC.imagePath('core', isFavorite ? 'actions/starred' : 'actions/star'),
					permalink: this._makePermalink(this.model.get('id')),
					permalinkTitle: t('files', 'Local link')
				}));

				// TODO: we really need OC.Previews
				var $iconDiv = this.$el.find('.thumbnail');
				var $container = this.$el.find('.thumbnailContainer');
				if (!this.model.isDirectory()) {
					$iconDiv.addClass('icon-loading icon-32');
					this.loadPreview(this.model.getFullPath(), this.model.get('mimetype'), this.model.get('etag'), $iconDiv, $container, this.model.isImage());
				} else {
					var iconUrl = this.model.get('icon') || OC.MimeType.getIconUrl('dir');
					$iconDiv.css('background-image', 'url("' + iconUrl + '")');
					OC.Util.scaleFixForIE8($iconDiv);
				}
				this.$el.find('[title]').tooltip({placement: 'bottom'});
			} else {
				this.$el.empty();
			}
			this.delegateEvents();
		},

		loadPreview: function(path, mime, etag, $iconDiv, $container, isImage) {
			var maxImageWidth  = $container.parent().width() + 50;  // 50px for negative margins
			var maxImageHeight = maxImageWidth / (16/9);
			var smallPreviewSize = 75;

			var isLandscape = function(img) {
				return img.width > (img.height * 1.2);
			};

			var isSmall = function(img) {
				return (img.width * 1.1) < (maxImageWidth * window.devicePixelRatio);
			};

			var getTargetHeight = function(img) {
				if(isImage) {
					var targetHeight = img.height / window.devicePixelRatio;
					if (targetHeight <= smallPreviewSize) {
						targetHeight = smallPreviewSize;
					}
					return targetHeight;
				}else{
					return smallPreviewSize;
				}
			};

			var getTargetRatio = function(img){
				var ratio = img.width / img.height;
				if (ratio > 16/9) {
					return ratio;
				} else {
					return 16/9;
				}
			};

			this._fileList.lazyLoadPreview({
				path: path,
				mime: mime,
				etag: etag,
				y: isImage ? maxImageHeight : smallPreviewSize,
				x: isImage ? maxImageWidth : smallPreviewSize,
				a: isImage ? 1 : null,
				mode: isImage ? 'cover' : null,
				callback: function (previewUrl, img) {
					$iconDiv.previewImg = previewUrl;

					// as long as we only have the mimetype icon, we only save it in case there is no preview
					if (!img) {
						return;
					}
					$iconDiv.removeClass('icon-loading icon-32');
					var targetHeight = getTargetHeight(img);
					if (this.model.isImage() && targetHeight > smallPreviewSize) {
						$container.addClass((isLandscape(img) && !isSmall(img))? 'landscape': 'portrait');
						$container.addClass('image');
					}

					// only set background when we have an actual preview
					// when we don't have a preview we show the mime icon in the error handler
					$iconDiv.css({
						'background-image': 'url("' + previewUrl + '")',
						height: (targetHeight > smallPreviewSize)? 'auto': targetHeight,
						'max-height': isSmall(img)? targetHeight: null
					});

					var targetRatio = getTargetRatio(img);
					$iconDiv.find('.stretcher').css({
						'padding-bottom': (100 / targetRatio) + '%'
					});
				}.bind(this),
				error: function () {
					$iconDiv.removeClass('icon-loading icon-32');
					this.$el.find('.thumbnailContainer').removeClass('image'); //fall back to regular view
					$iconDiv.css({
						'background-image': 'url("' + $iconDiv.previewImg + '")'
					});
					OC.Util.scaleFixForIE8($iconDiv);
				}.bind(this)
			});
		}
	});

	OCA.Files.MainFileInfoDetailView = MainFileInfoDetailView;
})();
