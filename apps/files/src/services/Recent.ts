/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
import type { ContentsWithRoot, Node } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission, davGetRecentSearch, davGetClient, davResultToNode, davRootPath, davRemoteURL } from '@nextcloud/files'
import { useUserConfigStore } from '../store/userconfig.ts'
import { pinia } from '../store/index.ts'

const client = davGetClient()

const lastTwoWeeksTimestamp = Math.round((Date.now() / 1000) - (60 * 60 * 24 * 14))

/**
 * Get recently changed nodes
 *
 * This takes the users preference about hidden files into account.
 * If hidden files are not shown, then also recently changed files *in* hidden directories are filtered.
 *
 * @param path Path to search for recent changes
 */
export const getContents = async (path = '/'): Promise<ContentsWithRoot> => {
	const store = useUserConfigStore(pinia)
	/**
	 * Filter function that returns only the visible nodes - or hidden if explicitly configured
	 * @param node The node to check
	 */
	const filterHidden = (node: Node) =>
		path !== '/' // We need to hide files from hidden directories in the root if not configured to show
		|| store.userConfig.show_hidden // If configured to show hidden files we can early return
		|| !node.dirname.split('/').some((dir) => dir.startsWith('.')) // otherwise only include the file if non of the parent directories is hidden

	const contentsResponse = await client.getDirectoryContents(path, {
		details: true,
		data: davGetRecentSearch(lastTwoWeeksTimestamp),
		headers: {
			// Patched in WebdavClient.ts
			method: 'SEARCH',
			// Somehow it's needed to get the correct response
			'Content-Type': 'application/xml; charset=utf-8',
		},
		deep: true,
	}) as ResponseDataDetailed<FileStat[]>

	const contents = contentsResponse.data

	return {
		folder: new Folder({
			id: 0,
			source: `${davRemoteURL}${davRootPath}`,
			root: davRootPath,
			owner: getCurrentUser()?.uid || null,
			permissions: Permission.READ,
		}),
		contents: contents.map((r) => davResultToNode(r)).filter(filterHidden),
	}
}
