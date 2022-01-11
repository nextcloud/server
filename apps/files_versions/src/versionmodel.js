/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Robin Appelman <robin@icewind.nl>
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

(function() {
	/**
	 * @memberof OCA.Versions
	 */
	const VersionModel = OC.Backbone.Model.extend({
		sync: OC.Backbone.davSync,

		davProperties: {
			size: '{DAV:}getcontentlength',
			mimetype: '{DAV:}getcontenttype',
			timestamp: '{DAV:}getlastmodified',
		},

		/**
		 * Restores the original file to this revision
		 *
		 * @param {object} [options] options
		 * @return {Promise}
		 */
		revert(options) {
			options = options ? _.clone(options) : {}
			const model = this

			const client = this.get('client')

			return client.move('/versions/' + this.get('fileId') + '/' + this.get('id'), '/restore/target', true)
				.done(function() {
					if (options.success) {
						options.success.call(options.context, model, {}, options)
					}
					model.trigger('revert', model, options)
				})
				.fail(function() {
					if (options.error) {
						options.error.call(options.context, model, {}, options)
					}
					model.trigger('error', model, {}, options)
				})
		},

		getFullPath() {
			return this.get('fullPath')
		},

		getPreviewUrl() {
			const url = OC.generateUrl('/apps/files_versions/preview')
			const params = {
				file: this.get('fullPath'),
				version: this.get('id'),
			}
			return url + '?' + OC.buildQueryString(params)
		},

		getDownloadUrl() {
			return OC.linkToRemoteBase('dav') + '/versions/' + this.get('user') + '/versions/' + this.get('fileId') + '/' + this.get('id')
		},
	})

	OCA.Versions = OCA.Versions || {}

	OCA.Versions.VersionModel = VersionModel
})()
