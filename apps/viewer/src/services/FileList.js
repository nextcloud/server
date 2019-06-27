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

import axios from 'axios'
import { generateRemoteUrl } from 'nextcloud-server/dist/router'

/**
 *
 * @param {String} user the current user
 * @param {String} path the path relative to the user root
 * @returns {Array} the file list
 */
export default async function(user, path) {
	const response = await axios({
		method: 'PROPFIND',
		url: generateRemoteUrl(`dav/files/${user}${path}`),
		headers: {
			requesttoken: OC.requestToken,
			'content-Type': 'text/xml'
		},
		data: `<?xml version="1.0"?>
			<d:propfind  xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns"
				xmlns:nc="http://nextcloud.org/ns"
				xmlns:ocs="http://open-collaboration-services.org/ns">
			<d:prop>
				<d:getlastmodified />
				<d:getetag />
				<d:getcontenttype />
				<d:resourcetype />
				<oc:fileid />
				<oc:permissions />
				<oc:size />
				<d:getcontentlength />
				<nc:has-preview />
				<nc:mount-type />
				<nc:is-encrypted />
				<ocs:share-permissions />
				<oc:tags />
				<oc:favorite />
				<oc:comments-unread />
				<oc:owner-id />
				<oc:owner-display-name />
				<oc:share-types />
			</d:prop>
			</d:propfind>`
	})

	const files = OCA.Files.App.fileList.filesClient._client.parseMultiStatus(response.data)
	return files
		.map(file => {
			const fileInfo = OCA.Files.App.fileList.filesClient._parseFileInfo(file)
			fileInfo.href = file.href
			return fileInfo
		})
}
