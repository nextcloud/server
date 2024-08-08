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
import type { FileStat, ResponseDataDetailed, SearchResult } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission, davGetRecentSearch, davGetClient, davResultToNode, davRootPath, davRemoteURL } from '@nextcloud/files'
import { getBaseUrl } from '@nextcloud/router'
import { CancelablePromise } from 'cancelable-promise'
import { useUserConfigStore } from '../store/userconfig.ts'
import { pinia } from '../store/index.ts'

const client = davGetClient()

const lastTwoWeeksTimestamp = Math.round((Date.now() / 1000) - (60 * 60 * 24 * 14))

/**
 * Helper to map a WebDAV result to a Nextcloud node
 * The search endpoint already includes the dav remote URL so we must not include it in the source
 *
 * @param stat the WebDAV result
 */
const resultToNode = (stat: FileStat) => davResultToNode(stat, davRootPath, getBaseUrl())

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

	const abort = new AbortController()

	return new CancelablePromise(async (resolve, reject, cancel) => {
		cancel(() => abort.abort())

		let contentsResponse: ResponseDataDetailed<SearchResult>
		try {
			contentsResponse = await client.search('/', {
				details: true,
				data: davGetRecentSearch(lastTwoWeeksTimestamp),
				signal: abort.signal,
			}) as ResponseDataDetailed<SearchResult>
		} catch (e) {
			reject(e)
			return
		}
	
		if (abort.signal.aborted) {
			reject()
			return
		}

		const contents = contentsResponse.data.results
			.map(resultToNode)
			.filter(filterHidden)

		resolve({
			contents,
			folder: new Folder({
				id: 0,
				source: `${davRemoteURL}${davRootPath}`,
				root: davRootPath,
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.READ,
			}),
		})
	})
}
