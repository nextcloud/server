/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Michael Jobst <mjobst+github@tecratech.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

(function(OC) {

	_.extend(OC.Files.Client, {
		PROPERTY_FILEID: '{' + OC.Files.Client.NS_OWNCLOUD + '}id',
		PROPERTY_CAN_ASSIGN: '{' + OC.Files.Client.NS_OWNCLOUD + '}can-assign',
		PROPERTY_DISPLAYNAME: '{' + OC.Files.Client.NS_OWNCLOUD + '}display-name',
		PROPERTY_USERVISIBLE: '{' + OC.Files.Client.NS_OWNCLOUD + '}user-visible',
		PROPERTY_USERASSIGNABLE: '{' + OC.Files.Client.NS_OWNCLOUD + '}user-assignable',
	})

	/**
	 * @class OCA.SystemTags.SystemTagsCollection
	 * @classdesc
	 *
	 * System tag
	 *
	 */
	const SystemTagModel = OC.Backbone.Model.extend(
		/** @lends OCA.SystemTags.SystemTagModel.prototype */ {
			sync: OC.Backbone.davSync,

			defaults: {
				userVisible: true,
				userAssignable: true,
				canAssign: true,
			},

			davProperties: {
				id: OC.Files.Client.PROPERTY_FILEID,
				name: OC.Files.Client.PROPERTY_DISPLAYNAME,
				userVisible: OC.Files.Client.PROPERTY_USERVISIBLE,
				userAssignable: OC.Files.Client.PROPERTY_USERASSIGNABLE,
				// read-only, effective permissions computed by the server,
				canAssign: OC.Files.Client.PROPERTY_CAN_ASSIGN,
			},

			parse(data) {
				return {
					id: data.id,
					name: data.name,
					userVisible: data.userVisible === true || data.userVisible === 'true',
					userAssignable: data.userAssignable === true || data.userAssignable === 'true',
					canAssign: data.canAssign === true || data.canAssign === 'true',
				}
			},
		})

	OC.SystemTags = OC.SystemTags || {}
	OC.SystemTags.SystemTagModel = SystemTagModel
})(OC)
