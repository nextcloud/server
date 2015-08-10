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
		'<div class="thumbnail"></div><div title="{{name}}" class="fileName ellipsis">{{name}}</div>' +
		'<div class="file-details ellipsis">' +
		'    <a href="#" ' +
		'    alt="{{starAltText}}"' +
		'    class="action action-favorite favorite">' +
		'    <img class="svg" src="{{starIcon}}" />' +
		'    </a>' +
		'    <span class="size" title="{{altSize}}">{{size}}</span>, <span class="date" title="{{altDate}}">{{date}}</span>' +
		'</div>';

	/**
	 * @class OCA.Files.MainFileInfoDetailView
	 * @classdesc
	 *
	 * Displays main details about a file
	 *
	 */
	var MainFileInfoDetailView = function() {
		this.initialize();
	};
	/**
	 * @memberof OCA.Files
	 */
	MainFileInfoDetailView.prototype = _.extend({}, OCA.Files.DetailFileInfoView.prototype,
		/** @lends OCA.Files.MainFileInfoDetailView.prototype */ {
		_template: null,

		/**
		 * Initialize the details view
		 */
		initialize: function() {
			this.$el = $('<div class="mainFileInfoView"></div>');
		},

		/**
		 * Renders this details view
		 */
		render: function() {
			this.$el.empty();

			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}

			if (this._fileInfo) {
				var isFavorite = (this._fileInfo.tags || []).indexOf(OC.TAG_FAVORITE) >= 0;
				this.$el.append(this._template({
					nameLabel: t('files', 'Name'),
					name: this._fileInfo.name,
					pathLabel: t('files', 'Path'),
					path: this._fileInfo.path,
					sizeLabel: t('files', 'Size'),
					size: OC.Util.humanFileSize(this._fileInfo.size, true),
					altSize: n('files', '%n byte', '%n bytes', this._fileInfo.size),
					dateLabel: t('files', 'Modified'),
					altDate: OC.Util.formatDate(this._fileInfo.mtime),
					date: OC.Util.relativeModifiedDate(this._fileInfo.mtime),
					starAltText: isFavorite ? t('files', 'Favorited') : t('files', 'Favorite'),
					starIcon: OC.imagePath('core', isFavorite ? 'actions/starred' : 'actions/star')
				}));

				// TODO: we really need OC.Previews
				var $iconDiv = this.$el.find('.thumbnail');
				if (this._fileInfo.mimetype !== 'httpd/unix-directory') {
					// TODO: inject utility class?
					FileList.lazyLoadPreview({
						path: this._fileInfo.path + '/' + this._fileInfo.name,
						mime: this._fileInfo.mimetype,
						etag: this._fileInfo.etag,
						x: 50,
						y: 50,
						callback: function(previewUrl) {
							$iconDiv.css('background-image', 'url("' + previewUrl + '")');
						}
					});
				} else {
					// TODO: special icons / shared / external
					$iconDiv.css('background-image', 'url("' + OC.MimeType.getIconUrl('dir') + '")');
				}
				this.$el.find('[title]').tooltip({placement: 'bottom'});
			}
		}
	});

	OCA.Files.MainFileInfoDetailView = MainFileInfoDetailView;
})();
