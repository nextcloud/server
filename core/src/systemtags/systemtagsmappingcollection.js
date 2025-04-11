/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateRemoteUrl } from '@nextcloud/router'

(function(OC) {
	/**
	 * @class OC.SystemTags.SystemTagsMappingCollection
	 * @classdesc
	 *
	 * Collection of tags assigned to a an object
	 *
	 */
	const SystemTagsMappingCollection = OC.Backbone.Collection.extend(
		/** @lends OC.SystemTags.SystemTagsMappingCollection.prototype */ {

			sync: OC.Backbone.davSync,

			/**
			 * Use PUT instead of PROPPATCH
			 */
			usePUT: true,

			/**
			 * Id of the file for which to filter activities by
			 *
			 * @member int
			 */
			_objectId: null,

			/**
			 * Type of the object to filter by
			 *
			 * @member string
			 */
			_objectType: 'files',

			model: OC.SystemTags.SystemTagModel,

			url() {
				return generateRemoteUrl('dav') + '/systemtags-relations/' + this._objectType + '/' + this._objectId
			},

			/**
			 * Sets the object id to filter by or null for all.
			 *
			 * @param {number} objectId file id or null
			 */
			setObjectId(objectId) {
				this._objectId = objectId
			},

			/**
			 * Sets the object type to filter by or null for all.
			 *
			 * @param {number} objectType file id or null
			 */
			setObjectType(objectType) {
				this._objectType = objectType
			},

			initialize(models, options) {
				options = options || {}
				if (!_.isUndefined(options.objectId)) {
					this._objectId = options.objectId
				}
				if (!_.isUndefined(options.objectType)) {
					this._objectType = options.objectType
				}
			},

			getTagIds() {
				return this.map(function(model) {
					return model.id
				})
			},
		})

	OC.SystemTags = OC.SystemTags || {}
	OC.SystemTags.SystemTagsMappingCollection = SystemTagsMappingCollection
})(OC)
