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
		'<div class="thumbnail"></div><div class="fileName">{{name}}</div>' +
		'<div><span title="{{altSize}}">{{size}}</span>, <span title="{{altDate}}">{{date}}</span></div>';

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
				this.$el.append(this._template({
					nameLabel: t('files', 'Name'),
					name: this._fileInfo.name,
					pathLabel: t('files', 'Path'),
					path: this._fileInfo.path,
					sizeLabel: t('files', 'Size'),
					// TODO: refactor and use size formatter
					size: OC.Util.humanFileSize(this._fileInfo.size, true),
					altSize: this._fileInfo.size,
					dateLabel: t('files', 'Modified'),
					altDate: OC.Util.formatDate(this._fileInfo.mtime),
					date: OC.Util.relativeModifiedDate(this._fileInfo.mtime)
				}));

				var $iconDiv = this.$el.find('.thumbnail');
				// TODO: we really need OC.Previews
				if (this._fileInfo.mimetype !== 'httpd/unix-directory') {
					// FIXME: use proper way, this is only for demo purposes
					var previewUrl = FileList.generatePreviewUrl({
						file: this._fileInfo.path + '/' + this._fileInfo.name,
						c: this._fileInfo.etag,
						x: 50,
						y: 50
					});
					previewUrl = previewUrl.replace('(', '%28').replace(')', '%29');
					$iconDiv.css('background-image', 'url("' + previewUrl + '")');
				} else {
					// TODO: special icons / shared / external
					$iconDiv.css('background-image', 'url("' + OC.MimeType.getIconUrl('dir') + '")');
				}
			} else {
				// TODO: render placeholder text?
			}
		}
	});

	OCA.Files.MainFileInfoDetailView = MainFileInfoDetailView;
})();

