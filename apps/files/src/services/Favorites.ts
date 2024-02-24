/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { Folder, davGetDefaultPropfind, davGetFavoritesReport } from '@nextcloud/files'

import { getClient } from './WebdavClient'
import { resultToNode } from './Files'

const client = getClient()

const reportPayload = davGetFavoritesReport()

export const getContents = async (path = '/'): Promise<ContentsWithRoot> => {
	const propfindPayload = davGetDefaultPropfind()

	// Get root folder
	let rootResponse
	if (path === '/') {
		rootResponse = await client.stat(path, {
			details: true,
			data: propfindPayload,
		}) as ResponseDataDetailed<FileStat>
	}

	const contentsResponse = await client.getDirectoryContents(path, {
		details: true,
		// Only filter favorites if we're at the root
		data: path === '/' ? reportPayload : propfindPayload,
		headers: {
			// Patched in WebdavClient.ts
			method: path === '/' ? 'REPORT' : 'PROPFIND',
		},
		includeSelf: true,
	}) as ResponseDataDetailed<FileStat[]>

	const root = rootResponse?.data || contentsResponse.data[0]
	const contents = contentsResponse.data.filter(node => node.filename !== path)

	return {
		folder: resultToNode(root) as Folder,
		contents: contents.map(resultToNode),
	}
}
