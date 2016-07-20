/* global Backbone */

/**
 * @author Christoph Wurst <christoph@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

(function(OC) {
	'use strict';

	OC.Settings = OC.Settings || {};

	var AuthTokenCollection = OC.Backbone.Collection.extend({

		model: OC.Settings.AuthToken,

		/**
		 * Show recently used sessions/devices first
		 *
		 * @param {OC.Settigns.AuthToken} t1
		 * @param {OC.Settigns.AuthToken} t2
		 * @returns {Boolean}
		 */
		comparator: function (t1, t2) {
			var ts1 = parseInt(t1.get('lastActivity'), 10);
			var ts2 = parseInt(t2.get('lastActivity'), 10);
			return ts2 - ts1;
		},

		tokenType: null,

		url: OC.generateUrl('/settings/personal/authtokens')
	});

	OC.Settings.AuthTokenCollection = AuthTokenCollection;

})(OC);
