/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
	if (!OC.Share) {
		OC.Share = {};
	}

	OC.Share.Social = {};

	var SocialModel = OC.Backbone.Model.extend({
		defaults: {
			/** used for sorting social buttons */
			key: null,
			/** url to open, {{reference}} will be replaced with the link */
			url: null,
			/** Name to show in the tooltip */
			name: null,
			/** Icon class to display */
			iconClass: null,
			/** Open in new windows */
			newWindow: true
		}
	});

	OC.Share.Social.Model = SocialModel;

	var SocialCollection = OC.Backbone.Collection.extend({
		model: OC.Share.Social.Model,

		comparator: 'key'
	});


	OC.Share.Social.Collection = new SocialCollection;
})();
