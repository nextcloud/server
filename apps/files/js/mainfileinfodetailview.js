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
		'<div class="thumbnailContainer"><a href="#" class="thumbnail action-default"></a></div>' +
		'<div class="file-details-container">' +
		'<div class="fileName"><h3 title="{{name}}" class="ellipsis">{{name}}</h3></div>' +
		'	<div class="file-details ellipsis">' +
		'		<a href="#" ' +
		'		alt="{{starAltText}}"' +
		'		class="action action-favorite favorite">' +
		'			<img class="svg" src="{{starIcon}}" />' +
		'		</a>' +
		'		{{#if hasSize}}<span class="size" title="{{altSize}}">{{size}}</span>, {{/if}}<span class="date" title="{{altDate}}">{{date}}</span>' +
		'	</div>' +
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
			'click a.action-default': '_onClickDefaultAction'
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
				throw 'Missing requird parameter "fileList"';
			}
			if (!this._fileActions) {
				throw 'Missing requird parameter "fileActions"';
			}
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
					starIcon: OC.imagePath('core', isFavorite ? 'actions/starred' : 'actions/star')
				}));

				// TODO: we really need OC.Previews
				var $iconDiv = this.$el.find('.thumbnail');
				$iconDiv.addClass('icon-loading');
				$container = this.$el.find('.thumbnailContainer');
				if (!this.model.isDirectory()) {
					this._fileList.lazyLoadPreview({
						path: this.model.getFullPath(),
						mime: this.model.get('mimetype'),
						etag: this.model.get('etag'),
						y: this.model.isImage() ? 250: 75,
						x: this.model.isImage() ? 99999 /* only limit on y */ : 75,
						a: this.model.isImage() ? 1 : null,
						callback: function(previewUrl, img) {
							$iconDiv.previewImg = previewUrl;
							if (img) {
								$iconDiv.removeClass('icon-loading');
								if(img.height > img.width) {
									$container.addClass('portrait');
								}
							}
							if (this.model.isImage() && img) {
								$iconDiv.parent().addClass('image');
								var targetHeight = img.height / window.devicePixelRatio;
								if (targetHeight <= 75) {
									$container.removeClass('image'); // small enough to fit in normaly
									targetHeight = 75;
								}
							} else {
								targetHeight = 75;
							}

							// only set background when we have an actual preview
							// when we dont have a preview we show the mime icon in the error handler
							if (img) {
								$iconDiv.css({
									'background-image': 'url("' + previewUrl + '")',
									'height': targetHeight
								});
							}
						}.bind(this),
						error: function() {
							$iconDiv.removeClass('icon-loading');
							this.$el.find('.thumbnailContainer').removeClass('image'); //fall back to regular view
							$iconDiv.css({
								'background-image': 'url("' + $iconDiv.previewImg + '")'
							});
						}.bind(this)
					});
				} else {
					// TODO: special icons / shared / external
					$iconDiv.css('background-image', 'url("' + OC.MimeType.getIconUrl('dir') + '")');
				}
				this.$el.find('[title]').tooltip({placement: 'bottom'});
			} else {
				this.$el.empty();
			}
			this.delegateEvents();
		}
	});

	OCA.Files.MainFileInfoDetailView = MainFileInfoDetailView;
})();
