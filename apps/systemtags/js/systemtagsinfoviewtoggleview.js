/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function(OCA) {

	var TEMPLATE =
		'<span class="icon icon-tag"/>' + t('systemtags', 'Tags');

	/**
	 * @class OCA.SystemTags.SystemTagsInfoViewToggleView
	 * @classdesc
	 *
	 * View to toggle the visibility of a SystemTagsInfoView.
	 *
	 * This toggle view must be explicitly rendered before it is used.
	 */
	var SystemTagsInfoViewToggleView = OC.Backbone.View.extend(
		/** @lends OC.Backbone.View.prototype */ {

		tagName: 'span',

		className: 'tag-label',

		events: {
			'click': 'click'
		},

		/**
		 * @type OCA.SystemTags.SystemTagsInfoView
		 */
		_systemTagsInfoView: null,

		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},

		/**
		 * Initialize this toggle view.
		 *
		 * The options must provide a systemTagsInfoView parameter that
		 * references the SystemTagsInfoView to associate to this toggle view.
		 */
		initialize: function(options) {
			var self = this;
			options = options || {};

			this._systemTagsInfoView = options.systemTagsInfoView;
			if (!this._systemTagsInfoView) {
				throw 'Missing required parameter "systemTagsInfoView"';
			}
		},

		/**
		 * Toggles the visibility of the associated SystemTagsInfoView.
		 *
		 * When the systemTagsInfoView is shown its dropdown is also opened.
		 */
		click: function() {
			if (this._systemTagsInfoView.isVisible()) {
				this._systemTagsInfoView.hide();
			} else {
				this._systemTagsInfoView.show();
				this._systemTagsInfoView.openDropdown();
			}
		},

		/**
		 * Renders this toggle view.
		 *
		 * @return OCA.SystemTags.SystemTagsInfoViewToggleView this object.
		 */
		render: function() {
			this.$el.html(this.template());

			return this;
		},

	});

	OCA.SystemTags.SystemTagsInfoViewToggleView = SystemTagsInfoViewToggleView;

})(OCA);
