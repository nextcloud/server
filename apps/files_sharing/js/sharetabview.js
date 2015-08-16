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
		'<div><ul>{{#if owner}}<li>Owner: {{owner}}</li>{{/if}}</ul></div>';

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
				console.log(this.model);
				var owner = this.model.get('shareOwner');
				if (owner === OC.currentUser) {
					owner = null;
				}
				this.$el.append(this._template({
					owner: owner
				}));

			} else {
				// TODO: render placeholder text?
			}
		}
	});

	OCA.Sharing.ShareTabView = ShareTabView;
})();

