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
		'<div>Owner: {{owner}}';

	/**
	 * @memberof OCA.Sharing
	 */
	var ShareTabView = OCA.Files.DetailTabView.extend(
		/** @lends OCA.Sharing.ShareTabView.prototype */ {
		id: 'shareTabView',
		className: 'tab shareTabView',

		_template: null,

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

			if (this.model) {
				this.$el.append(this._template({
					owner: this.model.get('shareOwner') || OC.currentUser
				}));

			} else {
				// TODO: render placeholder text?
			}
		}
	});

	OCA.Sharing.ShareTabView = ShareTabView;
})();

