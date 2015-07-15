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
		'<div class="thumbnail"></div>' +
		'<ul class="detailList">' +
		'   <li>Name: {{name}}</li>' +
		'   <li>Path: {{path}}</li>' +
		'</ul>';

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
		 *
		 * @abstract
		 */
		render: function() {
			this.$el.empty();

			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}

			if (this._fileInfo) {
				this.$el.append(this._template(this._fileInfo));
				var $iconDiv = this.$el.find('.thumbnail');
				// FIXME: use proper way, this is only for demo purposes
				FileList.lazyLoadPreview({
					path: this._fileInfo.path + '/' + this._fileInfo.name,
					mime: this._fileInfo.mimetype,
					etag: this._fileInfo.etag,
					callback: function(url) {
						$iconDiv.css('background-image', 'url("' + url + '")');
					}
				});
			} else {
				// TODO: render placeholder text?
			}
		}
	});

	OCA.Files.MainFileInfoDetailView = MainFileInfoDetailView;
})();

