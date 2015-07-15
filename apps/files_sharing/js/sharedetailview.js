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
		'<ul class="shareDetailList">' +
		'   <li>Owner: {{owner}}</li>' +
		'</ul>';

	/**
	 * @class OCA.Files.MainFileInfoDetailView
	 * @classdesc
	 *
	 * Displays main details about a file
	 *
	 */
	var ShareDetailView = function() {
		this.initialize();
	};
	/**
	 * @memberof OCA.Sharing
	 */
	ShareDetailView.prototype = _.extend({}, OCA.Files.DetailFileInfoView.prototype,
		/** @lends OCA.Sharing.ShareDetailView.prototype */ {
		_template: null,

		/**
		 * Initialize the details view
		 */
		initialize: function() {
			this.$el = $('<div class="shareDetailView"></div>');
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
					owner: this._fileInfo.shareOwner || OC.currentUser
				}));
			} else {
				// TODO: render placeholder text?
			}
		}
	});

	OCA.Sharing.ShareDetailView = ShareDetailView;
})();

