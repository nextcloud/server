/* global Handlebars, OC */

/**
 * @copyright 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
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

(function() {
	'use strict';

	var TEMPLATE = '{{#if isShared}}'
		+ 'Shared!'
		+ '{{else}}'
		+ 'Not shared!'
		+ '{{/if}}';

	var BreadCrumbView = OC.Backbone.View.extend({
		tagName: 'span',
		_template: undefined,
		template: function(data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		},
		render: function(data) {
			var isShared = data.dirInfo && data.dirInfo.shareTypes && data.dirInfo.shareTypes.length > 0;

			this.$el.html(this.template({
				isShared: isShared
			}));

			return this;
		}
	});

	OCA.Sharing.ShareBreadCrumbView = BreadCrumbView;
})();

