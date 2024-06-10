/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot, Node } from '@nextcloud/files'
import type { ResponseDataDetailed, SearchResult } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission, davGetRecentSearch, davRootPath, davRemoteURL } from '@nextcloud/files'
import { CancelablePromise } from 'cancelable-promise'
import { useUserConfigStore } from '../store/userconfig.ts'
import { pinia } from '../store/index.ts'
import { client } from './WebdavClient.ts'
import { resultToNode } from './Files.ts'

const lastTwoWeeksTimestamp = Math.round((Date.now() / 1000) - (60 * 60 * 24 * 14))

/**
 * Get recently changed nodes
 *
 * This takes the users preference about hidden files into account.
 * If hidden files are not shown, then also recently changed files *in* hidden directories are filtered.
 *
 * @param path Path to search for recent changes
 */
export const getContents = (path = '/'): CancelablePromise<ContentsWithRoot> => {
	const store = useUserConfigStore(pinia)

	/**
	 * Filter function that returns only the visible nodes - or hidden if explicitly configured
	 * @param node The node to check
	 */
	const filterHidden = (node: Node) =>
		path !== '/' // We need to hide files from hidden directories in the root if not configured to show
		|| store.userConfig.show_hidden // If configured to show hidden files we can early return
		|| !node.dirname.split('/').some((dir) => dir.startsWith('.')) // otherwise only include the file if non of the parent directories is hidden

	const controller = new AbortController()
	const handler = async () => {
		const contentsResponse = await client.search('/', {
			signal: controller.signal,
			details: true,
			data: davGetRecentSearch(lastTwoWeeksTimestamp),
		}) as ResponseDataDetailed<SearchResult>

		const contents = contentsResponse.data.results
			.map(resultToNode)
			.filter(filterHidden)

		return {
			folder: new Folder({
				id: 0,
				source: `${davRemoteURL}${davRootPath}`,
				root: davRootPath,
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.READ,
			}),
			contents,
		}
	}

	return new CancelablePromise(async (resolve, reject, cancel) => {
		cancel(() => controller.abort())
		resolve(handler())
	})
}
