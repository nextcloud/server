
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { generateUrl } from '@nextcloud/router'
import { getRootPath, getToken, isPublic } from '../utils/davUtils'

export default {
	computed: {
		/**
		 * Link to the preview path if the file have a preview
		 * @returns {string}
		 */
		previewpath() {
			return this.getPreviewIfAny({
				fileid: this.fileid,
				filename: this.filename,
				hasPreview: this.hasPreview,
				davPath: this.davPath,
			})
		},

		/**
		 * Absolute dav remote path of the file
		 * @returns {string}
		 */
		davPath() {
			// TODO: allow proper dav access without the need of basic auth
			// https://github.com/nextcloud/server/issues/19700
			if (isPublic()) {
				return generateUrl(`/s/${getToken()}/download?path=${this.filename.replace(this.basename, '')}&files=${this.basename}`)
			}
			return getRootPath() + this.filename
		},
	},
	methods: {
		/**
		 * Return the preview url if the file have an existing
		 * preview or the absolute dav remote path if none.
		 *
		 * @param {Object} data destructuring object
		 * @param {string} data.fileid the file id
		 * @param {boolean} data.hasPreview have the file an existing preview ?
		 * @param {string} data.davPath the absolute dav path
		 * @returns {String} the absolute url
		 */
		getPreviewIfAny({ fileid, filename, hasPreview, davPath }) {
			if (hasPreview) {
				// TODO: find a nicer standard way of doing this?
				if (isPublic()) {
					return generateUrl(`/apps/files_sharing/publicpreview/${getToken()}?fileId=${fileid}&file=${filename}&x=${screen.width}&y=${screen.height}`)
				}
				return generateUrl(`/core/preview?fileId=${fileid}&x=${screen.width}&y=${screen.height}&a=true`)
			}
			return davPath
		},
	},
}
