/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
			 * @param {int} objectId file id or null
			 */
			setObjectId(objectId) {
				this._objectId = objectId
			},

			/**
			 * Sets the object type to filter by or null for all.
			 *
			 * @param {int} objectType file id or null
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
