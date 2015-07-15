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
		'<div>TODO: here comes the share dialog</div>';

	/**
	 * @class OCA.Sharing.ShareTabView
	 * @classdesc
	 *
	 * Displays sharing information
	 *
	 */
	var ShareTabView = function(id) {
		this.initialize(id);
	};
	/**
	 * @memberof OCA.Sharing
	 */
	ShareTabView.prototype = _.extend({}, OCA.Files.DetailTabView.prototype,
		/** @lends OCA.Sharing.ShareTabView.prototype */ {
		_template: null,

		/**
		 * Initialize the details view
		 */
		initialize: function() {
			OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
			this.$el.addClass('shareTabView');
		},

		getLabel: function() {
			return t('files_sharing', 'Sharing');
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
				this.$el.append(this._template());
			} else {
				// TODO: render placeholder text?
			}
		}
	});

	OCA.Sharing.ShareTabView = ShareTabView;
})();

