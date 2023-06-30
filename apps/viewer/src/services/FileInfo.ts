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

import { getClient } from './WebdavClient'
import { genFileInfo, type FileInfo } from '../utils/fileUtils'
import { createClient, type FileStat, type ResponseDataDetailed } from 'webdav'

const statData = `<?xml version="1.0"?>
<d:propfind  xmlns:d="DAV:"
	xmlns:oc="http://owncloud.org/ns"
	xmlns:nc="http://nextcloud.org/ns"
	xmlns:ocs="http://open-collaboration-services.org/ns">
	<d:prop>
		<d:getlastmodified />
		<d:getcontenttype />
		<d:resourcetype />
		<d:getetag />
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

/**
 * Retrieve the files list
 */
export default async function(path: string, options = {}): Promise<FileInfo> {
	const response = await getClient().stat(path, Object.assign({
		data: statData,
		details: true,
	}, options)) as ResponseDataDetailed<FileStat>
	return genFileInfo(response.data)
}

/**
 * Retrieve the files list
 */
export async function rawStat(origin: string, path: string, options = {}) {
	const response = await createClient(origin).stat(path, {
		...options,
		data: statData,
		details: true,
	}) as ResponseDataDetailed<FileStat>

	return response.data
}
