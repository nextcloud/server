/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * @param {object} OC The OC namespace
 */

(function(OC) {
	if (OC?.Files?.Client) {
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
	}
})(OC)
