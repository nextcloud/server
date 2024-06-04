/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
