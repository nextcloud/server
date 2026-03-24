/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot, Node } from '@nextcloud/files'
import type { ResponseDataDetailed, SearchResult } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import { Folder, Permission } from '@nextcloud/files'
import { getRecentSearch, getRemoteURL, getRootPath, resultToNode } from '@nextcloud/files/dav'
import logger from '../logger.ts'
import { getPinia } from '../store/index.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import { client } from './WebdavClient.ts'

const lastTwoWeeksTimestamp = Math.round((Date.now() / 1000) - (60 * 60 * 24 * 14))

/**
 * Get recently changed nodes
 *
 * This takes the users preference about hidden files into account.
 * If hidden files are not shown, then also recently changed files *in* hidden directories are filtered.
 *
 * @param path Path to search for recent changes
 * @param options Options including abort signal
 * @param options.signal Abort signal to cancel the request
 */
export async function getContents(path = '/', options: { signal: AbortSignal }): Promise<ContentsWithRoot> {
	const store = useUserConfigStore(getPinia())

	/**
	 * Filter function that returns only the visible nodes - or hidden if explicitly configured
	 *
	 * @param node The node to check
	 */
	const filterHidden = (node: Node) => path !== '/' // We need to hide files from hidden directories in the root if not configured to show
		|| store.userConfig.show_hidden // If configured to show hidden files we can early return
		|| !node.dirname.split('/').some((dir) => dir.startsWith('.')) // otherwise only include the file if non of the parent directories is hidden

	try {
		const contentsResponse = await client.search('/', {
			signal: options.signal,
			details: true,
			data: getRecentSearch(lastTwoWeeksTimestamp),
		}) as ResponseDataDetailed<SearchResult>

		const contents = contentsResponse.data.results
			.map((stat) => {
				// The search endpoint already includes the dav remote URL so we must not include it in the source
				stat.filename = stat.filename.replace('/remote.php/dav', '')
				return resultToNode(stat)
			})
			.filter(filterHidden)

		return {
			folder: new Folder({
				id: 0,
				source: `${getRemoteURL()}${getRootPath()}`,
				root: getRootPath(),
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.READ,
			}),
			contents,
		}
	} catch (error) {
		if (options.signal.aborted) {
			logger.info('Fetching recent files aborted')
			throw new DOMException('Aborted', 'AbortError')
		}
		logger.error('Failed to fetch recent files', { error })
		throw error
	}
}
