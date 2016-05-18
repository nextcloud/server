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

(function(OC, Backbone) {
	'use strict';

	OC.Settings = OC.Settings || {};

	var AuthTokenCollection = Backbone.Collection.extend({
		model: OC.Settings.AuthToken,
		tokenType: null,
		url: OC.generateUrl('/settings/personal/authtokens'),
	});

	OC.Settings.AuthTokenCollection = AuthTokenCollection;

})(OC, Backbone);
