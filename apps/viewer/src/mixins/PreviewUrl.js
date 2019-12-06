
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
import { generateUrl, generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

export default {
	computed: {
		/**
		 * Link to the preview path if the file have a preview
		 * @returns {string}
		 */
		previewpath() {
			if (this.hasPreview) {
				return generateUrl(`/core/preview?fileId=${this.fileid}&x=${screen.width}&y=${screen.height}&a=true`)
			}
			return this.davPath
		},
		/**
		 * Absolute dav remote path of the file
		 * @returns {string}
		 */
		davPath() {
			return generateRemoteUrl(`dav/files/${getCurrentUser().uid}${this.filename}`)
		},
	},
}
