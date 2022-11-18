
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { encodePath } from '@nextcloud/paths'
import { generateUrl } from '@nextcloud/router'
import { getToken, isPublic } from '../utils/davUtils.js'
import { getDavPath } from '../utils/fileUtils.js'

export default {
	computed: {
		/**
		 * Link to the preview path if the file have a preview
		 *
		 * @return {string}
		 */
		previewPath() {
			return this.getPreviewIfAny({
				fileid: this.fileid,
				filename: this.filename,
				hasPreview: this.hasPreview,
				davPath: this.davPath,
			})
		},

		/**
		 * Absolute dav remote path of the file
		 *
		 * @return {string}
		 */
		davPath() {
			return getDavPath({
				filename: this.filename,
				basename: this.basename,
			})
		},
	},
	methods: {
		/**
		 * Return the preview url if the file have an existing
		 * preview or the absolute dav remote path if none.
		 *
		 * @param {object} data destructuring object
		 * @param {string} data.fileid the file id
		 * @param {boolean} data.hasPreview have the file an existing preview ?
		 * @param {string} data.davPath the absolute dav path
		 * @param {string} data.filename the file name
		 * @return {string} the absolute url
		 */
		getPreviewIfAny({ fileid, filename, hasPreview, davPath }) {
			const searchParams = `fileId=${fileid}`
				+ `&x=${Math.floor(screen.width * devicePixelRatio)}`
				+ `&y=${Math.floor(screen.height * devicePixelRatio)}`
				+ '&a=true'

			if (hasPreview) {
				// TODO: find a nicer standard way of doing this?
				if (isPublic()) {
					return generateUrl(`/apps/files_sharing/publicpreview/${getToken()}?file=${encodePath(filename)}&${searchParams}`)
				}
				return generateUrl(`/core/preview?${searchParams}`)
			}
			return davPath
		},
	},
}
